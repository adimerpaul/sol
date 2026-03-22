<?php

namespace App\Http\Controllers;

use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ReportesController extends Controller
{
    private int $MUNICIPIO_ID = 191;

    private array $JEFE_ROLES = [
        'Jefe de recinto',
        'Jefe de Recinto',
        'Administrador',
        'Delegado de mesa',
        'Delegado de Mesa',
    ];

    private array $DELEGADO_ROLES = [
        'Delegado de mesa',
        'Delegado de Mesa',
        'Administrador',
    ];

    private array $PERSONA_ROLES = [
        'Administrador',
        'Supervisor',
        'Jefe de recinto',
        'Jefe de Recinto',
        'Delegado de mesa',
        'Delegado de Mesa',
    ];

    public function bootstrap(Request $request)
    {
        $filters = $this->resolveFilters($request);

        return response()->json([
            'filters' => $this->buildOptionsPayload(),
            'data' => $this->buildDataPayload($filters),
        ]);
    }

    public function delegadosAsignados(Request $request)
    {
        return response()->json($this->buildDelegadosAsignadosRows($this->resolveFilters($request)));
    }

    public function jefesAsignados(Request $request)
    {
        return response()->json($this->buildJefesAsignadosRows($this->resolveFilters($request)));
    }

    public function delegadosLibres(Request $request)
    {
        return response()->json($this->buildDelegadosLibresRows($this->resolveFilters($request)));
    }

    public function jefesLibres(Request $request)
    {
        return response()->json($this->buildJefesLibresRows($this->resolveFilters($request)));
    }

    public function recintosSinJefe(Request $request)
    {
        return response()->json($this->buildRecintosSinJefeRows($this->resolveFilters($request)));
    }

    public function mesasLibres(Request $request)
    {
        return response()->json($this->buildMesasLibresRows($this->resolveFilters($request)));
    }

    public function exportDelegadosAsignados(Request $request)
    {
        return $this->streamCsv(
            $this->buildDelegadosAsignadosRows($this->resolveFilters($request)),
            'delegados_asignados.csv',
            [
                'Nro Recinto', 'Recinto', 'Numero de Mesa', 'Nombres', 'Apellido Paterno', 'Apellido Materno',
                'CI', 'Fecha Nacimiento', 'Celular', 'Bloque', 'Registrado Por', 'Registrado En Fecha',
            ],
            [
                'nro_recinto', 'recinto', 'numero_mesa', 'nombres', 'apellido_paterno', 'apellido_materno',
                'ci', 'fecha_nacimiento', 'celular', 'bloque', 'registrado_por', 'registrado_en_fecha',
            ]
        );
    }

    public function exportJefesAsignados(Request $request)
    {
        return $this->streamCsv(
            $this->buildJefesAsignadosRows($this->resolveFilters($request)),
            'jefes_asignados.csv',
            [
                'Nro Recinto', 'Recinto', 'Nombres', 'Apellido Paterno', 'Apellido Materno',
                'CI', 'Fecha Nacimiento', 'Celular', 'Bloque', 'Registrado Por', 'Registrado En Fecha', 'Tipo Jefe',
            ],
            [
                'nro_recinto', 'recinto', 'nombres', 'apellido_paterno', 'apellido_materno',
                'ci', 'fecha_nacimiento', 'celular', 'bloque', 'registrado_por', 'registrado_en_fecha', 'tipo_jefe',
            ]
        );
    }

    public function exportDelegadosLibres(Request $request)
    {
        return $this->streamCsv(
            $this->buildDelegadosLibresRows($this->resolveFilters($request)),
            'delegados_libres.csv',
            [
                'Nro Recinto', 'Recinto', 'Nombres', 'Apellido Paterno', 'Apellido Materno',
                'CI', 'Fecha Nacimiento', 'Celular', 'Bloque', 'Registrado Por', 'Registrado En Fecha', 'Estado',
            ],
            [
                'nro_recinto', 'recinto', 'nombres', 'apellido_paterno', 'apellido_materno',
                'ci', 'fecha_nacimiento', 'celular', 'bloque', 'registrado_por', 'registrado_en_fecha', 'estado',
            ]
        );
    }

    public function exportJefesLibres(Request $request)
    {
        return $this->streamCsv(
            $this->buildJefesLibresRows($this->resolveFilters($request)),
            'jefes_libres.csv',
            [
                'Nro Recinto', 'Recinto', 'Nombres', 'Apellido Paterno', 'Apellido Materno',
                'CI', 'Fecha Nacimiento', 'Celular', 'Bloque', 'Registrado Por', 'Registrado En Fecha', 'Estado',
            ],
            [
                'nro_recinto', 'recinto', 'nombres', 'apellido_paterno', 'apellido_materno',
                'ci', 'fecha_nacimiento', 'celular', 'bloque', 'registrado_por', 'registrado_en_fecha', 'estado',
            ]
        );
    }

    public function exportRecintosSinJefe(Request $request)
    {
        return $this->streamCsv(
            $this->buildRecintosSinJefeRows($this->resolveFilters($request)),
            'recintos_sin_jefe.csv',
            ['Nro Recinto', 'ID Recinto', 'Recinto'],
            ['nro_recinto', 'id_recinto', 'recinto']
        );
    }

    public function exportMesasLibres(Request $request)
    {
        return $this->streamCsv(
            $this->buildMesasLibresRows($this->resolveFilters($request)),
            'mesas_libres.csv',
            ['Nro Recinto', 'Recinto', 'Numero de Mesa', 'Estado'],
            ['nro_recinto', 'recinto', 'numero_mesa', 'estado']
        );
    }

    private function buildDataPayload(array $filters): array
    {
        return [
            'del_asignados' => $this->buildDelegadosAsignadosRows($filters),
            'jef_asignados' => $this->buildJefesAsignadosRows($filters),
            'del_libres' => $this->buildDelegadosLibresRows($filters),
            'jef_libres' => $this->buildJefesLibresRows($filters),
            'rec_sin_jefe' => $this->buildRecintosSinJefeRows($filters),
            'mesas_libres' => $this->buildMesasLibresRows($filters),
        ];
    }

    private function buildDelegadosAsignadosRows(array $filters): array
    {
        return $this->delegadosAsignadosQuery($filters)->get()->map(fn ($row) => (array) $row)->all();
    }

    private function buildJefesAsignadosRows(array $filters): array
    {
        return $this->jefesAsignadosQuery($filters)->get()->map(fn ($row) => (array) $row)->all();
    }

    private function buildDelegadosLibresRows(array $filters): array
    {
        return $this->delegadosLibresQuery($filters)->get()->map(fn ($row) => (array) $row)->all();
    }

    private function buildJefesLibresRows(array $filters): array
    {
        return $this->jefesLibresQuery($filters)->get()->map(fn ($row) => (array) $row)->all();
    }

    private function buildRecintosSinJefeRows(array $filters): array
    {
        return $this->recintosSinJefeQuery($filters)->get()->map(fn ($row) => (array) $row)->all();
    }

    private function buildMesasLibresRows(array $filters): array
    {
        return $this->mesasLibresQuery($filters)->get()->map(fn ($row) => (array) $row)->all();
    }

    private function delegadosAsignadosQuery(array $filters): Builder
    {
        $query = DB::table('mesas as m')
            ->join('recintos as r', 'm.recinto_id', '=', 'r.id')
            ->join('users as u', 'm.delegado_id', '=', 'u.id')
            ->leftJoin('users as cb', 'u.created_by', '=', 'cb.id')
            ->selectRaw('
                DENSE_RANK() OVER (ORDER BY r.nombre ASC) AS nro_recinto,
                r.nombre AS recinto,
                m.numero_mesa AS numero_mesa,
                u.id AS user_id,
                m.delegado_id AS delegado_id,
                u.nombres AS nombres,
                u.apellido_paterno AS apellido_paterno,
                u.apellido_materno AS apellido_materno,
                u.ci AS ci,
                u.fecha_nacimiento AS fecha_nacimiento,
                u.celular AS celular,
                u.bloque AS bloque,
                cb.name AS registrado_por,
                u.created_at AS registrado_en_fecha
            ')
            ->whereNull('u.deleted_at')
            ->whereNull('m.deleted_at')
            ->where('r.municipio_id', $this->MUNICIPIO_ID);

        $this->applyRecintoFilter($query, $filters);
        $this->applyPersonaFilter($query, $filters, 'u.id');
        $this->applyDelegadoFilter($query, $filters, 'u.id');
        $this->applyJefeDelegadoAssignmentFilter($query, $filters, 'u.id');

        return $query
            ->orderBy('r.nombre')
            ->orderBy('m.numero_mesa')
            ->orderBy('u.apellido_paterno');
    }

    private function jefesAsignadosQuery(array $filters): Builder
    {
        $query = DB::table('recinto_jefe as rj')
            ->join('recintos as r', 'rj.recinto_id', '=', 'r.id')
            ->join('users as u', 'rj.jefe_id', '=', 'u.id')
            ->leftJoin('users as cb', 'u.created_by', '=', 'cb.id')
            ->selectRaw("
                DENSE_RANK() OVER (ORDER BY r.nombre ASC) AS nro_recinto,
                r.nombre AS recinto,
                u.id AS user_id,
                rj.jefe_id AS jefe_id,
                u.nombres AS nombres,
                u.apellido_paterno AS apellido_paterno,
                u.apellido_materno AS apellido_materno,
                u.ci AS ci,
                u.fecha_nacimiento AS fecha_nacimiento,
                u.celular AS celular,
                u.bloque AS bloque,
                cb.name AS registrado_por,
                u.created_at AS registrado_en_fecha,
                CASE
                    WHEN rj.super_jefe = 1 THEN 'SUPER JEFE'
                    ELSE 'JEFE'
                END AS tipo_jefe
            ")
            ->whereNull('u.deleted_at')
            ->whereNull('rj.deleted_at')
            ->where('r.municipio_id', $this->MUNICIPIO_ID);

        $this->applyRecintoFilter($query, $filters);
        $this->applyPersonaFilter($query, $filters, 'u.id');
        $this->applyJefeFilter($query, $filters, 'u.id');
        $this->applyDelegadoUnderJefeFilter($query, $filters, 'u.id');

        return $query
            ->orderBy('r.nombre')
            ->orderBy('u.apellido_paterno');
    }

    private function delegadosLibresQuery(array $filters): Builder
    {
        $query = DB::table('users as u')
            ->leftJoin('recintos as r', 'u.recinto_id', '=', 'r.id')
            ->leftJoin('users as cb', 'u.created_by', '=', 'cb.id')
            ->leftJoin('mesas as m', function ($join) {
                $join->on('u.id', '=', 'm.delegado_id')
                    ->whereNull('m.deleted_at');
            })
            ->selectRaw("
                DENSE_RANK() OVER (ORDER BY r.nombre ASC) AS nro_recinto,
                r.nombre AS recinto,
                u.id AS user_id,
                u.nombres AS nombres,
                u.apellido_paterno AS apellido_paterno,
                u.apellido_materno AS apellido_materno,
                u.ci AS ci,
                u.fecha_nacimiento AS fecha_nacimiento,
                u.celular AS celular,
                u.bloque AS bloque,
                cb.name AS registrado_por,
                u.created_at AS registrado_en_fecha,
                'DELEGADO LIBRE' AS estado
            ")
            ->whereIn('u.role', $this->DELEGADO_ROLES)
            ->whereNull('u.deleted_at')
            ->whereNull('m.id')
            ->where('r.municipio_id', $this->MUNICIPIO_ID);

        $this->applyRecintoFilter($query, $filters, 'u.recinto_id');
        $this->applyPersonaFilter($query, $filters, 'u.id');
        $this->applyDelegadoFilter($query, $filters, 'u.id');
        $this->applyJefeDelegadoAssignmentFilter($query, $filters, 'u.id');

        return $query
            ->orderBy('r.nombre')
            ->orderBy('u.apellido_paterno');
    }

    private function jefesLibresQuery(array $filters): Builder
    {
        $query = DB::table('users as u')
            ->leftJoin('recintos as r', 'u.recinto_id', '=', 'r.id')
            ->leftJoin('users as cb', 'u.created_by', '=', 'cb.id')
            ->leftJoin('recinto_jefe as rj', function ($join) {
                $join->on('u.id', '=', 'rj.jefe_id')
                    ->whereNull('rj.deleted_at');
            })
            ->selectRaw("
                DENSE_RANK() OVER (ORDER BY r.nombre ASC) AS nro_recinto,
                r.nombre AS recinto,
                u.id AS user_id,
                u.nombres AS nombres,
                u.apellido_paterno AS apellido_paterno,
                u.apellido_materno AS apellido_materno,
                u.ci AS ci,
                u.fecha_nacimiento AS fecha_nacimiento,
                u.celular AS celular,
                u.bloque AS bloque,
                cb.name AS registrado_por,
                u.created_at AS registrado_en_fecha,
                'JEFE LIBRE' AS estado
            ")
            ->whereIn('u.role', $this->JEFE_ROLES)
            ->whereNull('u.deleted_at')
            ->whereNull('rj.id')
            ->where('r.municipio_id', $this->MUNICIPIO_ID);

        $this->applyRecintoFilter($query, $filters, 'u.recinto_id');
        $this->applyPersonaFilter($query, $filters, 'u.id');
        $this->applyJefeFilter($query, $filters, 'u.id');
        $this->applyDelegadoUnderJefeFilter($query, $filters, 'u.id');

        return $query
            ->orderBy('r.nombre')
            ->orderBy('u.apellido_paterno');
    }

    private function recintosSinJefeQuery(array $filters): Builder
    {
        $query = DB::table('recintos as r')
            ->leftJoin('recinto_jefe as rj', function ($join) {
                $join->on('r.id', '=', 'rj.recinto_id')
                    ->whereNull('rj.deleted_at');
            })
            ->selectRaw('
                DENSE_RANK() OVER (ORDER BY r.nombre ASC) AS nro_recinto,
                r.id AS id_recinto,
                r.nombre AS recinto
            ')
            ->whereNull('rj.id')
            ->where('r.municipio_id', $this->MUNICIPIO_ID);

        $this->applyRecintoFilter($query, $filters, 'r.id');

        return $query->orderBy('r.nombre');
    }

    private function mesasLibresQuery(array $filters): Builder
    {
        $query = DB::table('mesas as m')
            ->join('recintos as r', 'm.recinto_id', '=', 'r.id')
            ->selectRaw("
                DENSE_RANK() OVER (ORDER BY r.nombre ASC) AS nro_recinto,
                CONCAT(r.id, '-', m.numero_mesa) AS mesa_key,
                r.nombre AS recinto,
                m.numero_mesa AS numero_mesa,
                'MESA LIBRE' AS estado
            ")
            ->whereNull('m.deleted_at')
            ->whereNull('m.delegado_id')
            ->where('r.municipio_id', $this->MUNICIPIO_ID);

        $this->applyRecintoFilter($query, $filters);
        $this->applyJefeRecintoFilter($query, $filters, 'r.id');

        if (!empty($filters['delegado_mesa_id'])) {
            $query->whereRaw('1 = 0');
        }

        return $query
            ->orderBy('r.nombre')
            ->orderBy('m.numero_mesa');
    }

    private function resolveFilters(Request $request): array
    {
        return [
            'recinto_id' => $request->filled('recinto_id') ? (int) $request->input('recinto_id') : null,
            'persona_id' => $request->filled('persona_id') ? (int) $request->input('persona_id') : null,
            'jefe_recinto_id' => $request->filled('jefe_recinto_id') ? (int) $request->input('jefe_recinto_id') : null,
            'delegado_mesa_id' => $request->filled('delegado_mesa_id') ? (int) $request->input('delegado_mesa_id') : null,
        ];
    }

    private function applyRecintoFilter(Builder $query, array $filters, string $column = 'r.id'): void
    {
        if (!empty($filters['recinto_id'])) {
            $query->where($column, $filters['recinto_id']);
        }
    }

    private function applyPersonaFilter(Builder $query, array $filters, string $column): void
    {
        if (!empty($filters['persona_id'])) {
            $query->where($column, $filters['persona_id']);
        }
    }

    private function applyJefeFilter(Builder $query, array $filters, string $column): void
    {
        if (!empty($filters['jefe_recinto_id'])) {
            $query->where($column, $filters['jefe_recinto_id']);
        }
    }

    private function applyDelegadoFilter(Builder $query, array $filters, string $column): void
    {
        if (!empty($filters['delegado_mesa_id'])) {
            $query->where($column, $filters['delegado_mesa_id']);
        }
    }

    private function applyJefeDelegadoAssignmentFilter(Builder $query, array $filters, string $delegadoColumn): void
    {
        if (empty($filters['jefe_recinto_id'])) {
            return;
        }

        $query->whereExists(function ($sub) use ($filters, $delegadoColumn) {
            $sub->select(DB::raw(1))
                ->from('jefe_delegado as jd')
                ->whereColumn('jd.delegado_id', $delegadoColumn)
                ->where('jd.jefe_id', $filters['jefe_recinto_id'])
                ->whereNull('jd.deleted_at');
        });
    }

    private function applyDelegadoUnderJefeFilter(Builder $query, array $filters, string $jefeColumn): void
    {
        if (empty($filters['delegado_mesa_id'])) {
            return;
        }

        $query->whereExists(function ($sub) use ($filters, $jefeColumn) {
            $sub->select(DB::raw(1))
                ->from('jefe_delegado as jd')
                ->whereColumn('jd.jefe_id', $jefeColumn)
                ->where('jd.delegado_id', $filters['delegado_mesa_id'])
                ->whereNull('jd.deleted_at');
        });
    }

    private function applyJefeRecintoFilter(Builder $query, array $filters, string $recintoColumn): void
    {
        if (empty($filters['jefe_recinto_id'])) {
            return;
        }

        $query->whereExists(function ($sub) use ($filters, $recintoColumn) {
            $sub->select(DB::raw(1))
                ->from('recinto_jefe as rj')
                ->whereColumn('rj.recinto_id', $recintoColumn)
                ->where('rj.jefe_id', $filters['jefe_recinto_id'])
                ->whereNull('rj.deleted_at');
        });
    }

    private function buildOptionsPayload(): array
    {
        return [
            'recintos' => $this->buildRecintosOptions(),
            'personas' => $this->buildPersonasOptions(),
            'jefes' => $this->buildJefesOptions(),
            'delegados' => $this->buildDelegadosOptions(),
        ];
    }

    private function buildRecintosOptions(): array
    {
        return DB::table('recintos as r')
            ->select('r.id', 'r.nombre')
            ->where('r.municipio_id', $this->MUNICIPIO_ID)
            ->orderBy('r.nombre')
            ->get()
            ->map(fn ($row) => [
                'id' => (int) $row->id,
                'label' => $row->nombre,
            ])
            ->all();
    }

    private function buildPersonasOptions(): array
    {
        $rows = DB::table('users as u')
            ->select(
                'u.id',
                'u.nombres',
                'u.apellido_paterno',
                'u.apellido_materno',
                'u.username',
                'u.role'
            )
            ->whereNull('u.deleted_at')
            ->where(function ($query) {
                $query->whereIn('u.role', $this->PERSONA_ROLES)
                    ->orWhereExists(function ($sub) {
                        $sub->select(DB::raw(1))
                            ->from('mesas as m')
                            ->join('recintos as r', 'm.recinto_id', '=', 'r.id')
                            ->whereColumn('m.delegado_id', 'u.id')
                            ->whereNull('m.deleted_at')
                            ->where('r.municipio_id', $this->MUNICIPIO_ID);
                    })
                    ->orWhereExists(function ($sub) {
                        $sub->select(DB::raw(1))
                            ->from('recinto_jefe as rj')
                            ->join('recintos as r', 'rj.recinto_id', '=', 'r.id')
                            ->whereColumn('rj.jefe_id', 'u.id')
                            ->whereNull('rj.deleted_at')
                            ->where('r.municipio_id', $this->MUNICIPIO_ID);
                    });
            })
            ->orderBy('u.nombres')
            ->orderBy('u.apellido_paterno')
            ->get();

        return $this->mapUsersToOptions($rows);
    }

    private function buildJefesOptions(): array
    {
        $rows = DB::table('users as u')
            ->select(
                'u.id',
                'u.nombres',
                'u.apellido_paterno',
                'u.apellido_materno',
                'u.username',
                'u.role'
            )
            ->whereNull('u.deleted_at')
            ->where(function ($query) {
                $query->whereIn('u.role', $this->JEFE_ROLES)
                    ->orWhereExists(function ($sub) {
                        $sub->select(DB::raw(1))
                            ->from('recinto_jefe as rj')
                            ->join('recintos as r', 'rj.recinto_id', '=', 'r.id')
                            ->whereColumn('rj.jefe_id', 'u.id')
                            ->whereNull('rj.deleted_at')
                            ->where('r.municipio_id', $this->MUNICIPIO_ID);
                    });
            })
            ->orderBy('u.nombres')
            ->orderBy('u.apellido_paterno')
            ->get();

        return $this->mapUsersToOptions($rows);
    }

    private function buildDelegadosOptions(): array
    {
        $rows = DB::table('users as u')
            ->select(
                'u.id',
                'u.nombres',
                'u.apellido_paterno',
                'u.apellido_materno',
                'u.username',
                'u.role'
            )
            ->whereNull('u.deleted_at')
            ->where(function ($query) {
                $query->whereIn('u.role', $this->DELEGADO_ROLES)
                    ->orWhereExists(function ($sub) {
                        $sub->select(DB::raw(1))
                            ->from('mesas as m')
                            ->join('recintos as r', 'm.recinto_id', '=', 'r.id')
                            ->whereColumn('m.delegado_id', 'u.id')
                            ->whereNull('m.deleted_at')
                            ->where('r.municipio_id', $this->MUNICIPIO_ID);
                    });
            })
            ->orderBy('u.nombres')
            ->orderBy('u.apellido_paterno')
            ->get();

        return $this->mapUsersToOptions($rows);
    }

    private function mapUsersToOptions(Collection $rows): array
    {
        return $rows
            ->unique('id')
            ->map(function ($row) {
                $name = trim(implode(' ', array_filter([
                    $row->nombres,
                    $row->apellido_paterno,
                    $row->apellido_materno,
                ])));

                return [
                    'id' => (int) $row->id,
                    'label' => trim($name . ' (' . ($row->username ?: 'sin usuario') . ') · ' . ($row->role ?: 'Sin rol')),
                ];
            })
            ->values()
            ->all();
    }

    private function streamCsv(array $rows, string $filename, array $headers, array $keys)
    {
        $callback = function () use ($rows, $headers, $keys) {
            $handle = fopen('php://output', 'w');
            fputs($handle, "\xEF\xBB\xBF");
            fputcsv($handle, $headers, ';');

            foreach ($rows as $row) {
                $line = [];
                foreach ($keys as $key) {
                    $line[] = $row[$key] ?? '';
                }
                fputcsv($handle, $line, ';');
            }

            fclose($handle);
        };

        return response()->streamDownload($callback, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
