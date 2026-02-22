<?php

namespace App\Http\Controllers;

//use App\Mail\UserCreatedMail;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\Storage;

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
        $data = $user->toArray();
        $data['permissions'] = $this->resolvedPermissions($user);

        return $data;
    }

    function permissions()
    {
        return Permission::all();
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
            'ci' => 'required|string|max:30',
            'fecha_nacimiento' => 'required|date',
        ]);

        $user = User::query()
            ->where('ci', $data['ci'])
            ->whereDate('fecha_nacimiento', $data['fecha_nacimiento'])
            ->first();

        if (!$user) {
            return response()->json([
                'message' => 'CI o fecha de nacimiento incorrectos',
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

    public function index()
    {
        $actor = request()->user();
        $q = User::query()->where('id', '!=', 0);

        if (!$this->isAdmin($actor)) {
            $q->where('created_by', $actor->id);
        }

        return $q
            ->with('permissions:id,name')
            ->orderBy('id', 'desc')
            ->get()
            ->map(function ($u) {
                $u->ci_anverso_url = $u->ci_anverso ? Storage::url($u->ci_anverso) : null;
                $u->ci_reverso_url = $u->ci_reverso ? Storage::url($u->ci_reverso) : null;
                $u->foto_personal_url = $u->foto_personal ? Storage::url($u->foto_personal) : null;
                $u->permissions = $this->resolvedPermissions($u);

                return $u;
            });
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
