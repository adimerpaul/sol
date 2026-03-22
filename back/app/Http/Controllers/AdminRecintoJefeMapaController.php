<?php

namespace App\Http\Controllers;

use App\Models\Mesa;
use App\Models\Recinto;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class AdminRecintoJefeMapaController extends Controller
{
    private const ORURO_PROVINCIA_ID = 57;
    private const ORURO_MUNICIPIO_ID = 191;
    private const ORURO_LOCALIDAD_ID = 1988;

    private function visibleRecintosQuery(User $user, ?Request $request = null)
    {
        $provinciaId = $request?->get('provincia_id');
        $municipioId = $request?->get('municipio_id');
        $localidadId = $request?->get('localidad_id');

        $query = Recinto::query()
            ->with([
                'jefe:id,name,username,celular',
                'provincia:id,nombre',
                'municipio:id,nombre',
                'localidad:id,nombre',
                'mesas:id,recinto_id,numero_mesa,delegado_id,estado',
                'mesas.delegado:id,name,username,celular',
            ])
            ->withCount([
                'mesas',
                'mesas as mesas_asignadas_count' => function ($qq) {
                    $qq->whereNotNull('delegado_id');
                }
            ]);

        if ($user->role === 'Administrador') {
            return $query->where('pais_id', 1)
                ->where('departamento_id', 5)
                ->when($provinciaId, fn ($qq) => $qq->where('provincia_id', $provinciaId))
                ->when($municipioId, fn ($qq) => $qq->where('municipio_id', $municipioId))
                ->when($localidadId, fn ($qq) => $qq->where('localidad_id', $localidadId))
                ->orderBy('nombre');
        }

        if ($user->role === 'Supervisor') {
            return $query
                ->whereHas('users', fn ($q) => $q->where('users.id', $user->id))
                ->when($provinciaId, fn ($qq) => $qq->where('provincia_id', $provinciaId))
                ->when($municipioId, fn ($qq) => $qq->where('municipio_id', $municipioId))
                ->when($localidadId, fn ($qq) => $qq->where('localidad_id', $localidadId))
                ->orderBy('nombre');
        }

        return null;
    }

    private function visibleRecintosBaseQuery(User $user)
    {
        $query = Recinto::query();

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

    private function buildGeoOptionsPayload(User $user): array
    {
        $base = $this->visibleRecintosBaseQuery($user);
        if (!$base) {
            return [
                'provincias' => [],
                'municipios' => [],
                'localidades' => [],
                'defaults' => [
                    'provincia_id' => self::ORURO_PROVINCIA_ID,
                    'municipio_id' => self::ORURO_MUNICIPIO_ID,
                    'localidad_id' => self::ORURO_LOCALIDAD_ID,
                ],
            ];
        }

        $rows = (clone $base)
            ->with([
                'provincia:id,nombre',
                'municipio:id,nombre,provincia_id',
                'localidad:id,nombre,municipio_id',
            ])
            ->get(['id', 'provincia_id', 'municipio_id', 'localidad_id']);

        $provincias = $rows
            ->filter(fn ($recinto) => $recinto->provincia)
            ->map(fn ($recinto) => [
                'id' => $recinto->provincia->id,
                'nombre' => $recinto->provincia->nombre,
            ])
            ->unique('id')
            ->sortBy('nombre')
            ->values();

        $municipios = $rows
            ->filter(fn ($recinto) => $recinto->municipio)
            ->map(fn ($recinto) => [
                'id' => $recinto->municipio->id,
                'nombre' => $recinto->municipio->nombre,
                'provincia_id' => $recinto->municipio->provincia_id,
            ])
            ->unique('id')
            ->sortBy('nombre')
            ->values();

        $localidades = $rows
            ->filter(fn ($recinto) => $recinto->localidad)
            ->map(fn ($recinto) => [
                'id' => $recinto->localidad->id,
                'nombre' => $recinto->localidad->nombre,
                'municipio_id' => $recinto->localidad->municipio_id,
            ])
            ->unique('id')
            ->sortBy('nombre')
            ->values();

        return [
            'provincias' => $provincias,
            'municipios' => $municipios,
            'localidades' => $localidades,
            'defaults' => [
                'provincia_id' => self::ORURO_PROVINCIA_ID,
                'municipio_id' => self::ORURO_MUNICIPIO_ID,
                'localidad_id' => self::ORURO_LOCALIDAD_ID,
            ],
        ];
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
                        'celular' => $mesa->delegado->celular,
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
        $r->provincia_nombre = $r->provincia?->nombre;
        $r->municipio_nombre = $r->municipio?->nombre;
        $r->localidad_nombre = $r->localidad?->nombre;

        return $r;
    }

    private function availableJefes(User $user)
    {
        if (!in_array($user->role, ['Administrador', 'Supervisor'])) {
            return collect();
        }

        if ($user->role === 'Administrador') {
            return User::whereIn('role', ['Administrador', 'Jefe de Recinto', 'Delegado de Mesa'])
                ->select('id', 'name', 'username', 'celular', 'role')
                ->orderBy('name')
                ->get();
        }

        $jefes = $user->jefesAsignados()
            ->select('users.id', 'users.name', 'users.username', 'users.celular', 'users.role')
            ->orderBy('users.name')
            ->get();

        $delegados = User::query()
            ->whereIn('role', ['Administrador', 'Delegado de Mesa'])
            ->select('id', 'name', 'username', 'celular', 'role')
            ->orderBy('name')
            ->get();

        return $jefes
            ->concat($delegados)
            ->unique('id')
            ->values();
    }

    private function availableDelegados()
    {
        return User::query()
            ->select('id', 'name', 'username', 'role')
            ->whereIn('role', ['Administrador', 'Delegado de Mesa'])
            ->orderBy('name')
            ->get();
    }

    public function bootstrap(Request $request)
    {
        $user = $request->user();
        $recintosQuery = $this->visibleRecintosQuery($user, $request);

        if (!$recintosQuery) {
            return response()->json([], 403);
        }

        return response()->json([
            'geo' => $this->buildGeoOptionsPayload($user),
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
        $query = $this->visibleRecintosQuery($user, $request);
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
                ->whereIn('role', ['Administrador', 'Jefe de Recinto', 'Delegado de Mesa'])
                ->count();

            if ($validCount !== $ids->count()) {
                return response()->json(['message' => 'Uno o mas usuarios no son Administrador, Jefe de Recinto o Delegado de Mesa'], 422);
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

    public function printMesasSinDelegado(Request $request)
    {
        $user = $request->user();
        $query = $this->visibleRecintosQuery($user, $request);
        if (!$query) {
            return response()->json([], 403);
        }

        $rows = $query
            ->get()
            ->flatMap(function (Recinto $recinto) {
                $jefes = collect($recinto->jefe ?? []);
                $jefeNombres = $jefes->pluck('name')->filter()->implode(', ');
                $jefeCelulares = $jefes->pluck('celular')->filter()->implode(', ');

                return collect($recinto->mesas ?? [])
                    ->filter(fn ($mesa) => empty($mesa['delegado_id']))
                    ->map(function ($mesa) use ($recinto, $jefeNombres, $jefeCelulares) {
                        return [
                            'recinto' => $recinto->nombre,
                            'provincia' => $recinto->provincia?->nombre,
                            'municipio' => $recinto->municipio?->nombre,
                            'localidad' => $recinto->localidad?->nombre,
                            'mesa_numero' => $mesa['numero_mesa'] ?? null,
                            'estado' => $mesa['estado'] ?? 'PENDIENTE',
                            'jefes' => $jefeNombres ?: 'Sin jefe asignado',
                            'celulares' => $jefeCelulares ?: 'Sin celular',
                        ];
                    });
            })
            ->sortBy([
                ['provincia', 'asc'],
                ['municipio', 'asc'],
                ['localidad', 'asc'],
                ['recinto', 'asc'],
                ['mesa_numero', 'asc'],
            ])
            ->values();

        $pdf = Pdf::loadView('pdf.mapa_recintos_mesas_sin_delegado', [
            'title' => 'Mesas sin delegado asignado',
            'rows' => $rows,
            'generatedAt' => now()->format('d/m/Y H:i'),
            'generatedBy' => $user->name ?? $user->username ?? 'Sistema',
        ])->setPaper('a4', 'landscape');

        return $pdf->stream('mesas_sin_delegado.pdf');
    }

    public function printRecintosSinJefe(Request $request)
    {
        $user = $request->user();
        $query = $this->visibleRecintosQuery($user, $request);
        if (!$query) {
            return response()->json([], 403);
        }

        $rows = $query
            ->get()
            ->filter(fn (Recinto $recinto) => collect($recinto->jefe ?? [])->isEmpty())
            ->map(function (Recinto $recinto) {
                return [
                    'recinto' => $recinto->nombre,
                    'provincia' => $recinto->provincia?->nombre,
                    'municipio' => $recinto->municipio?->nombre,
                    'localidad' => $recinto->localidad?->nombre,
                    'mesas_total' => (int) ($recinto->mesas_count ?? 0),
                    'mesas_asignadas' => (int) ($recinto->mesas_asignadas_count ?? 0),
                    'mesas_faltan' => max(0, (int) ($recinto->mesas_count ?? 0) - (int) ($recinto->mesas_asignadas_count ?? 0)),
                ];
            })
            ->sortBy([
                ['provincia', 'asc'],
                ['municipio', 'asc'],
                ['localidad', 'asc'],
                ['recinto', 'asc'],
            ])
            ->values();

        $pdf = Pdf::loadView('pdf.mapa_recintos_recintos_sin_jefe', [
            'title' => 'Recintos sin jefe asignado',
            'rows' => $rows,
            'generatedAt' => now()->format('d/m/Y H:i'),
            'generatedBy' => $user->name ?? $user->username ?? 'Sistema',
        ])->setPaper('a4', 'landscape');

        return $pdf->stream('recintos_sin_jefe.pdf');
    }

    public function printJefesMesasDelegados(Request $request)
    {
        $user = $request->user();
        $query = $this->visibleRecintosQuery($user, $request);
        if (!$query) {
            return response()->json([], 403);
        }

        $groups = $query
            ->get()
            ->flatMap(function (Recinto $recinto) {
                $mesas = collect($recinto->mesas ?? [])
                    ->map(function ($mesa) use ($recinto) {
                        return [
                            'recinto' => $recinto->nombre,
                            'provincia' => $recinto->provincia?->nombre,
                            'municipio' => $recinto->municipio?->nombre,
                            'localidad' => $recinto->localidad?->nombre,
                            'mesa_numero' => $mesa['numero_mesa'] ?? null,
                            'estado' => $mesa['estado'] ?? 'PENDIENTE',
                            'delegado_nombre' => $mesa['delegado']['name'] ?? 'Sin delegado',
                            'delegado_username' => $mesa['delegado']['username'] ?? '',
                            'delegado_celular' => $mesa['delegado']['celular'] ?? 'Sin celular',
                        ];
                    })
                    ->sortBy('mesa_numero')
                    ->values();

                return collect($recinto->jefe ?? [])->map(function ($jefe) use ($mesas, $recinto) {
                    return [
                        'jefe_id' => $jefe->id,
                        'jefe_nombre' => $jefe->name,
                        'jefe_username' => $jefe->username,
                        'jefe_celular' => $jefe->celular ?: 'Sin celular',
                        'super_jefe' => (bool) ($jefe->super_jefe ?? false),
                        'recinto' => $recinto->nombre,
                        'provincia' => $recinto->provincia?->nombre,
                        'municipio' => $recinto->municipio?->nombre,
                        'localidad' => $recinto->localidad?->nombre,
                        'mesas' => $mesas,
                    ];
                });
            })
            ->groupBy('jefe_id')
            ->map(function ($items) {
                $first = $items->first();
                return [
                    'jefe_nombre' => $first['jefe_nombre'],
                    'jefe_username' => $first['jefe_username'],
                    'jefe_celular' => $first['jefe_celular'],
                    'super_jefe' => $first['super_jefe'],
                    'recintos' => $items
                        ->map(function ($item) {
                            return [
                                'recinto' => $item['recinto'],
                                'provincia' => $item['provincia'],
                                'municipio' => $item['municipio'],
                                'localidad' => $item['localidad'],
                                'mesas' => $item['mesas'],
                            ];
                        })
                        ->sortBy('recinto')
                        ->values(),
                ];
            })
            ->sortBy('jefe_nombre')
            ->values();

        $pdf = Pdf::loadView('pdf.mapa_recintos_jefes_mesas_delegados', [
            'title' => 'Jefes, mesas y delegados asignados',
            'groups' => $groups,
            'generatedAt' => now()->format('d/m/Y H:i:s'),
            'generatedBy' => $user->name ?? $user->username ?? 'Sistema',
        ])->setPaper('a4', 'portrait');

        return $pdf->stream('jefes_mesas_delegados.pdf');
    }
}
