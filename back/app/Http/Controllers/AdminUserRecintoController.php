<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Recinto;
use App\Models\Mesa;
use Illuminate\Http\Request;

class AdminUserRecintoController extends Controller
{
    public function bootstrap()
    {
        return response()->json([
            'users' => $this->buildUsersPayload(),
            'recintos' => $this->buildRecintosOruroPayload(),
            'no_asignados' => $this->buildRecintosNoAsignadosPayload(),
        ]);
    }

    /**
     * Lista usuarios con:
     * - recintos asignados
     * - TOTAL de mesas asignadas (sumatoria de mesas de sus recintos)
     */
    public function users()
    {
        return $this->buildUsersPayload();
    }

    private function buildUsersPayload()
    {
        return User::query()
            ->select('id','name','username','role','avatar','email')
            ->with([
                'recintos:id,nombre',
            ])
//            ->where usel role Administrador Suervisor
            ->whereIn('role', ['Administrador', 'Supervisor'])
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
        return $this->buildRecintosOruroPayload();
    }

    private function buildRecintosOruroPayload()
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
//        &pais_id=1&departamento_id=5&provincia_id=57&municipio_id=191&localidad_id=1988&page=1&per_page=10
            ->where('pais_id', 1)
            ->where('departamento_id', 5)
//            ->where('provincia_id', 57)
//            ->where('municipio_id', 191)
//            ->where('localidad_id', 1988)
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
        return $this->buildRecintosNoAsignadosPayload();
    }

    private function buildRecintosNoAsignadosPayload()
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
            //        &pais_id=1&departamento_id=5&provincia_id=57&municipio_id=191&localidad_id=1988&page=1&per_page=10
            ->where('pais_id', 1)
            ->where('departamento_id', 5)
            ->where('provincia_id', 57)
            ->where('municipio_id', 191)
            ->where('localidad_id', 1988)
            ->whereDoesntHave('users')
            ->withCount('mesas')
            ->orderBy('nombre')
            ->get();
    }
}
