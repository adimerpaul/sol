<?php

namespace App\Http\Controllers;

//use App\Mail\UserCreatedMail;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class UserController extends Controller
{
    private function isAdmin(User $user): bool
    {
        return (string) ($user->role ?? '') === 'Administrador';
    }

    private function canManageUser(User $actor, User $target): bool
    {
        if ($this->isAdmin($actor)) {
            return true;
        }

        return (int) ($target->created_by ?? 0) === (int) $actor->id;
    }

    private function makeUniqueUsername(string $base, ?int $ignoreUserId = null): string
    {
        $base = trim($base);
        if ($base === '') {
            $base = 'user';
        }

        $candidate = $base;
        $i = 1;

        while (true) {
            $query = User::query()->where('username', $candidate);
            if ($ignoreUserId) {
                $query->where('id', '!=', $ignoreUserId);
            }
            if (!$query->exists()) {
                return $candidate;
            }
            $candidate = $base . '_' . $i;
            $i++;
        }
    }

    private function resolvedPermissions(User $user)
    {
        return $user->getAllPermissions()
            ->map(fn ($p) => [
                'id' => $p->id,
                'name' => $p->name,
            ])
            ->values();
    }

    private function userPayload(User $user): array
    {
        $user->loadMissing('recinto:id,nombre', 'creator:id,name,username');
        $data = $user->toArray();
        $data['permissions'] = $this->resolvedPermissions($user);
        $data['recinto_nombre'] = $user->recinto?->nombre;
        $data['creator'] = $user->creator ? [
            'id' => $user->creator->id,
            'name' => $user->creator->name,
            'username' => $user->creator->username,
        ] : null;
        $data['creator_name'] = $user->creator?->name;
        $data['creator_username'] = $user->creator?->username;

        return $data;
    }

    private function buildDelegadoJerarquiaPayload(User $user): ?array
    {
        if ((string) ($user->role ?? '') !== 'Delegado de Mesa') {
            return null;
        }

        $jefes = collect($user->jefes ?? [])->map(function ($jefe) use ($user) {
            $recintoPivot = collect($jefe->recintosComoJefe ?? [])
                ->firstWhere('id', (int) $user->recinto_id);

            $supervisores = collect($jefe->supervisores ?? [])
                ->map(fn ($supervisor) => [
                    'id' => $supervisor->id,
                    'name' => $supervisor->name,
                    'username' => $supervisor->username,
                    'celular' => $supervisor->celular,
                ])
                ->values();

            return [
                'id' => $jefe->id,
                'name' => $jefe->name,
                'username' => $jefe->username,
                'celular' => $jefe->celular,
                'super_jefe' => (bool) ($recintoPivot?->pivot?->super_jefe ?? false),
                'supervisores' => $supervisores,
            ];
        })->values();

        $supervisores = $jefes
            ->flatMap(fn ($jefe) => $jefe['supervisores'] ?? [])
            ->unique('id')
            ->values();

        return [
            'jefes' => $jefes,
            'supervisores' => $supervisores,
        ];
    }

    private function enrichUser(User $user): User
    {
        $user->ci_anverso_url = $user->ci_anverso ? Storage::url($user->ci_anverso) : null;
        $user->ci_reverso_url = $user->ci_reverso ? Storage::url($user->ci_reverso) : null;
        $user->foto_personal_url = $user->foto_personal ? Storage::url($user->foto_personal) : null;
        $user->permissions = $this->resolvedPermissions($user);
        $user->recinto_nombre = $user->recinto?->nombre;
        $user->creator_name = $user->creator?->name;
        $user->creator_username = $user->creator?->username;
        $user->jerarquia = $this->buildDelegadoJerarquiaPayload($user);

        return $user;
    }

    private function applyIndexSearch($query, string $search): void
    {
        $needle = trim($search);
        if ($needle === '') {
            return;
        }

        $like = '%' . $needle . '%';

        $query->where(function ($qq) use ($like) {
            $qq->where('username', 'like', $like)
                ->orWhere('nombres', 'like', $like)
                ->orWhere('apellido_paterno', 'like', $like)
                ->orWhere('apellido_materno', 'like', $like)
                ->orWhere('name', 'like', $like)
                ->orWhere('ci', 'like', $like)
                ->orWhere('numero_mesa', 'like', $like)
                ->orWhere('celular', 'like', $like)
                ->orWhere('fecha_nacimiento', 'like', $like)
                ->orWhere('bloque', 'like', $like)
                ->orWhere('role', 'like', $like)
                ->orWhereHas('recinto', fn ($recinto) => $recinto->where('nombre', 'like', $like))
                ->orWhereHas('creator', function ($creator) use ($like) {
                    $creator->where('name', 'like', $like)
                        ->orWhere('username', 'like', $like);
                })
                ->orWhereHas('jefes', function ($jefes) use ($like) {
                    $jefes->where('users.name', 'like', $like)
                        ->orWhere('users.username', 'like', $like)
                        ->orWhere('users.celular', 'like', $like);
                })
                ->orWhereHas('jefes.supervisores', function ($supervisores) use ($like) {
                    $supervisores->where('users.name', 'like', $like)
                        ->orWhere('users.username', 'like', $like)
                        ->orWhere('users.celular', 'like', $like);
                });
        });
    }

    private function applyIndexSorting($query, string $sortBy, bool $descending): void
    {
        $direction = $descending ? 'desc' : 'asc';

        $sortableColumns = [
            'id' => 'users.id',
            'username' => 'users.username',
            'celular' => 'users.celular',
            'numero_mesa' => 'users.numero_mesa',
            'fecha_nacimiento' => 'users.fecha_nacimiento',
            'bloque' => 'users.bloque',
            'role' => 'users.role',
        ];

        if ($sortBy === 'created_by') {
            $query
                ->leftJoin('users as creators', 'creators.id', '=', 'users.created_by')
                ->select('users.*')
                ->orderByRaw("COALESCE(users.nombres, users.name, '') {$direction}")
                ->orderByRaw("COALESCE(users.apellido_paterno, '') {$direction}")
                ->orderByRaw("COALESCE(users.apellido_materno, '') {$direction}")
                ->orderBy('users.id', 'desc');

            return;
        }

        if ($sortBy === 'recinto_nombre') {
            $query
                ->leftJoin('recintos', 'recintos.id', '=', 'users.recinto_id')
                ->select('users.*')
                ->orderBy('recintos.nombre', $direction)
                ->orderBy('users.id', 'desc');

            return;
        }

        $column = $sortableColumns[$sortBy] ?? 'users.id';
        $query->orderBy($column, $direction)->orderBy('users.id', 'desc');
    }

    function permissions()
    {
        return Permission::all();
    }

    public function printByType(Request $request, string $type)
    {
        $actor = $request->user();

        $rolesMap = [
            'administradores' => ['Administrador'],
            'supervisores' => ['Supervisor'],
            'jefes' => ['Jefe de Recinto'],
            'delegados' => ['Delegado de Mesa'],
        ];

        $normalized = strtolower(trim($type));
        $selectedRoles = $rolesMap[$normalized] ?? null;

        $q = User::query()
            ->with(['recinto:id,nombre'])
            ->orderBy('role')
            ->orderBy('apellido_paterno')
            ->orderBy('apellido_materno')
            ->orderBy('nombres');

        if (!$this->isAdmin($actor)) {
            $q->where('created_by', $actor->id);
        }

        if (is_array($selectedRoles)) {
            $q->whereIn('role', $selectedRoles);
        }

        $users = $q->get()->map(function ($u) {
            return [
                'username' => $u->username,
                'numero_mesa' => $u->numero_mesa,
                'nombres' => $u->nombres,
                'apellido_paterno' => $u->apellido_paterno,
                'apellido_materno' => $u->apellido_materno,
                'name' => $u->name,
                'ci' => $u->ci,
                'fecha_nacimiento' => $u->fecha_nacimiento,
                'celular' => $u->celular,
                'bloque' => $u->bloque,
                'role' => $u->role,
                'recinto_nombre' => $u->recinto?->nombre,
            ];
        })->values();

        $title = match ($normalized) {
            'administradores' => 'Administradores',
            'supervisores' => 'Supervisores',
            'jefes' => 'Jefes de Recinto',
            'delegados' => 'Delegados de Mesa',
            default => 'Todos los Usuarios',
        };

        $pdf = Pdf::loadView('pdf.users_list', [
            'title' => $title,
            'users' => $users,
            'generatedAt' => now()->format('d/m/Y H:i'),
            'generatedBy' => $actor->name ?? $actor->username ?? 'Sistema',
        ])->setPaper('a4', 'landscape');

        return $pdf->stream('usuarios_' . $normalized . '.pdf');
    }

    public function updateAvatar(Request $request, $userId)
    {
        $actor = $request->user();
        $user = User::find($userId);
        if (!$user) {
            return response()->json(['message' => 'Usuario no encontrado'], 404);
        }
        if (!$this->canManageUser($actor, $user)) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        if ($request->hasFile('avatar')) {
            $file = $request->file('avatar');
            $filename = time() . '.' . $file->getClientOriginalExtension();
            $path = public_path('images/' . $filename);

            $manager = new ImageManager(new Driver());

            $manager->read($file->getPathname())
                ->resize(300, 300)
                ->toJpeg(70)
                ->save($path);

            $user->avatar = $filename;
            $user->save();

            return response()->json(['message' => 'Avatar actualizado', 'avatar' => $filename]);
        }

        return response()->json(['message' => 'No se ha enviado un archivo'], 400);
    }

    function login(Request $request)
    {
        $data = $request->validate([
            'username' => 'required|string|max:120',
            'fecha_nacimiento' => 'required|date',
        ]);

        $user = User::query()
            ->where('username', $data['username'])
            ->whereDate('fecha_nacimiento', $data['fecha_nacimiento'])
            ->first();

        if (!$user) {
            return response()->json([
                'message' => 'Username o fecha de nacimiento incorrectos',
            ], 401);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => $this->userPayload($user),
        ]);
    }

    function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Token eliminado',
        ]);
    }

    function me(Request $request)
    {
        $user = $request->user();

        return response()->json($this->userPayload($user));
    }

    public function updateMyProfile(Request $request)
    {
        $user = $request->user();
        $role = (string) ($user->role ?? '');

        if (!in_array($role, ['Administrador', 'Supervisor'], true)) {
            return response()->json(['message' => 'Solo Administrador y Supervisor pueden editar este perfil'], 403);
        }

        $data = $request->validate([
            'username' => [
                'required',
                'string',
                'max:120',
                Rule::unique('users', 'username')->ignore($user->id),
            ],
            'nombres' => 'required|string|max:120',
            'apellido_paterno' => 'nullable|string|max:120',
            'apellido_materno' => 'required|string|max:120',
            'celular' => 'nullable|string|max:30',
            'email' => 'nullable|email|max:180',
        ]);

        $data['name'] = trim(($data['nombres'] ?? '') . ' ' . ($data['apellido_paterno'] ?? '') . ' ' . ($data['apellido_materno'] ?? ''));

        $user->update($data);

        return response()->json([
            'message' => 'Perfil actualizado',
            'user' => $this->userPayload($user->fresh()),
        ]);
    }

    public function index(Request $request)
    {
        $actor = $request->user();
        $q = User::query()->where('id', '!=', 0);

        if (!$this->isAdmin($actor)) {
            $q->where('created_by', $actor->id);
        }

        $this->applyIndexSearch($q, (string) $request->query('search', ''));

        $q
            ->with([
                'permissions:id,name',
                'recinto:id,nombre',
                'creator:id,name,username',
                'jefes' => fn ($qq) => $qq
                    ->select('users.id', 'users.name', 'users.username', 'users.celular')
                    ->with([
                        'supervisores:id,name,username,celular',
                        'recintosComoJefe:id,nombre',
                    ]),
            ]);

        $sortBy = (string) $request->query('sortBy', 'id');
        $descending = filter_var($request->query('descending', true), FILTER_VALIDATE_BOOLEAN);
        $this->applyIndexSorting($q, $sortBy, $descending);

        $paginate = !filter_var($request->query('paginate', true), FILTER_VALIDATE_BOOLEAN) ? false : true;

        if (!$paginate) {
            return $q->get()->map(fn ($u) => $this->enrichUser($u));
        }

        $rowsPerPage = (int) $request->query('rowsPerPage', 15);
        if ($rowsPerPage <= 0) {
            $rowsPerPage = 15;
        }
        $rowsPerPage = min($rowsPerPage, 100);

        /** @var LengthAwarePaginator $users */
        $users = $q->paginate($rowsPerPage)->appends($request->query());
        $users->setCollection(
            $users->getCollection()->map(fn ($u) => $this->enrichUser($u))
        );

        return response()->json($users);
    }

