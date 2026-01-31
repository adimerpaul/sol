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

class UserController extends Controller{
    function permissions(){
        return Permission::all();
    }
    public function updateAvatar(Request $request, $userId)
    {
        $user = User::find($userId);
        if (!$user) {
            return response()->json(['message' => 'Usuario no encontrado'], 404);
        }

        if ($request->hasFile('avatar')) {
            $file = $request->file('avatar');
            $filename = time() . '.' . $file->getClientOriginalExtension();
            $path = public_path('images/' . $filename);

            // Crear instancia del gestor de imágenes
            $manager = new ImageManager(new Driver()); // O new Imagick\Driver()

            // Redimensionar y comprimir
            $manager->read($file->getPathname())
                ->resize(300, 300) // o no pongas resize si no quieres cambiar tamaño
                ->toJpeg(70)       // calidad 70%
                ->save($path);

            $user->avatar = $filename;
            $user->save();

            return response()->json(['message' => 'Avatar actualizado', 'avatar' => $filename]);
        }

        return response()->json(['message' => 'No se ha enviado un archivo'], 400);
    }
    function login(Request $request){
        $credentials = $request->only('username', 'password');
        $user = User::where('username', $credentials['username'])->with('permissions:id,name')->first();
        if (!$user || !password_verify($credentials['password'], $user->password)) {
            return response()->json([
                'message' => 'Usuario o contraseña incorrectos',
            ], 401);
        }
        $token = $user->createToken('auth_token')->plainTextToken;
        return response()->json([
            'token' => $token,
            'user' => $user,
        ]);
    }
    function logout(Request $request){
        $request->user()->currentAccessToken()->delete();
        return response()->json([
            'message' => 'Token eliminado',
        ]);
    }
    function me(Request $request){
        $user = $request->user();
        $user->load('permissions:id,name');
        return response()->json($user);
    }
    public function index()
    {
        return User::query()
            ->where('id', '!=', 0)
            ->with('permissions:id,name')
            ->orderBy('id', 'desc')
            ->get()
            ->map(function ($u) {
                $u->ci_anverso_url   = $u->ci_anverso ? Storage::url($u->ci_anverso) : null;
                $u->ci_reverso_url   = $u->ci_reverso ? Storage::url($u->ci_reverso) : null;
                $u->foto_personal_url= $u->foto_personal ? Storage::url($u->foto_personal) : null;
                return $u;
            });
    }
//    function update(Request $request, $id){
//        $user = User::find($id);
//        $user->update($request->except('password'));
//        error_log('User' . json_encode($user));
//        return $user;
//    }
    function updatePassword(Request $request, $id){
        $user = User::find($id);
        $user->update([
            'password' => bcrypt($request->password),
        ]);
        return $user;
    }
    public function store(Request $request)
    {
        $data = $request->validate([
            'nombres' => 'required|string|max:120',
            'apellido_paterno' => 'nullable|string|max:120',
            'apellido_materno' => 'required|string|max:120',
            'ci' => 'required|string|max:30|unique:users,ci',
            'fecha_nacimiento' => 'required|date',
            'bloque' => 'required|string|max:180',

            'username' => 'required|string|max:120|unique:users,username',
            'password' => 'required|string|min:4',
            'role' => 'required|string|max:60',
            'email' => 'nullable|email|max:180',
        ]);

        $data['password'] = bcrypt($data['password']);

        // opcional: compatibilidad con tu "name" viejo
        $data['name'] = trim($data['nombres'].' '.$data['apellido_paterno'].' '.$data['apellido_materno']);

        $user = User::create($data);
        $user->load('permissions:id,name');

        return response()->json($user);
    }

    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $data = $request->validate([
            'nombres' => 'required|string|max:120',
            'apellido_paterno' => 'nullable|string|max:120',
            'apellido_materno' => 'required|string|max:120',
            'ci' => 'required|string|max:30|unique:users,ci,'.$user->id,
            'fecha_nacimiento' => 'required|date',
            'bloque' => 'required|string|max:180',

            'username' => 'required|string|max:120|unique:users,username,'.$user->id,
            'role' => 'required|string|max:60',
            'email' => 'nullable|email|max:180',
        ]);

        $data['name'] = trim($data['nombres'].' '.$data['apellido_paterno'].' '.$data['apellido_materno']);

        $user->update($data);
        $user->load('permissions:id,name');

        return response()->json($user);
    }
    public function updateFiles(Request $request, $userId)
    {
        $user = User::findOrFail($userId);

        $request->validate([
            'ci_anverso'     => 'nullable|image|mimes:jpg,jpeg,png,webp|max:10240',
            'ci_reverso'     => 'nullable|image|mimes:jpg,jpeg,png,webp|max:10240',
            'foto_personal'  => 'nullable|image|mimes:jpg,jpeg,png,webp|max:10240',
        ]);

        $dir = "usuarios/user_{$user->id}";

        foreach (['ci_anverso','ci_reverso','foto_personal'] as $k) {
            if ($request->hasFile($k)) {
                // borrar anterior
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
    function destroy($id){
        return User::destroy($id);
    }
    public function getPermissions($userId)
    {
        $user = User::findOrFail($userId);
        // devuelve IDs de permisos del usuario
        return $user->permissions()->pluck('id');
    }

    public function syncPermissions(Request $request, $userId)
    {
        $request->validate([
            'permissions' => 'array',
            'permissions.*' => 'integer|exists:permissions,id',
        ]);

        $user = User::findOrFail($userId);
        $perms = Permission::whereIn('id', $request->permissions ?? [])->get();
        $user->syncPermissions($perms);

        return response()->json([
            'message' => 'Permisos actualizados',
            'permissions' => $user->permissions()->pluck('name'),
        ]);
    }
}
