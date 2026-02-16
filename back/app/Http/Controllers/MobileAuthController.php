<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Mesa;
use Illuminate\Http\Request;

class MobileAuthController extends Controller
{
    public function login(Request $request)
    {
        $data = $request->validate([
            'ci' => 'required|string|max:30',
            'fecha_nacimiento' => 'required|string',
        ]);

        $fecha = substr($data['fecha_nacimiento'], 0, 10);
        error_log("Login attempt - CI: {$data['ci']}, Fecha Nacimiento: {$fecha}");

        $user = User::query()
            ->where('ci', $data['ci'])
            ->whereDate('fecha_nacimiento', $fecha)
            ->first();

        if (!$user) {
            return response()->json(['message' => 'CI o fecha de nacimiento incorrectos'], 401);
        }

        // ✅ Mesa asignada pendiente (la más simple)
        $mesas = Mesa::query()
            ->where('delegado_id', $user->id)
            ->where('estado', 'PENDIENTE')
            ->with([
                'recinto:id,nombre,latitud,longitud',
                'localidad:id,nombre',
                'municipio:id,nombre',
                'provincia:id,nombre',
                'departamento:id,nombre',
            ])
            ->orderBy('id', 'desc')
            ->get();

        // Jerarquia via tablas pivote:
        // delegado -> jefe (jefe_delegado), jefe -> supervisor (supervisor_jefe)
        $jefe = $user->jefes()->select('users.id', 'users.name', 'users.nombres')->first();
        $supervisor = $jefe
            ? $jefe->supervisores()->select('users.id', 'users.name', 'users.nombres')->first()
            : null;

        // Token Sanctum
        $token = $user->createToken('mobile')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name
                    ?? trim(($user->nombres ?? '').' '.($user->apellido_paterno ?? '').' '.($user->apellido_materno ?? '')),
                'ci' => $user->ci,
                'fecha_nacimiento' => $user->fecha_nacimiento,
                'role' => $user->role ?? null,
            ],
            'jerarquia' => [
                'jefe' => $jefe ? ['id'=>$jefe->id, 'name'=>$jefe->name ?? $jefe->nombres] : null,
                'supervisor' => $supervisor ? ['id'=>$supervisor->id, 'name'=>$supervisor->name ?? $supervisor->nombres] : null,
            ],
            'mesas' => $mesas
        ]);
    }

    public function me(Request $request)
    {
        $u = $request->user();
        return response()->json([
            'user' => [
                'id' => $u->id,
                'name' => $u->name ?? trim(($u->nombres ?? '').' '.($u->apellido_paterno ?? '').' '.($u->apellido_materno ?? '')),
                'ci' => $u->ci,
                'role' => $u->role ?? null,
            ],
        ]);
    }
}
