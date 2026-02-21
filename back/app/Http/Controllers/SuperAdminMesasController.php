<?php

namespace App\Http\Controllers;

use App\Services\SocketEmitter;
use App\Models\Mesa;
use App\Models\Partido;
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

    /**
     * GET /api/admin/mesas?recinto_id=&mesa_id=&asignado=&estado=&con_resultado=
     * Devuelve máximo 250 registros (front hace paginación local con QPagination).
     */
    public function index(Request $request)
    {
        $recintoId    = $request->get('recinto_id');
        $mesaId       = $request->get('mesa_id');
        $asignado     = $request->get('asignado', 'ALL');
        $estado       = $request->get('estado');
        $conResultado = $request->get('con_resultado', 'ALL');

        // NUEVO
        $all     = $request->boolean('all', false);          // all=1
        $page    = max(1, (int) $request->get('page', 1));
        $perPage = (int) $request->get('per_page', $this->MAX_ROWS);
        $perPage = max(10, min($perPage, 500));              // techo de seguridad

        $base = Mesa::query()
            ->select([
                'mesas.id',
                'mesas.recinto_id',
                'mesas.numero_mesa',
                'mesas.delegado_id',
                'mesas.estado',
            ])
            ->with([
                'recinto:id,nombre',
                'delegado:id,name,username',
                'resultado:id,mesa_id,aviso_antes,aviso_manana,aviso_mediodia,hora_apertura_mesa,aviso_tarde,etapa_1,etapa_2,total_votos,total_validos,total_blancos,total_nulos'
            ])
            ->whereHas('recinto', function ($qq) {
                $qq->whereNull('deleted_at')
                    ->where('departamento_id', 5)
                    ->where('provincia_id', 57)
                    ->where('municipio_id', 191);
            })
            ->when($recintoId, fn($qq) => $qq->where('mesas.recinto_id', $recintoId))
            ->when($mesaId, fn($qq) => $qq->where('mesas.id', $mesaId))
            ->when($estado, fn($qq) => $qq->where('mesas.estado', $estado))
            ->when($asignado !== 'ALL', function ($qq) use ($asignado) {
                if ($asignado === 'YES') $qq->whereNotNull('mesas.delegado_id');
                if ($asignado === 'NO')  $qq->whereNull('mesas.delegado_id');
            })
            ->when($conResultado !== 'ALL', function ($qq) use ($conResultado) {
                if ($conResultado === 'YES') $qq->whereHas('resultado');
                if ($conResultado === 'NO')  $qq->whereDoesntHave('resultado');
            })
            ->orderBy('mesas.numero_mesa');

        // ✅ si piden ALL => paginate real (para traer TODO por lotes)
        if ($all) {
            $pag = $base->paginate($perPage, ['*'], 'page', $page);

            $data = collect($pag->items())->map(function ($m) {
                return [
                    'id' => $m->id,
                    'recinto_id' => $m->recinto_id,
                    'recinto_nombre' => $m->recinto?->nombre,

                    'numero_mesa' => $m->numero_mesa,
                    'delegado_id' => $m->delegado_id,
                    'delegado' => $m->delegado ? [
                        'id' => $m->delegado->id,
                        'name' => $m->delegado->name,
                        'username' => $m->delegado->username,
                    ] : null,

                    'estado' => $m->estado,

                    'tiene_resultado' => (bool) $m->resultado,
                    'aviso_antes' => (bool) optional($m->resultado)->aviso_antes,
                    'aviso_manana' => (bool) optional($m->resultado)->aviso_manana,
                    'aviso_mediodia' => (bool) optional($m->resultado)->aviso_mediodia,
                    'hora_apertura_mesa' => optional($m->resultado)->hora_apertura_mesa,
                    'aviso_tarde' => null,
                    'etapa_1' => null,
                    'etapa_2' => null,

                    'total_votos' => (int) (optional($m->resultado)->total_votos ?? 0),
                    'total_validos' => (int) (optional($m->resultado)->total_validos ?? 0),
                    'total_blancos' => (int) (optional($m->resultado)->total_blancos ?? 0),
                    'total_nulos' => (int) (optional($m->resultado)->total_nulos ?? 0),
                ];
            })->values();

            return response()->json([
                'mode' => 'paginate',
                'total' => $pag->total(),
                'page' => $pag->currentPage(),
                'per_page' => $pag->perPage(),
                'last_page' => $pag->lastPage(),
                'data' => $data,
            ]);
        }

        // ✅ modo “rápido” actual: solo 250
        $total = (clone $base)->toBase()->getCountForPagination();
        $rows  = (clone $base)->limit($this->MAX_ROWS)->get();

        $data = $rows->map(function ($m) {
            return [
                'id' => $m->id,
                'recinto_id' => $m->recinto_id,
                'recinto_nombre' => $m->recinto?->nombre,
                'numero_mesa' => $m->numero_mesa,
                'delegado_id' => $m->delegado_id,
                'delegado' => $m->delegado ? [
                    'id' => $m->delegado->id,
                    'name' => $m->delegado->name,
                    'username' => $m->delegado->username,
                ] : null,
                'estado' => $m->estado,
                'tiene_resultado' => (bool) $m->resultado,
                'aviso_antes' => (bool) optional($m->resultado)->aviso_antes,
                'aviso_manana' => (bool) optional($m->resultado)->aviso_manana,
                'aviso_mediodia' => (bool) optional($m->resultado)->aviso_mediodia,
                'hora_apertura_mesa' => optional($m->resultado)->hora_apertura_mesa,
                'aviso_tarde' => null,
                'etapa_1' => null,
                'etapa_2' => null,
                'total_votos' => (int) (optional($m->resultado)->total_votos ?? 0),
                'total_validos' => (int) (optional($m->resultado)->total_validos ?? 0),
                'total_blancos' => (int) (optional($m->resultado)->total_blancos ?? 0),
                'total_nulos' => (int) (optional($m->resultado)->total_nulos ?? 0),
            ];
        })->values();

        return response()->json([
            'mode' => 'cap',
            'total' => $total,
            'returned' => $data->count(),
            'truncated' => $total > $this->MAX_ROWS,
            'max' => $this->MAX_ROWS,
            'data' => $data,
        ]);
    }


    // combos (recintos)
    public function recintosOptions()
    {
        return DB::table('recintos')
            ->select('id', 'nombre')
            ->whereNull('deleted_at')
            ->where('departamento_id', 5)
            ->where('provincia_id', 57)
            ->where('municipio_id', 191)
            ->orderBy('nombre')
            ->get();
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
        return User::query()
            ->select('id','name','username','role')
            ->where('role', 'Delegado de Mesa')
            ->orderBy('name')
            ->get();
    }

    // PUT /api/admin/mesas/{mesa}/delegado  body: { delegado_id, estado? }
    public function asignarDelegado(Request $request, Mesa $mesa)
    {
        $data = $request->validate([
            'delegado_id' => 'required|exists:users,id',
            'estado' => 'nullable|string|max:30',
        ]);

        $delegado = User::findOrFail($data['delegado_id']);
        if ($delegado->role !== 'Delegado de Mesa') {
            return response()->json(['message' => 'El usuario no es Delegado de Mesa'], 422);
        }

        $mesa->delegado_id = $data['delegado_id'];
        $mesa->estado = $data['estado'] ?? 'ASIGNADA';
        $mesa->save();

        return response()->json(['message' => 'Delegado asignado']);
    }

    // GET /api/admin/mesas/{mesa}/resultado
    public function resultado(Mesa $mesa)
    {
        $partidos = Partido::query()
            ->select('id','sigla','nombre','color','orden_municipal','orden_departamental','icono') // icono/logo
            ->orderBy('orden_municipal')
            ->orderBy('sigla')
            ->get();

        $res = ResultadoMesa::with(['detalles'])
            ->where('mesa_id', $mesa->id)
            ->first();

        $mesa->load(['recinto:id,nombre', 'delegado:id,name,username']);

        $mesaPayload = [
            'id' => $mesa->id,
            'numero_mesa' => $mesa->numero_mesa,
            'delegado_id' => $mesa->delegado_id,
            'recinto_nombre' => $mesa->recinto?->nombre,
            'delegado' => $mesa->delegado ? [
                'id' => $mesa->delegado->id,
                'name' => $mesa->delegado->name,
                'username' => $mesa->delegado->username,
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
            'latitud' => 'nullable|numeric',
            'longitud' => 'nullable|numeric',
            'blancos_gobernador' => 'nullable|integer|min:0',
            'nulos_gobernador' => 'nullable|integer|min:0',
            'blancos_asambleista_distrito' => 'nullable|integer|min:0',
            'nulos_asambleista_distrito' => 'nullable|integer|min:0',
            'blancos_asambleista_poblacion' => 'nullable|integer|min:0',
            'nulos_asambleista_poblacion' => 'nullable|integer|min:0',
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
            'votos_concejal',
            'votos_alcalde',
        ];

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
            if (!Partido::whereKey($row['partido_id'])->exists()) {
                return response()->json(['message' => 'Partido invalido'], 422);
            }
        }

        $socketPayload = DB::transaction(function () use ($request, $data, $mesa, $votos) {
            $res = ResultadoMesa::with('detalles')
                ->firstOrCreate(
                    ['mesa_id' => $mesa->id],
                    ['registrado_por' => $request->user()->id]
                );

            $res->registrado_por = $request->user()->id;

            foreach (['aviso_antes', 'aviso_manana', 'aviso_mediodia'] as $k) {
                if ($request->has($k)) {
                    $res->{$k} = (bool) $request->boolean($k);
                }
            }

            if ($request->has('hora_apertura_mesa')) {
                $res->hora_apertura_mesa = $data['hora_apertura_mesa'] ?: null;
            }

            // Campos fuera del flujo actual
            $res->aviso_tarde = null;
            $res->etapa_1 = null;
            $res->etapa_2 = null;

            if ($request->has('observacion')) $res->observacion = $data['observacion'] ?? null;
            if ($request->has('total_blancos')) $res->total_blancos = (int) ($data['total_blancos'] ?? 0);
            if ($request->has('total_nulos')) $res->total_nulos = (int) ($data['total_nulos'] ?? 0);
            if ($request->has('latitud')) $res->latitud = $data['latitud'] ?? null;
            if ($request->has('longitud')) $res->longitud = $data['longitud'] ?? null;
            if ($request->has('blancos_gobernador')) $res->blancos_gobernador = (int) ($data['blancos_gobernador'] ?? 0);
            if ($request->has('nulos_gobernador')) $res->nulos_gobernador = (int) ($data['nulos_gobernador'] ?? 0);
            if ($request->has('blancos_asambleista_distrito')) $res->blancos_asambleista_distrito = (int) ($data['blancos_asambleista_distrito'] ?? 0);
            if ($request->has('nulos_asambleista_distrito')) $res->nulos_asambleista_distrito = (int) ($data['nulos_asambleista_distrito'] ?? 0);
            if ($request->has('blancos_asambleista_poblacion')) $res->blancos_asambleista_poblacion = (int) ($data['blancos_asambleista_poblacion'] ?? 0);
            if ($request->has('nulos_asambleista_poblacion')) $res->nulos_asambleista_poblacion = (int) ($data['nulos_asambleista_poblacion'] ?? 0);
            if ($request->has('blancos_concejal')) $res->blancos_concejal = (int) ($data['blancos_concejal'] ?? 0);
            if ($request->has('nulos_concejal')) $res->nulos_concejal = (int) ($data['nulos_concejal'] ?? 0);
            if ($request->has('papeletas_no_utilizadas_concejal')) $res->papeletas_no_utilizadas_concejal = (int) ($data['papeletas_no_utilizadas_concejal'] ?? 0);
            if ($request->has('blancos_alcalde')) $res->blancos_alcalde = (int) ($data['blancos_alcalde'] ?? 0);
            if ($request->has('nulos_alcalde')) $res->nulos_alcalde = (int) ($data['nulos_alcalde'] ?? 0);
            if ($request->has('papeletas_no_utilizadas_alcalde')) $res->papeletas_no_utilizadas_alcalde = (int) ($data['papeletas_no_utilizadas_alcalde'] ?? 0);

            $dir = "resultados_mesa/mesa_{$mesa->id}";
            foreach (['foto1', 'foto2', 'foto3', 'foto4', 'foto5', 'foto6', 'foto7', 'foto8', 'foto9', 'foto10'] as $f) {
                if ($request->hasFile($f)) {
                    if (!empty($res->{$f})) {
                        Storage::disk('public')->delete($res->{$f});
                    }
                    $path = $request->file($f)->store($dir, 'public');
                    $res->{$f} = $path;
                }
            }

            $totalVotos = 0;
            foreach ($votos as $row) {
                $vvCon = (int) $row['votos_concejal'];
                $vvAlc = (int) $row['votos_alcalde'];

                $totalVotos += ($vvCon + $vvAlc);

                ResultadoMesaDetalle::updateOrCreate(
                    [
                        'resultado_mesa_id' => $res->id,
                        'partido_id' => $row['partido_id'],
                    ],
                    [
                        'votos_gobernador' => 0,
                        'votos_asambleista_distrito' => 0,
                        'votos_asambleista_poblacion' => 0,
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

            if ($res->aviso_antes || $res->aviso_manana || $res->aviso_mediodia) {
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
