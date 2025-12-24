<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Recinto;
use App\Models\Mesa;
use Illuminate\Http\Request;

class AdminUserRecintoController extends Controller
{
    /**
     * Lista usuarios con:
     * - recintos asignados
     * - TOTAL de mesas asignadas (sumatoria de mesas de sus recintos)
     */
    public function users()
    {
        return User::query()
            ->select('id','name','username','role','avatar','email')
            ->with([
                'recintos:id,nombre',
            ])
            ->get()
            ->map(function ($u) {
                $recintoIds = $u->recintos->pluck('id');

                $u->mesas_count = Mesa::whereIn('recinto_id', $recintoIds)->count();
                $u->recintos_count = $u->recintos->count();

                return $u;
            });
    }

    /**
     * Recintos SOLO ORURO con:
     * - conteo de mesas por recinto
     */
    public function recintosOruro()
    {
        return Recinto::query()
            ->with([
                'localidad:id,nombre',
                'municipio:id,nombre',
                'provincia:id,nombre',
                'departamento:id,nombre',
            ])
            ->whereHas('departamento', fn($d) =>
            $d->whereRaw('UPPER(nombre) = ?', ['ORURO'])
            )
            ->withCount('mesas') // 👈 mesas_count
            ->orderBy('nombre')
            ->get();
    }

    /**
     * Sincroniza recintos de un usuario
     */
    public function sync(Request $request, User $user)
    {
        $data = $request->validate([
            'recintos' => 'array',
            'recintos.*' => 'integer|exists:recintos,id',
        ]);

        $user->recintos()->sync($data['recintos'] ?? []);

        return response()->json(['ok' => true]);
    }

    /**
     * Recintos NO asignados a ningún usuario (ORURO)
     */
    public function recintosNoAsignados()
    {
        return Recinto::query()
            ->whereHas('departamento', fn($d) =>
            $d->whereRaw('UPPER(nombre) = ?', ['ORURO'])
            )->with([
                'localidad:id,nombre',
                'municipio:id,nombre',
                'provincia:id,nombre',
                'departamento:id,nombre',
            ])
            ->whereDoesntHave('users')
            ->withCount('mesas')
            ->orderBy('nombre')
            ->get();
    }
}
