<?php

namespace App\Http\Controllers;

use App\Models\Localidad;
use App\Models\Mesa;
use App\Models\Municipio;
use App\Models\Provincia;
use App\Models\Recinto;
use App\Models\ResultadoMesa;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class GraficosController extends Controller
{
    private const JACHA_PARTIDO_ID = 15;
    private const ORURO_PROVINCIA_ID = 57;
    private const ORURO_MUNICIPIO_ID = 191;
    private const ORURO_LOCALIDAD_ID = 1988;

    private const CATEGORY_FIELDS = [
        'alcalde' => 'votos_alcalde',
        'concejal' => 'votos_concejal',
        'gobernador' => 'votos_gobernador',
        'asambleista_distrito' => 'votos_asambleista_distrito',
        'asambleista_poblacion' => 'votos_asambleista_poblacion',
    ];

    private function buildScope(Request $request): array
    {
        return [
            'departamento_id' => 5,
            'provincia_id' => $request->input('provincia_id') ? (int) $request->input('provincia_id') : self::ORURO_PROVINCIA_ID,
            'municipio_id' => $request->input('municipio_id') ? (int) $request->input('municipio_id') : self::ORURO_MUNICIPIO_ID,
            'localidad_id' => $request->input('localidad_id') ? (int) $request->input('localidad_id') : self::ORURO_LOCALIDAD_ID,
            'delegado_id' => $request->input('delegado_id') ? (int) $request->input('delegado_id') : null,
        ];
    }

    private function applyMesaScope($query, array $scope)
    {
        return $query
            ->where('departamento_id', $scope['departamento_id'])
            ->when($scope['provincia_id'], fn ($q) => $q->where('provincia_id', $scope['provincia_id']))
            ->when($scope['municipio_id'], fn ($q) => $q->where('municipio_id', $scope['municipio_id']))
            ->when($scope['localidad_id'], fn ($q) => $q->where('localidad_id', $scope['localidad_id']))
            ->when($scope['delegado_id'], fn ($q) => $q->where('delegado_id', $scope['delegado_id']));
    }

    private function partidoTotals(array $scope)
    {
        return DB::table('partidos as p')
            ->leftJoin('resultado_mesa_detalles as d', function ($join) {
                $join->on('d.partido_id', '=', 'p.id')
                    ->whereNull('d.deleted_at');
            })
            ->leftJoin('resultados_mesa as r', function ($join) {
                $join->on('r.id', '=', 'd.resultado_mesa_id')
                    ->whereNull('r.deleted_at');
            })
            ->leftJoin('mesas as m', function ($join) use ($scope) {
                $join->on('m.id', '=', 'r.mesa_id')
                    ->whereNull('m.deleted_at')
                    ->where('m.departamento_id', '=', $scope['departamento_id']);

                if (!empty($scope['provincia_id'])) {
                    $join->where('m.provincia_id', '=', $scope['provincia_id']);
                }
                if (!empty($scope['municipio_id'])) {
                    $join->where('m.municipio_id', '=', $scope['municipio_id']);
                }
                if (!empty($scope['localidad_id'])) {
                    $join->where('m.localidad_id', '=', $scope['localidad_id']);
                }
                if (!empty($scope['delegado_id'])) {
                    $join->where('m.delegado_id', '=', $scope['delegado_id']);
                }
            })
            ->whereNull('p.deleted_at')
            ->groupBy('p.id', 'p.sigla', 'p.nombre', 'p.color')
            ->selectRaw("
                p.id,
                p.sigla,
                p.nombre,
                p.color,
                COALESCE(SUM(CASE WHEN m.id IS NOT NULL THEN d.votos_alcalde ELSE 0 END), 0) as votos_alcalde,
                COALESCE(SUM(CASE WHEN m.id IS NOT NULL THEN d.votos_concejal ELSE 0 END), 0) as votos_concejal,
                COALESCE(SUM(CASE WHEN m.id IS NOT NULL THEN d.votos_gobernador ELSE 0 END), 0) as votos_gobernador,
                COALESCE(SUM(CASE WHEN m.id IS NOT NULL THEN d.votos_asambleista_distrito ELSE 0 END), 0) as votos_asambleista_distrito,
                COALESCE(SUM(CASE WHEN m.id IS NOT NULL THEN d.votos_asambleista_poblacion ELSE 0 END), 0) as votos_asambleista_poblacion
            ")
            ->get()
            ->map(function ($r) {
                $alcalde = (int) ($r->votos_alcalde ?? 0);
                $concejal = (int) ($r->votos_concejal ?? 0);
                $gobernador = (int) ($r->votos_gobernador ?? 0);
                $asd = (int) ($r->votos_asambleista_distrito ?? 0);
                $asp = (int) ($r->votos_asambleista_poblacion ?? 0);

                return [
                    'id' => (int) $r->id,
                    'sigla' => (string) ($r->sigla ?? ''),
                    'nombre' => (string) ($r->nombre ?? ''),
                    'color' => $r->color ?: null,
                    'votos_alcalde' => $alcalde,
                    'votos_concejal' => $concejal,
                    'votos_gobernador' => $gobernador,
                    'votos_asambleista_distrito' => $asd,
                    'votos_asambleista_poblacion' => $asp,
                    'votos_validos' => $alcalde + $concejal + $gobernador + $asd + $asp,
                ];
            })
            ->values();
    }

    private function buildSummaryPayload(array $scope): array
    {
        $partidosRaw = $this->partidoTotals($scope);
        $ranking = $partidosRaw->sortByDesc('votos_validos')->values();

        $categorias = [];
        foreach (self::CATEGORY_FIELDS as $key => $field) {
            $categorias[$key] = [
                'total' => (int) $partidosRaw->sum($field),
                'ranking' => $partidosRaw->sortByDesc($field)->values(),
            ];
        }

        $mesasBase = $this->applyMesaScope(Mesa::query(), $scope);
        $mesasTotal = (int) (clone $mesasBase)->count();
        $mesasConResultado = (int) ResultadoMesa::query()
            ->whereHas('mesa', fn ($q) => $this->applyMesaScope($q, $scope))
            ->where(function ($query) {
                $this->applyResultadoConDatosConstraint($query);
            })
            ->distinct('mesa_id')
            ->count('mesa_id');

        return [
            'votos_validos_total' => (int) $partidosRaw->sum('votos_validos'),
            'ranking_validos' => $ranking,
            'categorias' => $categorias,
            'mesas' => [
                'total' => $mesasTotal,
                'con_resultado' => $mesasConResultado,
                'faltantes' => max(0, $mesasTotal - $mesasConResultado),
            ],
        ];
    }

    private function applyResultadoConDatosConstraint($query): void
    {
        $query->where(function ($qq) {
            $qq->where('total_votos', '>', 0)
                ->orWhere('total_validos', '>', 0)
                ->orWhere('total_blancos', '>', 0)
                ->orWhere('total_nulos', '>', 0)
                ->orWhereHas('detalles', function ($dq) {
                    $dq->where('votos_gobernador', '>', 0)
                        ->orWhere('votos_asambleista_distrito', '>', 0)
                        ->orWhere('votos_asambleista_poblacion', '>', 0)
                        ->orWhere('votos_concejal', '>', 0)
                        ->orWhere('votos_alcalde', '>', 0);
                });
        });
    }

    private function resultadoTieneDatos($resultado): bool
    {
        if (!$resultado) {
            return false;
        }

        if ((int) ($resultado->total_votos ?? 0) > 0) {
            return true;
        }

        if ((int) ($resultado->total_validos ?? 0) > 0) {
            return true;
        }

        if ((int) ($resultado->total_blancos ?? 0) > 0) {
            return true;
        }

        if ((int) ($resultado->total_nulos ?? 0) > 0) {
            return true;
        }

        return collect($resultado->detalles ?? [])->contains(function ($detalle) {
            return (int) ($detalle->votos_gobernador ?? 0) > 0
                || (int) ($detalle->votos_asambleista_distrito ?? 0) > 0
                || (int) ($detalle->votos_asambleista_poblacion ?? 0) > 0
                || (int) ($detalle->votos_concejal ?? 0) > 0
                || (int) ($detalle->votos_alcalde ?? 0) > 0;
        });
    }

    private function buildOptionsPayload(array $scope): array
    {
        $provincias = Provincia::query()
            ->select('id', 'nombre')
            ->where('departamento_id', $scope['departamento_id'])
            ->orderBy('nombre')
            ->get();

        $municipios = Municipio::query()
            ->select('id', 'nombre', 'provincia_id')
            ->whereHas('provincia', function ($q) use ($scope) {
                $q->where('departamento_id', $scope['departamento_id']);
            })
            ->when($scope['provincia_id'], fn ($q) => $q->where('provincia_id', $scope['provincia_id']))
            ->orderBy('nombre')
            ->get();

        $localidades = Localidad::query()
            ->select('id', 'nombre', 'municipio_id')
            ->whereHas('municipio.provincia', function ($q) use ($scope) {
                $q->where('departamento_id', $scope['departamento_id']);
            })
            ->when($scope['municipio_id'], fn ($q) => $q->where('municipio_id', $scope['municipio_id']))
            ->orderBy('nombre')
            ->get();

        $delegados = User::query()
            ->select('users.id', 'users.name', 'users.username')
            ->where('users.role', 'Delegado de Mesa')
            ->whereHas('recinto', function ($q) use ($scope) {
                $q->where('departamento_id', $scope['departamento_id'])
                    ->when($scope['provincia_id'], fn ($qq) => $qq->where('provincia_id', $scope['provincia_id']))
                    ->when($scope['municipio_id'], fn ($qq) => $qq->where('municipio_id', $scope['municipio_id']))
                    ->when($scope['localidad_id'], fn ($qq) => $qq->where('localidad_id', $scope['localidad_id']));
            })
            ->orderBy('users.name')
            ->get();

        return [
            'provincias' => $provincias,
            'municipios' => $municipios,
            'localidades' => $localidades,
            'delegados' => $delegados,
        ];
    }

    private function buildPhotoUrls($resultado): array
    {
        $photos = [];
        foreach (range(1, 10) as $slot) {
            $key = 'foto' . $slot;
            if (!empty($resultado?->{$key})) {
                $photos[] = [
                    'slot' => $key,
                    'url' => Storage::url($resultado->{$key}),
                ];
            }
        }

        return $photos;
    }

    private function resolveMesaCategoryWinners($detalles): array
    {
        $rows = collect($detalles ?? []);
        $winners = [];

        foreach (self::CATEGORY_FIELDS as $category => $field) {
            $winner = $rows
                ->groupBy('partido_id')
                ->map(function ($groupRows) use ($field) {
                    $first = $groupRows->first();
                    return [
                        'partido_id' => (int) ($first?->partido_id ?? 0),
                        'sigla' => $first?->partido?->sigla,
                        'nombre' => $first?->partido?->nombre,
                        'color' => $first?->partido?->color,
                        'icono' => $first?->partido?->icono,
                        'votos' => (int) collect($groupRows)->sum($field),
                    ];
                })
                ->sortByDesc('votos')
                ->first();

            $winners[$category] = !empty($winner) && (int) ($winner['votos'] ?? 0) > 0
                ? $winner
                : null;
        }

        return $winners;
    }

    private function buildDelegadoPayload($delegado): ?array
    {
        if (!$delegado) {
            return null;
        }

        $jefes = collect($delegado->jefes ?? [])->map(function ($jefe) {
            $supervisores = collect($jefe->supervisores ?? [])->map(fn ($supervisor) => [
                'id' => $supervisor->id,
                'name' => $supervisor->name,
                'username' => $supervisor->username,
                'celular' => $supervisor->celular,
            ])->values();

            return [
                'id' => $jefe->id,
                'name' => $jefe->name,
                'username' => $jefe->username,
                'celular' => $jefe->celular,
                'supervisores' => $supervisores,
            ];
        })->values();

        $supervisores = $jefes
            ->flatMap(fn ($jefe) => $jefe['supervisores'] ?? [])
            ->unique('id')
            ->values();

        return [
            'id' => $delegado->id,
            'name' => $delegado->name,
            'username' => $delegado->username,
            'celular' => $delegado->celular,
            'foto_personal_url' => $delegado->foto_personal ? Storage::url($delegado->foto_personal) : null,
            'ci_anverso_url' => $delegado->ci_anverso ? Storage::url($delegado->ci_anverso) : null,
            'ci_reverso_url' => $delegado->ci_reverso ? Storage::url($delegado->ci_reverso) : null,
            'jefes' => $jefes,
            'supervisores' => $supervisores,
        ];
    }

    private function buildMesaPayload(Mesa $mesa): array
    {
        $resultado = $mesa->resultado;
        $hasVotes = $this->resultadoTieneDatos($resultado);
        $mesaWinners = $this->resolveMesaCategoryWinners($resultado?->detalles ?? []);
        $winnerSummary = collect($mesaWinners)
            ->map(function ($winner, $category) {
                if (!$winner) {
                    return null;
                }

                $label = match ($category) {
                    'alcalde' => 'Alcalde',
                    'concejal' => 'Concejal',
                    'gobernador' => 'Gobernador',
                    'asambleista_distrito' => 'Asam. Territorio',
                    'asambleista_poblacion' => 'Asam. Poblacion',
                    default => ucfirst((string) $category),
                };

                return [
                    'category' => $category,
                    'label' => $label,
                    'sigla' => $winner['sigla'] ?? null,
                    'nombre' => $winner['nombre'] ?? null,
                    'color' => $winner['color'] ?? null,
                    'icono' => $winner['icono'] ?? null,
                    'votos' => (int) ($winner['votos'] ?? 0),
                ];
            })
            ->filter()
            ->values();

        $detalles = collect($resultado?->detalles ?? [])
            ->map(function ($detalle) {
                return [
                    'partido_id' => $detalle->partido_id,
                    'sigla' => $detalle->partido?->sigla,
                    'nombre' => $detalle->partido?->nombre,
                    'color' => $detalle->partido?->color,
                    'icono' => $detalle->partido?->icono,
                    'icono_url' => !empty($detalle->partido?->icono)
                        ? rtrim((string) config('app.url'), '/') . '/../images/partidos/' . ltrim((string) $detalle->partido->icono, '/')
                        : null,
                    'votos_alcalde' => (int) ($detalle->votos_alcalde ?? 0),
                    'votos_concejal' => (int) ($detalle->votos_concejal ?? 0),
                    'votos_gobernador' => (int) ($detalle->votos_gobernador ?? 0),
                    'votos_asambleista_distrito' => (int) ($detalle->votos_asambleista_distrito ?? 0),
                    'votos_asambleista_poblacion' => (int) ($detalle->votos_asambleista_poblacion ?? 0),
                ];
            })
            ->sortByDesc('votos_alcalde')
            ->values();

        return [
            'id' => $mesa->id,
            'numero_mesa' => $mesa->numero_mesa,
            'estado' => $hasVotes ? 'REALIZADO' : ($mesa->estado ?: 'PENDIENTE'),
            'delegado_id' => $mesa->delegado_id,
            'delegado' => $this->buildDelegadoPayload($mesa->delegado),
            'tiene_resultado' => $hasVotes,
            'resultado' => $resultado ? [
                'id' => $resultado->id,
                'total_votos' => (int) ($resultado->total_votos ?? 0),
                'total_validos' => (int) ($resultado->total_validos ?? 0),
                'total_blancos' => (int) ($resultado->total_blancos ?? 0),
                'total_nulos' => (int) ($resultado->total_nulos ?? 0),
                'updated_at' => $resultado->updated_at?->toIso8601String(),
                'observacion' => $resultado->observacion,
                'registrado_por_id' => $resultado->registrado_por,
                'registrado_por_nombre' => $resultado->registradoPor?->name ?: $resultado->registradoPor?->username,
                'fotos' => $this->buildPhotoUrls($resultado),
                'ganadores' => $mesaWinners,
                'ganadores_resumen' => $winnerSummary,
                'detalles' => $detalles,
            ] : null,
        ];
    }

    private function winnerState(?array $winner, bool $isComplete): array
    {
        if (!$winner || empty($winner['partido_id']) || (int) ($winner['votos'] ?? 0) <= 0) {
            return [
                'partido_id' => null,
                'votos' => 0,
                'estado' => 'pendiente',
                'color' => '#9e9e9e',
            ];
        }

        if ((int) $winner['partido_id'] === self::JACHA_PARTIDO_ID) {
            return [
                'partido_id' => (int) $winner['partido_id'],
                'votos' => (int) $winner['votos'],
                'estado' => $isComplete ? 'ganado' : 'proceso',
                'color' => $isComplete ? '#0b7a28' : '#d4a017',
            ];
        }

        return [
            'partido_id' => (int) $winner['partido_id'],
            'votos' => (int) $winner['votos'],
            'estado' => 'perdido',
            'color' => '#2b5fb8',
        ];
    }

    private function buildRecintoMapPayload(Recinto $recinto): array
    {
        $mesas = collect($recinto->mesas ?? []);
        $mesasPayload = $mesas->map(fn ($mesa) => $this->buildMesaPayload($mesa))->values();
        $mesasConResultado = $mesasPayload->filter(fn ($mesa) => !empty($mesa['tiene_resultado']))->count();
        $mesasFaltantes = max(0, $mesasPayload->count() - $mesasConResultado);
        $isComplete = $mesasPayload->count() > 0 && $mesasFaltantes === 0;
        $detalles = $mesas
            ->flatMap(fn ($mesa) => collect($mesa->resultado?->detalles ?? []))
            ->values();

        $winners = [];
        foreach (self::CATEGORY_FIELDS as $category => $field) {
            $winner = $detalles
                ->groupBy('partido_id')
                ->map(function ($rows, $partidoId) use ($field) {
                    return [
                        'partido_id' => (int) $partidoId,
                        'votos' => (int) collect($rows)->sum($field),
                    ];
                })
                ->sortByDesc('votos')
                ->first();

            $winners[$category] = $this->winnerState($winner, $isComplete);
        }

        return [
            'id' => $recinto->id,
            'nombre' => $recinto->nombre,
            'lat' => (float) $recinto->latitud,
            'lng' => (float) $recinto->longitud,
            'mesas_total' => $mesasPayload->count(),
            'mesas_con_resultado' => $mesasConResultado,
            'mesas_faltantes' => $mesasFaltantes,
            'winners' => $winners,
            'mesas' => $mesasPayload,
        ];
    }

    private function buildMapPayload(array $scope)
    {
        return Recinto::query()
            ->where('departamento_id', $scope['departamento_id'])
            ->when($scope['provincia_id'], fn ($q) => $q->where('provincia_id', $scope['provincia_id']))
            ->when($scope['municipio_id'], fn ($q) => $q->where('municipio_id', $scope['municipio_id']))
            ->when($scope['localidad_id'], fn ($q) => $q->where('localidad_id', $scope['localidad_id']))
            ->when($scope['delegado_id'], function ($q) use ($scope) {
                $q->whereHas('mesas', fn ($mesaQuery) => $this->applyMesaScope($mesaQuery, $scope));
            })
            ->whereNotNull('latitud')
            ->whereNotNull('longitud')
            ->with([
                'mesas' => function ($q) use ($scope) {
                    $this->applyMesaScope($q, $scope)
                        ->select('id', 'recinto_id', 'numero_mesa', 'delegado_id', 'estado')
                        ->orderBy('numero_mesa')
                        ->with([
                            'delegado:id,name,username,celular,foto_personal,ci_anverso,ci_reverso',
                            'delegado.jefes:id,name,username,celular',
                            'delegado.jefes.supervisores:id,name,username,celular',
                            'resultado:id,mesa_id,registrado_por,total_votos,total_validos,total_blancos,total_nulos,observacion,foto1,foto2,foto3,foto4,foto5,foto6,foto7,foto8,foto9,foto10,updated_at',
                            'resultado.registradoPor:id,name,username',
                            'resultado.detalles:id,resultado_mesa_id,partido_id,votos_alcalde,votos_concejal,votos_gobernador,votos_asambleista_distrito,votos_asambleista_poblacion',
                            'resultado.detalles.partido:id,sigla,nombre,color,icono',
                        ]);
                },
            ])
            ->orderBy('nombre')
            ->get(['id', 'nombre', 'latitud', 'longitud'])
            ->map(fn ($recinto) => $this->buildRecintoMapPayload($recinto))
            ->values();
    }

    public function bootstrap(Request $request)
    {
        $scope = $this->buildScope($request);
        $summary = $this->buildSummaryPayload($scope);

        return response()->json([
            ...$summary,
            'options' => $this->buildOptionsPayload($scope),
            'mapa' => $this->buildMapPayload($scope),
            'scope' => $scope,
            'generated_at' => now()->toIso8601String(),
        ]);
    }

    public function index(Request $request)
    {
        return response()->json($this->bootstrap($request)->getData(true));
    }

    public function mapa(Request $request)
    {
        $scope = $this->buildScope($request);
        return response()->json($this->buildMapPayload($scope));
    }
}
