<?php

namespace App\Http\Controllers;

use Barryvdh\DomPDF\Facade\Pdf;
use App\Services\SocketEmitter;
use App\Models\Departamento;
use App\Models\Localidad;
use App\Models\Mesa;
use App\Models\Municipio;
use App\Models\Partido;
use App\Models\Provincia;
use App\Models\ResultadoMesa;
use App\Models\ResultadoMesaDetalle;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class SuperAdminMesasController extends Controller
{
    // límite duro
    private int $MAX_ROWS = 250;
    private int $PRINT_TIMEOUT_SECONDS = 600;
    private int $ORURO_PROVINCIA_ID = 57;
    private int $ORURO_MUNICIPIO_ID = 191;
    private int $ORURO_LOCALIDAD_ID = 1988;

    /**
     * GET /api/admin/mesas?recinto_id=&mesa_id=&asignado=&delegado_id=&estado=&con_resultado=
     * Devuelve máximo 250 registros (front hace paginación local con QPagination).
     */
    public function bootstrap(Request $request)
    {
        return response()->json([
            'geo' => $this->buildGeoOptionsPayload(),
            'delegados' => $this->buildDelegadosOptionsPayload(),
            'jefes_recinto' => $this->buildJefesRecintoOptionsPayload(),
            'supervisores' => $this->buildSupervisoresOptionsPayload(),
            'recintos' => $this->buildRecintosOptionsPayload($request),
            'mesas' => $this->buildMesasIndexPayload($request),
        ]);
    }

    public function index(Request $request)
    {
        return response()->json($this->buildMesasIndexPayload($request));
    }

    private function buildMesasIndexPayload(Request $request): array
    {
        $departamentoId = $request->get('departamento_id', 5);
        $provinciaId  = $request->get('provincia_id');
        $municipioId  = $request->get('municipio_id');
        $localidadId  = $request->get('localidad_id');
        $recintoId    = $request->get('recinto_id');
        $mesaId       = $request->get('mesa_id');
        $asignado     = $request->get('asignado', 'ALL');
        $delegadoId   = $request->get('delegado_id');
        $jefeRecintoId = $request->get('jefe_recinto_id');
        $supervisorId = $request->get('supervisor_id');
        $estado       = $request->get('estado');
        $conResultado = $request->get('con_resultado', 'ALL');
        $enMesa       = $request->get('en_mesa', 'ALL');

        // NUEVO
        $all     = $request->boolean('all', false);          // all=1
        $page    = max(1, (int) $request->get('page', 1));
        $perPage = (int) $request->get('per_page', $this->MAX_ROWS);
        $perPage = max(10, min($perPage, 500));              // techo de seguridad

        $scopeRecinto = function ($qq) use ($departamentoId, $provinciaId, $municipioId, $localidadId, $recintoId) {
            $qq->whereNull('deleted_at')
                ->where('departamento_id', $departamentoId)
                ->when($provinciaId, fn($q) => $q->where('provincia_id', $provinciaId))
                ->when($municipioId, fn($q) => $q->where('municipio_id', $municipioId))
                ->when($localidadId, fn($q) => $q->where('localidad_id', $localidadId))
                ->when($recintoId, fn($q) => $q->where('id', $recintoId));
        };

        $summaryBase = Mesa::query()
            ->whereHas('recinto', $scopeRecinto)
            ->when($jefeRecintoId, fn($qq) => $qq->whereHas('delegado.jefes', fn($q) => $q->where('users.id', $jefeRecintoId)))
            ->when($supervisorId, fn($qq) => $qq->whereHas('delegado.jefes', fn($q) => $q->whereHas('supervisores', fn($sq) => $sq->where('users.id', $supervisorId))))
            ->when($mesaId, fn($qq) => $qq->where('mesas.id', $mesaId))
            ->when($delegadoId, fn($qq) => $qq->where('mesas.delegado_id', $delegadoId))
            ->when($estado, fn($qq) => $qq->where('mesas.estado', $estado))
            ->when($asignado !== 'ALL', function ($qq) use ($asignado) {
                if ($asignado === 'YES') {
                    $qq->whereNotNull('mesas.delegado_id');
                }
                if ($asignado === 'NO') {
                    $qq->whereNull('mesas.delegado_id');
                }
            })
            ->when($conResultado !== 'ALL', function ($qq) use ($conResultado) {
                if ($conResultado === 'YES') {
                    $qq->whereHas('resultado');
                }
                if ($conResultado === 'NO') {
                    $qq->whereDoesntHave('resultado');
                }
            });

        $summaryPresenceBase = clone $summaryBase;
        $this->applyEnMesaFilter($summaryPresenceBase, 'YES');
        $this->applyEnMesaFilter($summaryBase, $enMesa);

        $summary = [
            'total' => (clone $summaryBase)->count(),
            'asignadas' => (clone $summaryBase)->whereNotNull('delegado_id')->count(),
            'sin_delegado' => (clone $summaryBase)->whereNull('delegado_id')->count(),
            'con_resultado' => (clone $summaryBase)->whereHas('resultado')->count(),
            'en_mesa' => (clone $summaryPresenceBase)->count(),
        ];

        $base = Mesa::query()
            ->select([
                'mesas.id',
                'mesas.departamento_id',
                'mesas.provincia_id',
                'mesas.municipio_id',
                'mesas.recinto_id',
                'mesas.numero_mesa',
                'mesas.habilitados',
                'mesas.delegado_id',
                'mesas.estado',
                'mesas.asistencia_capacitacion',
                'mesas.delegado_latitud',
                'mesas.delegado_longitud',
                'mesas.delegado_presente_at',
            ])
            ->with([
                'recinto:id,nombre,latitud,longitud',
                'departamento:id,nombre',
                'provincia:id,nombre',
                'municipio:id,nombre',
                'delegado:id,name,username,celular,ci,fecha_nacimiento',
                'resultado:id,mesa_id,aviso_antes,aviso_manana,aviso_mediodia,hora_apertura_mesa,aviso_tarde,etapa_1,etapa_2,total_votos,total_validos,total_blancos,total_nulos'
            ])
            ->whereHas('recinto', $scopeRecinto)
            ->when($jefeRecintoId, fn($qq) => $qq->whereHas('delegado.jefes', fn($q) => $q->where('users.id', $jefeRecintoId)))
            ->when($supervisorId, fn($qq) => $qq->whereHas('delegado.jefes', fn($q) => $q->whereHas('supervisores', fn($sq) => $sq->where('users.id', $supervisorId))))
            ->when($mesaId, fn($qq) => $qq->where('mesas.id', $mesaId))
            ->when($delegadoId, fn($qq) => $qq->where('mesas.delegado_id', $delegadoId))
            ->when($estado, fn($qq) => $qq->where('mesas.estado', $estado))
            ->when($asignado !== 'ALL', function ($qq) use ($asignado) {
                if ($asignado === 'YES') {
                    $qq->whereNotNull('mesas.delegado_id');
                }
                if ($asignado === 'NO') {
                    $qq->whereNull('mesas.delegado_id');
                }
            })
            ->when($conResultado !== 'ALL', function ($qq) use ($conResultado) {
                if ($conResultado === 'YES') {
                    $qq->whereHas('resultado');
                }
                if ($conResultado === 'NO') {
                    $qq->whereDoesntHave('resultado');
                }
            });

        $this->applyEnMesaFilter($base, $enMesa);

        $base = $base
            ->orderBy('mesas.numero_mesa');

        // ✅ si piden ALL => paginate real (para traer TODO por lotes)
        if ($all) {
            $pag = $base->paginate($perPage, ['*'], 'page', $page);

            $data = collect($pag->items())->map(function ($m) {
                return [
                    'id' => $m->id,
                    'departamento_id' => $m->departamento_id,
                    'departamento_nombre' => $m->departamento?->nombre,
                    'provincia_id' => $m->provincia_id,
                    'provincia_nombre' => $m->provincia?->nombre,
                    'municipio_id' => $m->municipio_id,
                    'municipio_nombre' => $m->municipio?->nombre,
                    'recinto_id' => $m->recinto_id,
                    'recinto_nombre' => $m->recinto?->nombre,
                    'recinto_latitud' => $m->recinto?->latitud !== null ? (string) $m->recinto?->latitud : null,
                    'recinto_longitud' => $m->recinto?->longitud !== null ? (string) $m->recinto?->longitud : null,

                    'numero_mesa' => $m->numero_mesa,
                    'habilitados' => (int) ($m->habilitados ?? 260),
                    'delegado_id' => $m->delegado_id,
                    'delegado' => $m->delegado ? [
                        'id' => $m->delegado->id,
                        'name' => $m->delegado->name,
                        'username' => $m->delegado->username,
                        'celular' => $m->delegado->celular,
                        'ci' => $m->delegado->ci,
                        'fecha_nacimiento' => $m->delegado->fecha_nacimiento,
                    ] : null,

                    'estado' => $m->estado,
                    'asistencia_capacitacion' => (bool) $m->asistencia_capacitacion,
                    'delegado_latitud' => $m->delegado_latitud !== null ? (string) $m->delegado_latitud : null,
                    'delegado_longitud' => $m->delegado_longitud !== null ? (string) $m->delegado_longitud : null,
                    'delegado_presente_at' => $m->delegado_presente_at?->toIso8601String(),

                    'tiene_resultado' => (bool) $m->resultado,
                    'aviso_antes' => (bool) optional($m->resultado)->aviso_antes,
                    'aviso_manana' => (bool) optional($m->resultado)->aviso_manana,
                    'aviso_mediodia' => (bool) optional($m->resultado)->aviso_mediodia,
                    'aviso_tarde' => (bool) optional($m->resultado)->aviso_tarde,
                    'hora_apertura_mesa' => optional($m->resultado)->hora_apertura_mesa,
                    'etapa_1' => null,
                    'etapa_2' => null,

                    'total_votos' => (int) (optional($m->resultado)->total_votos ?? 0),
                    'total_validos' => (int) (optional($m->resultado)->total_validos ?? 0),
                    'total_blancos' => (int) (optional($m->resultado)->total_blancos ?? 0),
                    'total_nulos' => (int) (optional($m->resultado)->total_nulos ?? 0),
                ];
            })->values();

            return [
                'mode' => 'paginate',
                'summary' => $summary,
                'total' => $pag->total(),
                'page' => $pag->currentPage(),
                'per_page' => $pag->perPage(),
                'last_page' => $pag->lastPage(),
                'data' => $data,
            ];
        }

        // ✅ modo “rápido” actual: solo 250
        $total = (clone $base)->toBase()->getCountForPagination();
        $rows  = (clone $base)->limit($this->MAX_ROWS)->get();

        $data = $rows->map(function ($m) {
            return [
                'id' => $m->id,
                'departamento_id' => $m->departamento_id,
                'departamento_nombre' => $m->departamento?->nombre,
                'provincia_id' => $m->provincia_id,
                'provincia_nombre' => $m->provincia?->nombre,
                'municipio_id' => $m->municipio_id,
                'municipio_nombre' => $m->municipio?->nombre,
                'recinto_id' => $m->recinto_id,
                'recinto_nombre' => $m->recinto?->nombre,
                'recinto_latitud' => $m->recinto?->latitud !== null ? (string) $m->recinto?->latitud : null,
                'recinto_longitud' => $m->recinto?->longitud !== null ? (string) $m->recinto?->longitud : null,
                'numero_mesa' => $m->numero_mesa,
                'habilitados' => (int) ($m->habilitados ?? 260),
                'delegado_id' => $m->delegado_id,
                    'delegado' => $m->delegado ? [
                        'id' => $m->delegado->id,
                        'name' => $m->delegado->name,
                        'username' => $m->delegado->username,
                        'celular' => $m->delegado->celular,
                        'ci' => $m->delegado->ci,
                        'fecha_nacimiento' => $m->delegado->fecha_nacimiento,
                    ] : null,
                'estado' => $m->estado,
                'asistencia_capacitacion' => (bool) $m->asistencia_capacitacion,
                'delegado_latitud' => $m->delegado_latitud !== null ? (string) $m->delegado_latitud : null,
                'delegado_longitud' => $m->delegado_longitud !== null ? (string) $m->delegado_longitud : null,
                'delegado_presente_at' => $m->delegado_presente_at?->toIso8601String(),
                'tiene_resultado' => (bool) $m->resultado,
                'aviso_antes' => (bool) optional($m->resultado)->aviso_antes,
                'aviso_manana' => (bool) optional($m->resultado)->aviso_manana,
                'aviso_mediodia' => (bool) optional($m->resultado)->aviso_mediodia,
                'aviso_tarde' => (bool) optional($m->resultado)->aviso_tarde,
                'hora_apertura_mesa' => optional($m->resultado)->hora_apertura_mesa,
                'etapa_1' => null,
                'etapa_2' => null,
                'total_votos' => (int) (optional($m->resultado)->total_votos ?? 0),
                'total_validos' => (int) (optional($m->resultado)->total_validos ?? 0),
                'total_blancos' => (int) (optional($m->resultado)->total_blancos ?? 0),
                'total_nulos' => (int) (optional($m->resultado)->total_nulos ?? 0),
            ];
        })->values();

        return [
            'mode' => 'cap',
            'summary' => $summary,
            'total' => $total,
            'returned' => $data->count(),
            'truncated' => $total > $this->MAX_ROWS,
            'max' => $this->MAX_ROWS,
            'data' => $data,
        ];
    }


    // combos (recintos)
    public function recintosOptions(Request $request)
    {
        return $this->buildRecintosOptionsPayload($request);
    }

    private function buildRecintosOptionsPayload(Request $request)
    {
        $departamentoId = $request->get('departamento_id', 5);
        $provinciaId = $request->get('provincia_id');
        $municipioId = $request->get('municipio_id');
        $localidadId = $request->get('localidad_id');
        $localidadId = $request->get('localidad_id');

        return DB::table('recintos as r')
            ->leftJoin('provincias as p', 'p.id', '=', 'r.provincia_id')
            ->leftJoin('municipios as m', 'm.id', '=', 'r.municipio_id')
            ->leftJoin('departamentos as d', 'd.id', '=', 'r.departamento_id')
            ->select(
                'r.id',
                'r.nombre',
                'r.departamento_id',
                'r.provincia_id',
                'r.municipio_id',
                'r.localidad_id',
                'd.nombre as departamento_nombre',
                'p.nombre as provincia_nombre',
                'm.nombre as municipio_nombre',
                'l.nombre as localidad_nombre'
            )
            ->leftJoin('localidades as l', 'l.id', '=', 'r.localidad_id')
            ->whereNull('r.deleted_at')
            ->where('r.departamento_id', $departamentoId)
            ->when($provinciaId, fn($qq) => $qq->where('r.provincia_id', $provinciaId))
            ->when($municipioId, fn($qq) => $qq->where('r.municipio_id', $municipioId))
            ->when($localidadId, fn($qq) => $qq->where('r.localidad_id', $localidadId))
            ->orderBy('p.nombre')
            ->orderBy('m.nombre')
            ->orderBy('l.nombre')
            ->orderBy('r.nombre')
            ->get();
    }

    public function printAsistenciaCapacitacion(Request $request)
    {
        $actor = $request->user();
        $asistio = $request->boolean('asistio', true);

        $rows = $this->buildMesasPrintBaseQuery($request)
            ->whereNotNull('mesas.delegado_id')
            ->where('mesas.asistencia_capacitacion', $asistio)
            ->get()
            ->map(function ($m) {
                return [
                    'mesa_numero' => $m->numero_mesa,
                    'recinto_nombre' => $m->recinto?->nombre,
                    'delegado_nombre' => $m->delegado?->name,
                    'delegado_username' => $m->delegado?->username,
                    'delegado_celular' => $m->delegado?->celular,
                    'estado' => $m->estado,
                ];
            })
            ->sortBy([
                ['delegado_nombre', 'asc'],
                ['delegado_username', 'asc'],
                ['mesa_numero', 'asc'],
            ])
            ->values();

        $pdf = Pdf::loadView('pdf.mesas_asistencia_capacitacion', [
            'title' => $asistio
                ? 'Asistencia a Capacitacion · Asistieron'
                : 'Asistencia a Capacitacion · No asistieron',
            'rows' => $rows,
            'generatedAt' => now()->format('d/m/Y H:i'),
            'generatedBy' => $actor->name ?? $actor->username ?? 'Sistema',
        ])->setPaper('a4', 'landscape');

        return $pdf->stream('mesas_asistencia_capacitacion_' . ($asistio ? 'si' : 'no') . '.pdf');
    }

    public function printActas(Request $request)
    {
        $actor = $request->user();

        $rows = $this->buildMesasPrintBaseQuery($request)
            ->get()
            ->map(function ($m) {
                return [
                    'mesa_numero' => $m->numero_mesa,
                    'recinto_nombre' => $m->recinto?->nombre,
                    'municipio_nombre' => $m->municipio?->nombre,
                    'provincia_nombre' => $m->provincia?->nombre,
                    'delegado_nombre' => $m->delegado?->name,
                    'delegado_username' => $m->delegado?->username,
                    'estado' => $m->estado,
                    'asistencia_capacitacion' => (bool) $m->asistencia_capacitacion,
                ];
            })
            ->sortBy([
                ['provincia_nombre', 'asc'],
                ['municipio_nombre', 'asc'],
                ['recinto_nombre', 'asc'],
                ['mesa_numero', 'asc'],
            ])
            ->values();

        $pdf = Pdf::loadView('pdf.mesas_actas_list', [
            'title' => 'Listado de Actas / Mesas',
            'rows' => $rows,
            'generatedAt' => now()->format('d/m/Y H:i'),
            'generatedBy' => $actor->name ?? $actor->username ?? 'Sistema',
        ])->setPaper('a4', 'landscape');

        return $pdf->stream('mesas_actas_list.pdf');
    }

    public function printEnMesa(Request $request)
    {
        @set_time_limit($this->PRINT_TIMEOUT_SECONDS);
        @ini_set('max_execution_time', (string) $this->PRINT_TIMEOUT_SECONDS);
        @ini_set('memory_limit', '1024M');
        @ignore_user_abort(true);
        $actor = $request->user();
        $enMesa = $request->get('en_mesa', 'YES');
        $isNoEnMesa = $enMesa === 'NO';

        $query = $this->buildMesasBaseQuery($request);

        if ($isNoEnMesa) {
            $query->whereNotNull('mesas.delegado_id');
        }

        $rows = $query
            ->orderBy('mesas.numero_mesa')
            ->get()
            ->map(fn($m) => $this->buildEnMesaReportRow($m))
            ->values();

        $pdf = Pdf::loadView('pdf.mesas_en_mesa', [
            'title' => $isNoEnMesa ? 'Reporte de delegados asignados no en mesa' : 'Reporte de delegados en mesa',
            'isNoEnMesa' => $isNoEnMesa,
            'rows' => $rows,
            'generatedAt' => now()->format('d/m/Y H:i'),
            'generatedBy' => $actor->name ?? $actor->username ?? 'Sistema',
        ])->setPaper('a4', 'landscape');

        return $pdf->stream($isNoEnMesa ? 'mesas_no_en_mesa.pdf' : 'mesas_en_mesa.pdf');
    }

    public function printMesaAbierta(Request $request)
    {
        @set_time_limit($this->PRINT_TIMEOUT_SECONDS);
        @ini_set('max_execution_time', (string) $this->PRINT_TIMEOUT_SECONDS);
        @ini_set('memory_limit', '1024M');
        @ignore_user_abort(true);

        $actor = $request->user();

        $rows = $this->buildMesasPrintBaseQuery($request)
            ->with([
                'resultado:id,mesa_id,aviso_manana,hora_apertura_mesa,registrado_por',
                'resultado.registradoPor:id,name,username',
            ])
            ->whereHas('resultado', function ($qq) {
                $qq->where('aviso_manana', true);
            })
            ->orderBy('mesas.numero_mesa')
            ->get()
            ->map(function ($m) {
                $jefes = collect($m->recinto?->jefe ?? []);
                $jefeNombre = $jefes->pluck('name')->filter()->implode(', ');
                $jefeCelular = $jefes->pluck('celular')->filter()->implode(', ');
                $resultado = $m->resultado;
                $isComplete = !empty($jefeNombre) && !empty($m->delegado?->name) && !empty($resultado?->hora_apertura_mesa);

                return [
                    'recinto_nombre' => $m->recinto?->nombre,
                    'mesa_numero' => $m->numero_mesa,
                    'jefe_nombre' => $jefeNombre ?: 'Sin jefe',
                    'jefe_celular' => $jefeCelular ?: 'Sin celular',
                    'delegado_nombre' => $m->delegado?->name ?: 'Sin delegado',
                    'delegado_celular' => $m->delegado?->celular ?: 'Sin celular',
                    'hora_apertura_mesa' => $resultado?->hora_apertura_mesa ?: 'Sin hora',
                    'registrado_por' => $resultado?->registradoPor?->name ?: $resultado?->registradoPor?->username ?: 'Sin registro',
                    'completo' => $isComplete,
                ];
            })
            ->values();

        $pdf = Pdf::loadView('pdf.mesas_apertura', [
            'title' => 'Reporte de mesa abierta',
            'rows' => $rows,
            'generatedAt' => now()->format('d/m/Y H:i:s'),
            'generatedBy' => $actor->name ?? $actor->username ?? 'Sistema',
        ])->setPaper('a4', 'landscape');

        return $pdf->stream('mesas_abiertas.pdf');
    }

    public function exportEnMesaCsv(Request $request)
    {
        @set_time_limit($this->PRINT_TIMEOUT_SECONDS);
        @ini_set('max_execution_time', (string) $this->PRINT_TIMEOUT_SECONDS);
        @ini_set('memory_limit', '1024M');
        @ignore_user_abort(true);

        $enMesa = $request->get('en_mesa', 'YES');
        $filename = $enMesa === 'NO' ? 'delegados_no_en_mesa.csv' : 'delegados_en_mesa.csv';

        return response()->streamDownload(function () use ($request) {
            $handle = fopen('php://output', 'w');

            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));
            fputcsv($handle, [
                'Mesa',
                'Recinto',
                'Municipio',
                'Provincia',
                'Delegado',
                'Usuario',
                'Celular',
                'Presencia',
                'Estado',
            ]);

            $this->buildMesasBaseQuery($request)
                ->orderBy('mesas.id')
                ->chunk(500, function ($mesas) use ($handle) {
                    foreach ($mesas as $mesa) {
                        $row = $this->buildEnMesaReportRow($mesa);

                        fputcsv($handle, [
                            $row['mesa_numero'],
                            $row['recinto_nombre'] ?? '-',
                            $row['municipio_nombre'] ?? '-',
                            $row['provincia_nombre'] ?? '-',
                            $row['delegado_nombre'] ?? 'SIN ASIGNAR',
                            $row['delegado_username'] ?? '-',
                            $row['delegado_celular'] ?? '-',
                            $row['presencia_at'] ?? 'No registrada',
                            $row['estado'] ?? '-',
                        ]);
                    }

                    fflush($handle);
                });

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function buildMesasBaseQuery(Request $request)
    {
        $departamentoId = $request->get('departamento_id', 5);
        $provinciaId = $request->get('provincia_id');
        $municipioId = $request->get('municipio_id');
        $localidadId = $request->get('localidad_id');
        $recintoId = $request->get('recinto_id');
        $mesaId = $request->get('mesa_id');
        $asignado = $request->get('asignado', 'ALL');
        $delegadoId = $request->get('delegado_id');
        $jefeRecintoId = $request->get('jefe_recinto_id');
        $supervisorId = $request->get('supervisor_id');
        $estado = $request->get('estado');
        $conResultado = $request->get('con_resultado', 'ALL');
        $enMesa = $request->get('en_mesa', 'ALL');

        $scopeRecinto = function ($qq) use ($departamentoId, $provinciaId, $municipioId, $localidadId, $recintoId) {
            $qq->whereNull('deleted_at')
                ->where('departamento_id', $departamentoId)
                ->when($provinciaId, fn($q) => $q->where('provincia_id', $provinciaId))
                ->when($municipioId, fn($q) => $q->where('municipio_id', $municipioId))
                ->when($localidadId, fn($q) => $q->where('localidad_id', $localidadId))
                ->when($recintoId, fn($q) => $q->where('id', $recintoId));
        };

        $query = Mesa::query()
            ->select([
                'mesas.id',
                'mesas.departamento_id',
                'mesas.provincia_id',
                'mesas.municipio_id',
                'mesas.recinto_id',
                'mesas.numero_mesa',
                'mesas.habilitados',
                'mesas.delegado_id',
                'mesas.estado',
                'mesas.asistencia_capacitacion',
                'mesas.delegado_latitud',
                'mesas.delegado_longitud',
                'mesas.delegado_presente_at',
            ])
            ->with([
                'recinto:id,nombre',
                'recinto.jefe:id,name,celular',
                'provincia:id,nombre',
                'municipio:id,nombre',
                'delegado:id,name,username,celular,ci,fecha_nacimiento',
                'resultado:id,mesa_id',
            ])
            ->whereHas('recinto', $scopeRecinto)
            ->when($jefeRecintoId, fn($qq) => $qq->whereHas('delegado.jefes', fn($q) => $q->where('users.id', $jefeRecintoId)))
            ->when($supervisorId, fn($qq) => $qq->whereHas('delegado.jefes', fn($q) => $q->whereHas('supervisores', fn($sq) => $sq->where('users.id', $supervisorId))))
            ->when($mesaId, fn($qq) => $qq->where('mesas.id', $mesaId))
            ->when($delegadoId, fn($qq) => $qq->where('mesas.delegado_id', $delegadoId))
            ->when($estado, fn($qq) => $qq->where('mesas.estado', $estado))
            ->when($asignado !== 'ALL', function ($qq) use ($asignado) {
                if ($asignado === 'YES') {
                    $qq->whereNotNull('mesas.delegado_id');
                }
                if ($asignado === 'NO') {
                    $qq->whereNull('mesas.delegado_id');
                }
            })
            ->when($conResultado !== 'ALL', function ($qq) use ($conResultado) {
                if ($conResultado === 'YES') {
                    $qq->whereHas('resultado');
                }
                if ($conResultado === 'NO') {
                    $qq->whereDoesntHave('resultado');
                }
            });

        $this->applyEnMesaFilter($query, $enMesa);

        return $query;
    }

    private function buildEnMesaReportRow(Mesa $m): array
    {
        return [
            'mesa_numero' => $m->numero_mesa,
            'recinto_nombre' => $m->recinto?->nombre,
            'municipio_nombre' => $m->municipio?->nombre,
            'provincia_nombre' => $m->provincia?->nombre,
            'delegado_nombre' => $m->delegado?->name,
            'delegado_username' => $m->delegado?->username,
            'delegado_celular' => $m->delegado?->celular,
            'presencia_at' => $m->delegado_presente_at?->format('d/m/Y H:i:s'),
            'estado' => $m->estado,
        ];
    }

    private function applyEnMesaFilter($query, $enMesa): void
    {
        if ($enMesa === 'YES') {
            $query->where(function ($qq) {
                $qq->whereNotNull('mesas.delegado_presente_at')
                    ->orWhereNotNull('mesas.delegado_latitud')
                    ->orWhereNotNull('mesas.delegado_longitud')
                    ->orWhereHas('resultado', function ($qr) {
                        $qr->where('aviso_antes', true);
                    });
            });
        }

        if ($enMesa === 'NO') {
            $query->whereNull('mesas.delegado_presente_at')
                ->whereNull('mesas.delegado_latitud')
                ->whereNull('mesas.delegado_longitud')
                ->whereDoesntHave('resultado', function ($qr) {
                    $qr->where('aviso_antes', true);
                });
        }
    }

    private function buildMesasPrintBaseQuery(Request $request)
    {
        $departamentoId = (int) $request->get('departamento_id', 5);
        $provinciaId = $request->get('provincia_id');
        $municipioId = $request->get('municipio_id');
        $localidadId = $request->get('localidad_id');
        $recintoId = $request->get('recinto_id');
        $mesaId = $request->get('mesa_id');
        $asignado = $request->get('asignado', 'ALL');
        $delegadoId = $request->get('delegado_id');
        $jefeRecintoId = $request->get('jefe_recinto_id');
        $supervisorId = $request->get('supervisor_id');
        $estado = $request->get('estado');
        $conResultado = $request->get('con_resultado', 'ALL');

        $scopeRecinto = function ($qq) use ($departamentoId, $provinciaId, $municipioId, $localidadId, $recintoId) {
            $qq->whereNull('deleted_at')
                ->where('departamento_id', $departamentoId)
                ->when($provinciaId, fn($q) => $q->where('provincia_id', $provinciaId))
                ->when($municipioId, fn($q) => $q->where('municipio_id', $municipioId))
                ->when($localidadId, fn($q) => $q->where('localidad_id', $localidadId))
                ->when($recintoId, fn($q) => $q->where('id', $recintoId));
        };

        return Mesa::query()
            ->select([
                'mesas.id',
                'mesas.departamento_id',
                'mesas.provincia_id',
                'mesas.municipio_id',
                'mesas.recinto_id',
                'mesas.numero_mesa',
                'mesas.habilitados',
                'mesas.delegado_id',
                'mesas.estado',
                'mesas.asistencia_capacitacion',
            ])
            ->with([
                'recinto:id,nombre',
                'recinto.jefe:id,name,celular',
                'provincia:id,nombre',
                'municipio:id,nombre',
                'delegado:id,name,username,celular,ci,fecha_nacimiento',
                'resultado:id,mesa_id',
            ])
            ->whereHas('recinto', $scopeRecinto)
            ->when($jefeRecintoId, fn($qq) => $qq->whereHas('recinto.jefe', fn($q) => $q->where('users.id', $jefeRecintoId)))
            ->when($supervisorId, fn($qq) => $qq->whereHas('recinto.jefe', fn($q) => $q->whereHas('supervisores', fn($sq) => $sq->where('users.id', $supervisorId))))
            ->when($mesaId, fn($qq) => $qq->where('mesas.id', $mesaId))
            ->when($delegadoId, fn($qq) => $qq->where('mesas.delegado_id', $delegadoId))
            ->when($estado, fn($qq) => $qq->where('mesas.estado', $estado))
            ->when($asignado !== 'ALL', function ($qq) use ($asignado) {
                if ($asignado === 'YES') {
                    $qq->whereNotNull('mesas.delegado_id');
                }
                if ($asignado === 'NO') {
                    $qq->whereNull('mesas.delegado_id');
                }
            })
            ->when($conResultado !== 'ALL', function ($qq) use ($conResultado) {
                if ($conResultado === 'YES') {
                    $qq->whereHas('resultado');
                }
                if ($conResultado === 'NO') {
                    $qq->whereDoesntHave('resultado');
                }
            });
    }

    // combos (mesas por recinto)
    // GET /api/admin/mesas/options/mesas?recinto_id=
    public function mesasOptions(Request $request)
    {
        $recintoId = $request->get('recinto_id');
        if (!$recintoId) return [];

        return Mesa::query()
            ->select('id', 'numero_mesa', 'estado')
            ->where('recinto_id', $recintoId)
            ->orderBy('numero_mesa')
            ->get();
    }

    // combos (delegados)
    public function delegadosOptions()
    {
        return $this->buildDelegadosOptionsPayload();
    }

    private function buildDelegadosOptionsPayload()
    {
        return User::query()
            ->select('id','name','username','role','ci')
            ->where('role', 'Delegado de Mesa')
            ->orderBy('name')
            ->get();
    }

    private function buildJefesRecintoOptionsPayload()
    {
        return User::query()
            ->select('id', 'name', 'username', 'celular')
            ->where('role', 'Jefe de Recinto')
            ->orderBy('name')
            ->get();
    }

    private function buildSupervisoresOptionsPayload()
    {
        return User::query()
            ->select('id', 'name', 'username', 'celular')
            ->where('role', 'Supervisor')
            ->orderBy('name')
            ->get();
    }

    private function buildGeoOptionsPayload(): array
    {
        return [
            'departamentos' => Departamento::query()
                ->select('id', 'pais_id', 'nombre')
                ->orderBy('nombre')
                ->get(),
            'provincias' => Provincia::query()
                ->select('id', 'departamento_id', 'nombre')
                ->orderBy('nombre')
                ->get(),
            'municipios' => Municipio::query()
                ->select('id', 'provincia_id', 'nombre')
                ->orderBy('nombre')
                ->get(),
            'localidades' => Localidad::query()
                ->select('id', 'municipio_id', 'nombre')
                ->orderBy('nombre')
                ->get(),
        ];
    }

    // PUT /api/admin/mesas/{mesa}/delegado  body: { delegado_id, estado? }
    public function asignarDelegado(Request $request, Mesa $mesa)
    {
        $data = $request->validate([
            'delegado_id' => 'nullable|exists:users,id',
            'estado' => 'nullable|string|max:30',
        ]);

        if (!empty($data['delegado_id'])) {
            $delegado = User::findOrFail($data['delegado_id']);
            if ($delegado->role !== 'Delegado de Mesa') {
                return response()->json(['message' => 'El usuario no es Delegado de Mesa'], 422);
            }

            if ((int) $mesa->delegado_id !== (int) $data['delegado_id']) {
                $mesa->delegado_latitud = null;
                $mesa->delegado_longitud = null;
                $mesa->delegado_presente_at = null;
            }

            $mesa->delegado_id = $data['delegado_id'];
            $mesa->estado = $data['estado'] ?? 'ASIGNADA';
            $mesa->save();

            return response()->json(['message' => 'Delegado asignado']);
        }

        $mesa->delegado_id = null;
        $mesa->estado = 'PENDIENTE';
        $mesa->delegado_latitud = null;
        $mesa->delegado_longitud = null;
        $mesa->delegado_presente_at = null;
        $mesa->save();

        return response()->json(['message' => 'Mesa liberada']);
    }

    public function asistenciaCapacitacion(Request $request, Mesa $mesa)
    {
        $data = $request->validate([
            'asistencia_capacitacion' => 'required|boolean',
        ]);

        $mesa->asistencia_capacitacion = (bool) $data['asistencia_capacitacion'];
        $mesa->save();

        return response()->json([
            'message' => 'Asistencia de capacitacion actualizada',
            'asistencia_capacitacion' => (bool) $mesa->asistencia_capacitacion,
        ]);
    }

    // GET /api/admin/mesas/{mesa}/resultado
    public function resultado(Mesa $mesa)
    {
        $partidos = $this->partidosPorMesa($mesa);

        $res = ResultadoMesa::with(['detalles'])
            ->where('mesa_id', $mesa->id)
            ->first();

        $mesa->load([
            'recinto:id,nombre',
            'delegado:id,name,username,celular,ci,fecha_nacimiento',
            'provincia:id,nombre',
            'municipio:id,nombre',
        ]);

        $mesaPayload = [
            'id' => $mesa->id,
            'numero_mesa' => $mesa->numero_mesa,
            'delegado_id' => $mesa->delegado_id,
            'recinto_nombre' => $mesa->recinto?->nombre,
            'provincia_nombre' => $mesa->provincia?->nombre,
            'municipio_nombre' => $mesa->municipio?->nombre,
            'delegado' => $mesa->delegado ? [
                'id' => $mesa->delegado->id,
                'name' => $mesa->delegado->name,
                'username' => $mesa->delegado->username,
                'celular' => $mesa->delegado->celular,
                'ci' => $mesa->delegado->ci,
                'fecha_nacimiento' => $mesa->delegado->fecha_nacimiento,
            ] : null,
        ];

        // fotos ya guardadas (si existen)
        if ($res) {
            $res->foto1_url = $res->foto1 ? Storage::url($res->foto1) : null;
            $res->foto2_url = $res->foto2 ? Storage::url($res->foto2) : null;
            $res->foto3_url = $res->foto3 ? Storage::url($res->foto3) : null;
            $res->foto4_url = $res->foto4 ? Storage::url($res->foto4) : null;
            $res->foto5_url = $res->foto5 ? Storage::url($res->foto5) : null;
            $res->foto6_url = $res->foto6 ? Storage::url($res->foto6) : null;
            $res->foto7_url = $res->foto7 ? Storage::url($res->foto7) : null;
            $res->foto8_url = $res->foto8 ? Storage::url($res->foto8) : null;
            $res->foto9_url = $res->foto9 ? Storage::url($res->foto9) : null;
            $res->foto10_url = $res->foto10 ? Storage::url($res->foto10) : null;
        }

        // icono como url (si es path en storage)
        $partidos = $partidos->map(function ($p) {
            $p->icono_url = $p->icono ? (str_starts_with($p->icono, 'http') ? $p->icono : Storage::url($p->icono)) : null;
            return $p;
        });

        return response()->json([
            'mesa' => $mesaPayload,
            'resultado' => $res,
            'partidos' => $partidos,
        ]);
    }

    /**
     * PUT /api/admin/mesas/{mesa}/resultado
     * Acepta multipart/form-data para fotos:
     * - foto1..foto10 (image)
     * - votos (JSON string)
     */
    public function guardarResultado(Request $request, Mesa $mesa)
    {
        if (!$mesa->delegado_id) {
            return response()->json(['message' => 'Esta mesa no tiene delegado asignado'], 422);
        }

        $data = $request->validate([
            'aviso_antes' => 'nullable|boolean',
            'aviso_manana' => 'nullable|boolean',
            'aviso_mediodia' => 'nullable|boolean',
            'hora_apertura_mesa' => 'nullable|string|max:5',
            'aviso_tarde' => 'nullable|boolean',
            'etapa_1' => 'nullable|boolean',
            'etapa_2' => 'nullable|boolean',

            'total_validos' => 'nullable|integer|min:0',
            'total_blancos' => 'nullable|integer|min:0',
            'total_nulos' => 'nullable|integer|min:0',
            'observacion' => 'nullable|string',
            'observacion_gobernador' => 'nullable|string',
            'observacion_asambleista_distrito' => 'nullable|string',
            'observacion_asambleista_poblacion' => 'nullable|string',
            'observacion_concejal' => 'nullable|string',
            'observacion_alcalde' => 'nullable|string',
            'latitud' => 'nullable|numeric',
            'longitud' => 'nullable|numeric',
            'blancos_gobernador' => 'nullable|integer|min:0',
            'nulos_gobernador' => 'nullable|integer|min:0',
            'papeletas_no_utilizadas_gobernador' => 'nullable|integer|min:0',
            'blancos_asambleista_distrito' => 'nullable|integer|min:0',
            'nulos_asambleista_distrito' => 'nullable|integer|min:0',
            'papeletas_no_utilizadas_asambleista_distrito' => 'nullable|integer|min:0',
            'blancos_asambleista_poblacion' => 'nullable|integer|min:0',
            'nulos_asambleista_poblacion' => 'nullable|integer|min:0',
            'papeletas_no_utilizadas_asambleista_poblacion' => 'nullable|integer|min:0',
            'blancos_concejal' => 'nullable|integer|min:0',
            'nulos_concejal' => 'nullable|integer|min:0',
            'papeletas_no_utilizadas_concejal' => 'nullable|integer|min:0',
            'blancos_alcalde' => 'nullable|integer|min:0',
            'nulos_alcalde' => 'nullable|integer|min:0',
            'papeletas_no_utilizadas_alcalde' => 'nullable|integer|min:0',

            'votos' => 'required',

            'foto1' => 'nullable|image|max:2048',
            'foto2' => 'nullable|image|max:2048',
            'foto3' => 'nullable|image|max:2048',
            'foto4' => 'nullable|image|max:2048',
            'foto5' => 'nullable|image|max:2048',
            'foto6' => 'nullable|image|max:2048',
            'foto7' => 'nullable|image|max:2048',
            'foto8' => 'nullable|image|max:2048',
            'foto9' => 'nullable|image|max:2048',
            'foto10' => 'nullable|image|max:2048',
            'clear_foto1' => 'nullable|boolean',
            'clear_foto2' => 'nullable|boolean',
            'clear_foto3' => 'nullable|boolean',
            'clear_foto4' => 'nullable|boolean',
            'clear_foto5' => 'nullable|boolean',
            'clear_foto6' => 'nullable|boolean',
            'clear_foto7' => 'nullable|boolean',
            'clear_foto8' => 'nullable|boolean',
            'clear_foto9' => 'nullable|boolean',
            'clear_foto10' => 'nullable|boolean',
        ]);

        $votos = $request->input('votos');
        if (is_string($votos)) {
            $votos = json_decode($votos, true);
        }
        if (!is_array($votos)) {
            return response()->json(['message' => 'Formato invalido de votos'], 422);
        }

        if (!$this->isHoraAperturaValida($data['hora_apertura_mesa'] ?? null)) {
            return response()->json([
                'message' => 'La hora de apertura debe estar entre 08:00 y 04:00',
            ], 422);
        }

        $votosFields = [
            'votos_gobernador',
            'votos_asambleista_distrito',
            'votos_asambleista_poblacion',
            'votos_concejal',
            'votos_alcalde',
        ];

        $partidosPermitidos = $this->partidosPorMesa($mesa)->pluck('id')->map(fn ($id) => (int) $id)->values();

        foreach ($votos as $row) {
            if (!isset($row['partido_id'])) {
                return response()->json(['message' => 'Votos incompletos'], 422);
            }
            foreach ($votosFields as $vf) {
                if (!isset($row[$vf])) {
                    return response()->json(['message' => 'Votos incompletos'], 422);
                }
                if ((int) $row[$vf] < 0) {
                    return response()->json(['message' => 'Votos invalidos'], 422);
                }
            }
            if (!$partidosPermitidos->contains((int) $row['partido_id'])) {
                return response()->json(['message' => 'Partido no habilitado para este municipio'], 422);
            }
        }

        $socketPayload = DB::transaction(function () use ($request, $data, $mesa, $votos) {
            $res = ResultadoMesa::with('detalles')
                ->firstOrCreate(
                    ['mesa_id' => $mesa->id],
                    ['registrado_por' => $request->user()->id]
                );

            $res->registrado_por = $request->user()->id;

            foreach (['aviso_antes', 'aviso_manana', 'aviso_mediodia', 'aviso_tarde'] as $k) {
                if ($request->has($k)) {
                    $res->{$k} = (bool) $request->boolean($k);
                }
            }

            if ($request->has('hora_apertura_mesa')) {
                $res->hora_apertura_mesa = $data['hora_apertura_mesa'] ?: null;
            }

            // Campos fuera del flujo actual
            $res->etapa_1 = null;
            $res->etapa_2 = null;

            if ($request->has('observacion')) $res->observacion = $data['observacion'] ?? null;
            if ($request->has('observacion_gobernador')) $res->observacion_gobernador = $data['observacion_gobernador'] ?? null;
            if ($request->has('observacion_asambleista_distrito')) $res->observacion_asambleista_distrito = $data['observacion_asambleista_distrito'] ?? null;
            if ($request->has('observacion_asambleista_poblacion')) $res->observacion_asambleista_poblacion = $data['observacion_asambleista_poblacion'] ?? null;
            if ($request->has('observacion_concejal')) $res->observacion_concejal = $data['observacion_concejal'] ?? null;
            if ($request->has('observacion_alcalde')) $res->observacion_alcalde = $data['observacion_alcalde'] ?? null;
            if ($request->has('total_blancos')) $res->total_blancos = (int) ($data['total_blancos'] ?? 0);
            if ($request->has('total_nulos')) $res->total_nulos = (int) ($data['total_nulos'] ?? 0);
            if ($request->has('latitud')) $res->latitud = $data['latitud'] ?? null;
            if ($request->has('longitud')) $res->longitud = $data['longitud'] ?? null;
            if ($request->has('blancos_gobernador')) $res->blancos_gobernador = (int) ($data['blancos_gobernador'] ?? 0);
            if ($request->has('nulos_gobernador')) $res->nulos_gobernador = (int) ($data['nulos_gobernador'] ?? 0);
            if ($request->has('papeletas_no_utilizadas_gobernador')) $res->papeletas_no_utilizadas_gobernador = (int) ($data['papeletas_no_utilizadas_gobernador'] ?? 0);
            if ($request->has('blancos_asambleista_distrito')) $res->blancos_asambleista_distrito = (int) ($data['blancos_asambleista_distrito'] ?? 0);
            if ($request->has('nulos_asambleista_distrito')) $res->nulos_asambleista_distrito = (int) ($data['nulos_asambleista_distrito'] ?? 0);
            if ($request->has('papeletas_no_utilizadas_asambleista_distrito')) $res->papeletas_no_utilizadas_asambleista_distrito = (int) ($data['papeletas_no_utilizadas_asambleista_distrito'] ?? 0);
            if ($request->has('blancos_asambleista_poblacion')) $res->blancos_asambleista_poblacion = (int) ($data['blancos_asambleista_poblacion'] ?? 0);
            if ($request->has('nulos_asambleista_poblacion')) $res->nulos_asambleista_poblacion = (int) ($data['nulos_asambleista_poblacion'] ?? 0);
            if ($request->has('papeletas_no_utilizadas_asambleista_poblacion')) $res->papeletas_no_utilizadas_asambleista_poblacion = (int) ($data['papeletas_no_utilizadas_asambleista_poblacion'] ?? 0);
            if ($request->has('blancos_concejal')) $res->blancos_concejal = (int) ($data['blancos_concejal'] ?? 0);
            if ($request->has('nulos_concejal')) $res->nulos_concejal = (int) ($data['nulos_concejal'] ?? 0);
            if ($request->has('papeletas_no_utilizadas_concejal')) $res->papeletas_no_utilizadas_concejal = (int) ($data['papeletas_no_utilizadas_concejal'] ?? 0);
            if ($request->has('blancos_alcalde')) $res->blancos_alcalde = (int) ($data['blancos_alcalde'] ?? 0);
            if ($request->has('nulos_alcalde')) $res->nulos_alcalde = (int) ($data['nulos_alcalde'] ?? 0);
            if ($request->has('papeletas_no_utilizadas_alcalde')) $res->papeletas_no_utilizadas_alcalde = (int) ($data['papeletas_no_utilizadas_alcalde'] ?? 0);

            $dir = "resultados_mesa/mesa_{$mesa->id}";
            foreach (['foto1', 'foto2', 'foto3', 'foto4', 'foto5', 'foto6', 'foto7', 'foto8', 'foto9', 'foto10'] as $f) {
                $clearField = 'clear_' . $f;
                if ($request->boolean($clearField)) {
                    if (!empty($res->{$f})) {
                        Storage::disk('public')->delete($res->{$f});
                    }
                    $res->{$f} = null;
                }

                if ($request->hasFile($f)) {
                    if (!empty($res->{$f})) {
                        Storage::disk('public')->delete($res->{$f});
                    }
                    $path = $request->file($f)->store($dir, 'public');
                    $res->{$f} = $path;
                }
            }

            $totalVotos = 0;
            $partidosEnviados = collect($votos)->pluck('partido_id')->map(fn ($id) => (int) $id)->values();

            ResultadoMesaDetalle::query()
                ->where('resultado_mesa_id', $res->id)
                ->whereNotIn('partido_id', $partidosEnviados)
                ->delete();

            foreach ($votos as $row) {
                $vvGob = (int) $row['votos_gobernador'];
                $vvAsd = (int) $row['votos_asambleista_distrito'];
                $vvAsp = (int) $row['votos_asambleista_poblacion'];
                $vvCon = (int) $row['votos_concejal'];
                $vvAlc = (int) $row['votos_alcalde'];

                $totalVotos += ($vvGob + $vvAsd + $vvAsp + $vvCon + $vvAlc);

                ResultadoMesaDetalle::updateOrCreate(
                    [
                        'resultado_mesa_id' => $res->id,
                        'partido_id' => $row['partido_id'],
                    ],
                    [
                        'votos_gobernador' => $vvGob,
                        'votos_asambleista_distrito' => $vvAsd,
                        'votos_asambleista_poblacion' => $vvAsp,
                        'votos_concejal' => $vvCon,
                        'votos_alcalde' => $vvAlc,
                    ]
                );
            }

            $res->total_votos = $totalVotos;
            if ($request->has('total_validos')) {
                $res->total_validos = (int) ($data['total_validos'] ?? 0);
            } else {
                $res->total_validos = $totalVotos;
            }

            $res->save();

            if ($res->aviso_antes || $res->aviso_manana || $res->aviso_mediodia || $res->aviso_tarde) {
                $mesa->estado = 'EN_PROCESO';
            } else {
                $mesa->estado = $mesa->delegado_id ? 'ASIGNADA' : 'PENDIENTE';
            }
            $mesa->save();

            $actor = $request->user();
            return [
                'title' => 'Nuevo dato registrado',
                'message' => trim(sprintf(
                    '%s registró resultado en Mesa %s%s',
                    $actor?->name ?: 'Usuario',
                    $mesa->numero_mesa,
                    $res->hora_apertura_mesa ? (' · Hora apertura: ' . $res->hora_apertura_mesa) : ''
                )),
                'kind' => 'resultado_admin',
                'mesa_id' => $mesa->id,
                'mesa_numero' => $mesa->numero_mesa,
                'estado' => $mesa->estado,
                'user_id' => $actor?->id,
                'user_name' => $actor?->name,
                'username' => $actor?->username,
                'aviso_antes' => (bool) $res->aviso_antes,
                'aviso_manana' => (bool) $res->aviso_manana,
                'aviso_mediodia' => (bool) $res->aviso_mediodia,
                'aviso_tarde' => (bool) $res->aviso_tarde,
                'hora_apertura_mesa' => $res->hora_apertura_mesa,
                'total_votos' => (int) ($res->total_votos ?? 0),
                'total_validos' => (int) ($res->total_validos ?? 0),
                'total_blancos' => (int) ($res->total_blancos ?? 0),
                'total_nulos' => (int) ($res->total_nulos ?? 0),
            ];
        });

        SocketEmitter::votacion($socketPayload);

        return response()->json(['message' => 'Resultado guardado']);
    }

    private function partidosPorMesa(Mesa $mesa)
    {
        $municipioId = $mesa->municipio_id ?: $mesa->recinto?->municipio_id;

        if (!$municipioId) {
            return Partido::query()
                ->select([
                    'id',
                    'sigla',
                    'nombre',
                    'color',
                    'orden_municipal',
                    'orden_departamental',
                    'icono',
                    DB::raw('1 as habilitado_gobernador'),
                    DB::raw('1 as habilitado_asambleista_poblacion'),
                    DB::raw('1 as habilitado_asambleista_distrito'),
                    DB::raw('1 as habilitado_alcalde'),
                    DB::raw('1 as habilitado_concejal'),
                ])
                ->orderByRaw('CASE WHEN orden_municipal IS NULL OR orden_municipal = 0 THEN 1 ELSE 0 END')
                ->orderBy('orden_municipal')
                ->orderBy('sigla')
                ->get();
        }

        $tieneConfig = DB::table('municipio_partido')
            ->where('municipio_id', $municipioId)
            ->exists();

        if (!$tieneConfig) {
            return Partido::query()
                ->select([
                    'id',
                    'sigla',
                    'nombre',
                    'color',
                    'orden_municipal',
                    'orden_departamental',
                    'icono',
                    DB::raw('1 as habilitado_gobernador'),
                    DB::raw('1 as habilitado_asambleista_poblacion'),
                    DB::raw('1 as habilitado_asambleista_distrito'),
                    DB::raw('1 as habilitado_alcalde'),
                    DB::raw('1 as habilitado_concejal'),
                ])
                ->orderByRaw('CASE WHEN orden_municipal IS NULL OR orden_municipal = 0 THEN 1 ELSE 0 END')
                ->orderBy('orden_municipal')
                ->orderBy('sigla')
                ->get();
        }

        return Partido::query()
            ->join('municipio_partido as mp', function ($join) use ($municipioId) {
                $join->on('mp.partido_id', '=', 'partidos.id')
                    ->where('mp.municipio_id', '=', $municipioId);
            })
            ->select([
                'partidos.id',
                'partidos.sigla',
                'partidos.nombre',
                'partidos.color',
                'partidos.orden_municipal',
                'partidos.orden_departamental',
                'partidos.icono',
                'mp.habilitado_gobernador',
                'mp.habilitado_asambleista_poblacion',
                'mp.habilitado_asambleista_distrito',
                'mp.habilitado_alcalde',
                'mp.habilitado_concejal',
            ])
            ->where(function ($qq) {
                $qq->where('mp.habilitado_gobernador', true)
                    ->orWhere('mp.habilitado_asambleista_poblacion', true)
                    ->orWhere('mp.habilitado_asambleista_distrito', true)
                    ->orWhere('mp.habilitado_alcalde', true)
                    ->orWhere('mp.habilitado_concejal', true);
            })
            ->orderByRaw('CASE WHEN partidos.orden_municipal IS NULL OR partidos.orden_municipal = 0 THEN 1 ELSE 0 END')
            ->orderBy('partidos.orden_municipal')
            ->orderBy('partidos.sigla')
            ->get();
    }

    private function isHoraAperturaValida(?string $hora): bool
    {
        if ($hora === null || $hora === '') {
            return true;
        }

        $dt = \DateTime::createFromFormat('H:i', $hora);
        if (!$dt || $dt->format('H:i') !== $hora) {
            return false;
        }

        $h = (int) $dt->format('G');
        return $h >= 8 || $h <= 4;
    }
}
