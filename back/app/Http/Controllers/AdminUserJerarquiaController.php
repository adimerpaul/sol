<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class AdminUserJerarquiaController extends Controller
{
    // Lista supervisores con conteo de jefes
    public function supervisores()
    {
        return User::query()
            ->select('id','name','username','role','avatar','email')
            ->whereIn('role', ['Supervisor', 'Administrador'])
            ->withCount(['jefesAsignados as jefes_count'])
            ->orderBy('name')
            ->get();
    }

    // Lista jefes con conteo de delegados
    public function jefes()
    {
        return User::query()
            ->select('id','name','username','role','avatar','email')
            ->where('role', 'Jefe de Recinto')
            ->withCount(['delegadosAsignados as delegados_count'])
            ->orderBy('name')
            ->get();
    }

    // Lista delegados
    public function delegados()
    {
        return User::query()
            ->select('id','name','username','role','avatar','email')
            ->where('role', 'Delegado de Mesa')
            ->orderBy('name')
            ->get();
    }

    // Devuelve jefes asignados a un supervisor
    public function supervisorJefes(User $supervisor)
    {
//        if ($supervisor->role !== 'Supervisor') {
//            return response()->json(['message' => 'El usuario no es Supervisor'], 422);
//        } pue deser supervisor o administrador
        if (!in_array($supervisor->role, ['Supervisor', 'Administrador'])) {
            return response()->json(['message' => 'El usuario no es Supervisor o Administrador'], 422);
        }

        return $supervisor->jefesAsignados()
            ->select('users.id','users.name','users.username','users.role','users.avatar','users.email')
            ->withCount(['delegadosAsignados as delegados_count'])
            ->orderBy('users.name')
            ->get();
    }

    // Sync jefes a supervisor (reemplaza todo)
    public function syncSupervisorJefes(Request $request, User $supervisor)
    {
        if (!in_array($supervisor->role, ['Supervisor', 'Administrador'])) {
            return response()->json(['message' => 'El usuario no es Supervisor o Administrador'], 422);
        }

        $data = $request->validate([
            'jefes' => 'array',
            'jefes.*' => 'integer|exists:users,id',
        ]);

        $ids = $data['jefes'] ?? [];

        // Validar que todos sean Jefe de Recinto
        $countOk = User::whereIn('id', $ids)->where('role', 'Jefe de Recinto')->count();
        if ($countOk !== count($ids)) {
            return response()->json(['message' => 'Solo puedes asignar usuarios con rol "Jefe de Recinto"'], 422);
        }

        $supervisor->jefesAsignados()->sync($ids);

        return response()->json(['ok' => true]);
    }

    // Devuelve delegados asignados a un jefe
    public function jefeDelegados(User $jefe)
    {
        if ($jefe->role !== 'Jefe de Recinto') {
            return response()->json(['message' => 'El usuario no es Jefe de Recinto'], 422);
        }

        return $jefe->delegadosAsignados()
            ->select('users.id','users.name','users.username','users.role','users.avatar','users.email')
            ->orderBy('users.name')
            ->get();
    }

    // Sync delegados a jefe (reemplaza todo)
    public function syncJefeDelegados(Request $request, User $jefe)
    {
        if ($jefe->role !== 'Jefe de Recinto') {
            return response()->json(['message' => 'El usuario no es Jefe de Recinto'], 422);
        }

        $data = $request->validate([
            'delegados' => 'array',
            'delegados.*' => 'integer|exists:users,id',
        ]);

        $ids = $data['delegados'] ?? [];

        // Validar que todos sean Delegado de Mesa
        $countOk = User::whereIn('id', $ids)->where('role', 'Delegado de Mesa')->count();
        if ($countOk !== count($ids)) {
            return response()->json(['message' => 'Solo puedes asignar usuarios con rol "Delegado de Mesa"'], 422);
        }

        $jefe->delegadosAsignados()->sync($ids);

        return response()->json(['ok' => true]);
    }
}