//    function update(Request $request, $id){
//        $user = User::find($id);
//        $user->update($request->except('password'));
//        error_log('User' . json_encode($user));
//        return $user;
//    }

    function updatePassword(Request $request, $id)
    {
        $user = User::findOrFail($id);
        $actor = $request->user();
        if (!$this->canManageUser($actor, $user)) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        $request->validate([
            'password' => 'required|string|min:4',
        ]);

        $user->update([
            'password' => bcrypt($request->password),
        ]);

        return $user;
    }

    public function updateUsername(Request $request, $id)
    {
        $actor = $request->user();
        $user = User::findOrFail($id);

        if (!in_array((string) ($actor->role ?? ''), ['Administrador', 'Supervisor'], true)) {
            return response()->json(['message' => 'Solo Administrador y Supervisor pueden cambiar username'], 403);
        }

        if (!$this->canManageUser($actor, $user)) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        $data = $request->validate([
            'username' => [
                'required',
                'string',
                'max:120',
                Rule::unique('users', 'username')->ignore($user->id),
            ],
        ]);

        $user->username = trim((string) $data['username']);
        $user->save();

        return response()->json([
            'message' => 'Username actualizado',
            'user' => $this->userPayload($user->fresh()),
        ]);
    }

    public function store(Request $request)
    {
        $actor = $request->user();
        $data = $request->validate([
            'nombres' => 'required|string|max:120',
            'apellido_paterno' => 'nullable|string|max:120',
            'apellido_materno' => 'required|string|max:120',
            'ci' => 'required|string|max:30',
            'fecha_nacimiento' => 'required|date',
            'bloque' => 'required|string|max:180',
            'celular' => 'nullable|string|max:30',
            'recinto_id' => 'nullable|integer|exists:recintos,id',
            'numero_mesa' => 'nullable|string|max:50',

            'username' => 'nullable|string|max:120|unique:users,username',
            'password' => 'nullable|string|min:4',
            'role' => 'required|string|max:60',
            'email' => 'nullable|max:180',
        ]);

        if (User::where('ci', $data['ci'])->exists()) {
            return response()->json(['message' => 'Ya existe un usuario con ese CI'], 422);
        }

        if (!$this->isAdmin($actor) && ($data['role'] ?? null) === 'Administrador') {
            return response()->json(['message' => 'Solo un administrador puede crear administradores'], 403);
        }

        $usernameBase = $data['username'] ?? $data['ci'];
        $data['username'] = $this->makeUniqueUsername((string) $usernameBase);
        $data['password'] = bcrypt($data['password'] ?? str()->random(12));
        $data['name'] = trim($data['nombres'] . ' ' . $data['apellido_paterno'] . ' ' . $data['apellido_materno']);
        $data['created_by'] = $actor?->id;

        $user = User::create($data);
        $user->load('permissions:id,name');

        return response()->json($user);
    }

    public function update(Request $request, $id)
    {
        $actor = $request->user();
        $user = User::findOrFail($id);
        if (!$this->canManageUser($actor, $user)) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        $data = $request->validate([
            'nombres' => 'required|string|max:120',
            'apellido_paterno' => 'nullable|string|max:120',
            'apellido_materno' => 'required|string|max:120',
            'ci' => 'required|string|max:30|unique:users,ci,' . $user->id,
            'fecha_nacimiento' => 'required|date',
            'bloque' => 'required|string|max:180',
            'celular' => 'nullable|string|max:30',
            'recinto_id' => 'nullable|integer|exists:recintos,id',
            'numero_mesa' => 'nullable|string|max:50',

            'username' => 'nullable|string|max:120|unique:users,username,' . $user->id,
            'role' => 'required|string|max:60',
            'email' => 'nullable|max:180',
        ]);

        if (!$this->isAdmin($actor) && ($data['role'] ?? null) === 'Administrador') {
            return response()->json(['message' => 'Solo un administrador puede asignar rol Administrador'], 403);
        }

        if (empty($data['username'])) {
            $data['username'] = $this->makeUniqueUsername((string) ($data['ci'] ?? 'user'), $user->id);
        }

        $data['name'] = trim($data['nombres'] . ' ' . $data['apellido_paterno'] . ' ' . $data['apellido_materno']);

        $user->update($data);
        $user->load('permissions:id,name');

        return response()->json($user);
    }

    public function updateFiles(Request $request, $userId)
    {
        $actor = $request->user();
        $user = User::findOrFail($userId);
        if (!$this->canManageUser($actor, $user)) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        $request->validate([
            'ci_anverso' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:10240',
            'ci_reverso' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:10240',
            'foto_personal' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:10240',
        ]);

        $dir = "usuarios/user_{$user->id}";

        foreach (['ci_anverso', 'ci_reverso', 'foto_personal'] as $k) {
            if ($request->hasFile($k)) {
                if (!empty($user->{$k})) {
                    Storage::disk('public')->delete($user->{$k});
                }
                $path = $request->file($k)->store($dir, 'public');
                $user->{$k} = $path;
            }
        }

        $user->save();

        return response()->json([
            'message' => 'Archivos actualizados',
            'ci_anverso_url' => $user->ci_anverso ? Storage::url($user->ci_anverso) : null,
            'ci_reverso_url' => $user->ci_reverso ? Storage::url($user->ci_reverso) : null,
            'foto_personal_url' => $user->foto_personal ? Storage::url($user->foto_personal) : null,
        ]);
    }

    function destroy($id)
    {
        $actor = request()->user();
        $user = User::findOrFail($id);
        if (!$this->canManageUser($actor, $user)) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        return User::destroy($id);
    }

    public function getPermissions($userId)
    {
        $actor = request()->user();
        $user = User::findOrFail($userId);
        if (!$this->canManageUser($actor, $user)) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        return $user->permissions()->pluck('id');
    }

    public function syncPermissions(Request $request, $userId)
    {
        $actor = $request->user();
        $request->validate([
            'permissions' => 'array',
            'permissions.*' => 'integer|exists:permissions,id',
        ]);

        $user = User::findOrFail($userId);
        if (!$this->canManageUser($actor, $user)) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        $perms = Permission::whereIn('id', $request->permissions ?? [])->get();
        $user->syncPermissions($perms);

        return response()->json([
            'message' => 'Permisos actualizados',
            'permissions' => $this->resolvedPermissions($user),
        ]);
    }
}
