<?php

namespace App\Http\Controllers;

use App\Models\Mesa;
use App\Models\Recinto;
use App\Models\User;
use Illuminate\Http\Request;

class AdminRecintoJefeMapaController extends Controller
{
    private function visibleRecintosQuery(User $user)
    {
        $query = Recinto::query()
            ->with([
                'jefe:id,name,username,celular',
                'mesas:id,recinto_id,numero_mesa,delegado_id,estado',
                'mesas.delegado:id,name,username',
            ])
            ->withCount([
                'mesas',
                'mesas as mesas_asignadas_count' => function ($qq) {
                    $qq->whereNotNull('delegado_id');
                }
            ]);

        if ($user->role === 'Administrador') {
            return $query
                ->where('pais_id', 1)
                ->where('departamento_id', 5);
        }

        if ($user->role === 'Supervisor') {
            return $query
                ->whereHas('users', fn ($q) => $q->where('users.id', $user->id));
        }

        return null;
    }

    private function mapRecintoPayload(Recinto $r): Recinto
    {
        $jefes = collect($r->jefe ?? [])
            ->map(function ($jefe) {
                $jefe->super_jefe = (bool) ($jefe->pivot->super_jefe ?? false);
                return $jefe;
            })
            ->values()
            ->sortBy('name')
            ->values();

        $mesas = collect($r->mesas ?? [])
            ->map(function (Mesa $mesa) {
                return [
                    'id' => $mesa->id,
                    'recinto_id' => $mesa->recinto_id,
                    'numero_mesa' => $mesa->numero_mesa,
                    'delegado_id' => $mesa->delegado_id,
                    'delegado' => $mesa->delegado ? [
                        'id' => $mesa->delegado->id,
                        'name' => $mesa->delegado->name,
                        'username' => $mesa->delegado->username,
                    ] : null,
                    'estado' => $mesa->estado,
                ];
            })
            ->sortBy('numero_mesa')
            ->values();

        $r->setRelation('jefe', $jefes);
        $r->setRelation('mesas', $mesas);

        $total = (int) ($r->mesas_count ?? 0);
        $asignadas = (int) ($r->mesas_asignadas_count ?? 0);
        $delegadosOk = $total > 0 ? ($asignadas >= $total) : true;
        $r->mesas_total = $total;
        $r->mesas_asignadas = $asignadas;
        $r->delegados_ok = $delegadosOk;
        $r->mesas_faltan = max(0, $total - $asignadas);

        return $r;
    }

    private function availableJefes(User $user)
    {
        if (!in_array($user->role, ['Administrador', 'Supervisor'])) {
            return collect();
        }

        if ($user->role === 'Administrador') {
            return User::where('role', 'Jefe de Recinto')
                ->select('id', 'name', 'username', 'celular')
                ->orderBy('name')
                ->get();
        }

        return $user->jefesAsignados()
            ->select('users.id', 'users.name', 'users.username', 'users.celular')
            ->orderBy('users.name')
            ->get();
    }

    private function availableDelegados()
    {
        return User::query()
            ->select('id', 'name', 'username')
            ->where('role', 'Delegado de Mesa')
            ->orderBy('name')
            ->get();
    }

    public function bootstrap(Request $request)
    {
        $user = $request->user();
        $recintosQuery = $this->visibleRecintosQuery($user);

        if (!$recintosQuery) {
            return response()->json([], 403);
        }

        return response()->json([
            'recintos' => $recintosQuery
                ->get()
                ->map(fn ($recinto) => $this->mapRecintoPayload($recinto))
                ->values(),
            'jefes' => $this->availableJefes($user)->values(),
            'delegados' => $this->availableDelegados()->values(),
        ]);
    }

    /**
     * Recintos visibles para el usuario logueado
     */
    public function recintos(Request $request)
    {
        $user = $request->user();
        $query = $this->visibleRecintosQuery($user);
        if (!$query) {
            return response()->json([], 403);
        }

        return $query
            ->get()
            ->map(fn ($recinto) => $this->mapRecintoPayload($recinto))
            ->values();
    }

    /**
     * Jefes disponibles para asignar
     */
    public function jefes(Request $request)
    {
        $user = $request->user();
        if (!in_array($user->role, ['Administrador', 'Supervisor'])) {
            return response()->json([], 403);
        }

        return $this->availableJefes($user)->values();
    }

    /**
     * Asignar multiples jefes a recinto
     */
    public function asignar(Request $request, Recinto $recinto)
    {
        $data = $request->validate([
            'jefes' => 'nullable|array',
            'jefes.*.id' => 'required|integer|exists:users,id',
            'jefes.*.super_jefe' => 'nullable|boolean',
            'jefe_ids' => 'nullable|array',
            'jefe_ids.*' => 'integer|exists:users,id',
        ]);

        $jefes = collect($data['jefes'] ?? [])
            ->map(function ($item) {
                return [
                    'id' => (int) ($item['id'] ?? 0),
                    'super_jefe' => (bool) ($item['super_jefe'] ?? false),
                ];
            })
            ->filter(fn ($item) => $item['id'] > 0)
            ->unique('id')
            ->values();

        if ($jefes->isEmpty() && array_key_exists('jefe_ids', $data)) {
            $jefes = collect($data['jefe_ids'] ?? [])
                ->map(fn ($id) => ['id' => (int) $id, 'super_jefe' => false])
                ->unique('id')
                ->values();
        }

        $ids = $jefes->pluck('id')->values();

        if ($ids->isNotEmpty()) {
            $validCount = User::query()
                ->whereIn('id', $ids)
                ->where('role', 'Jefe de Recinto')
                ->count();

            if ($validCount !== $ids->count()) {
                return response()->json(['message' => 'Uno o mas usuarios no son Jefe de Recinto'], 422);
            }
        }

        $payload = $jefes
            ->mapWithKeys(fn ($item) => [
                $item['id'] => ['super_jefe' => (bool) $item['super_jefe']],
            ])
            ->all();

        $recinto->jefe()->sync($payload);

        return response()->json(['ok' => true]);
    }
}
