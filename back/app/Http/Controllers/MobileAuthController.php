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
            'fecha_nacimiento' => 'required|date',
        ]);

        $fecha = substr($data['fecha_nacimiento'], 0, 10);

        $user = User::query()
            ->where('ci', $data['ci'])
            ->whereDate('fecha_nacimiento', $fecha)
            ->first();

        if (!$user) {
            return response()->json(['message' => 'CI o fecha de nacimiento incorrectos'], 401);
        }

        // ✅ Mesa asignada pendiente (la más simple)
        $mesa = Mesa::query()
            ->where('delegado_id', $user->id)
            ->where('estado', 'PENDIENTE')
            ->with([
                'recinto:id,nombre,lat,lng', // ajusta si tienes lat/lng en recinto
                'localidad:id,nombre',
                'municipio:id,nombre',
                'provincia:id,nombre',
                'departamento:id,nombre',
            ])
            ->orderBy('id', 'desc')
            ->first();

        // ✅ Jerarquía (ajusta a tu modelo/relaciones)
        $jefe = $user->jefe_id ? User::find($user->jefe_id) : null;
        $supervisor = $user->supervisor_id ? User::find($user->supervisor_id) : null;

        // Token Sanctum
        $token = $user->createToken('mobile')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name
                    ?? trim(($user->nombres ?? '').' '.($user->apellido_paterno ?? '').' '.($user->apellido_materno ?? '')),
                'ci' => $user->ci,
                'fecha_nacimiento' => $user->fecha_nacimiento?->format('Y-m-d'),
                'role' => $user->role ?? null,
            ],
            'jerarquia' => [
                'jefe' => $jefe ? ['id'=>$jefe->id, 'name'=>$jefe->name ?? $jefe->nombres] : null,
                'supervisor' => $supervisor ? ['id'=>$supervisor->id, 'name'=>$supervisor->name ?? $supervisor->nombres] : null,
            ],
            'mesa' => $mesa ? [
                'id' => $mesa->id,
                'numero_mesa' => $mesa->numero_mesa,
                'estado' => $mesa->estado,
                'recinto' => $mesa->recinto ? [
                    'id' => $mesa->recinto->id,
                    'nombre' => $mesa->recinto->nombre,
                    'lat' => $mesa->recinto->lat ?? null,
                    'lng' => $mesa->recinto->lng ?? null,
                ] : null,
                'localidad' => $mesa->localidad?->nombre,
                'municipio' => $mesa->municipio?->nombre,
                'provincia' => $mesa->provincia?->nombre,
                'departamento' => $mesa->departamento?->nombre,
            ] : null,
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
