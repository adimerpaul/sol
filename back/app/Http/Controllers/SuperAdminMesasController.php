<?php

namespace App\Http\Controllers;

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
                'resultado:id,mesa_id,aviso_antes,aviso_manana,aviso_mediodia,aviso_tarde,etapa_1,etapa_2,total_votos,total_validos,total_blancos,total_nulos'
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
                    'aviso_tarde' => (bool) optional($m->resultado)->aviso_tarde,
                    'etapa_1' => (bool) optional($m->resultado)->etapa_1,
                    'etapa_2' => (bool) optional($m->resultado)->etapa_2,

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
                'aviso_tarde' => (bool) optional($m->resultado)->aviso_tarde,
                'etapa_1' => (bool) optional($m->resultado)->etapa_1,
                'etapa_2' => (bool) optional($m->resultado)->etapa_2,
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
            ->select('id','sigla','nombre','color','orden','icono') // icono/logo
            ->orderBy('orden')
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
     * - foto1..foto4 (image)
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
            'aviso_tarde' => 'nullable|boolean',
            'etapa_1' => 'nullable|boolean',
            'etapa_2' => 'nullable|boolean',

            'total_validos' => 'nullable|integer|min:0',
            'total_blancos' => 'nullable|integer|min:0',
            'total_nulos' => 'nullable|integer|min:0',
            'observacion' => 'nullable|string',

            // viene como string JSON
            'votos' => 'required',

            // fotos
            'foto1' => 'nullable|image|max:2048',
            'foto2' => 'nullable|image|max:2048',
            'foto3' => 'nullable|image|max:2048',
            'foto4' => 'nullable|image|max:2048',
        ]);

        $votos = $request->input('votos');
        if (is_string($votos)) {
            $votos = json_decode($votos, true);
        }
        if (!is_array($votos)) {
            return response()->json(['message' => 'Formato inválido de votos'], 422);
        }

        // validar estructura de votos
        foreach ($votos as $row) {
            if (!isset($row['partido_id'], $row['votos'])) {
                return response()->json(['message' => 'Votos incompletos'], 422);
            }
            if (!Partido::whereKey($row['partido_id'])->exists()) {
                return response()->json(['message' => 'Partido inválido'], 422);
            }
            if ((int)$row['votos'] < 0) {
                return response()->json(['message' => 'Votos inválidos'], 422);
            }
        }

        return DB::transaction(function () use ($request, $data, $mesa, $votos) {

            $res = ResultadoMesa::with('detalles')
                ->firstOrCreate(
                    ['mesa_id' => $mesa->id],
                    ['registrado_por' => $request->user()->id]
                );

            $res->registrado_por = $request->user()->id;

            // booleans (si no vienen, mantenemos)
            foreach (['aviso_antes','aviso_manana','aviso_mediodia','aviso_tarde','etapa_1','etapa_2'] as $k) {
                if ($request->has($k)) $res->{$k} = (bool)$request->boolean($k);
            }

            if ($request->has('observacion'))   $res->observacion = $data['observacion'] ?? null;
            if ($request->has('total_blancos')) $res->total_blancos = (int)($data['total_blancos'] ?? 0);
            if ($request->has('total_nulos'))   $res->total_nulos   = (int)($data['total_nulos'] ?? 0);

            // guardar / reemplazar fotos si llegan
            $dir = "resultados_mesa/mesa_{$mesa->id}";
            foreach (['foto1','foto2','foto3','foto4'] as $f) {
                if ($request->hasFile($f)) {
                    // borra anterior
                    if (!empty($res->{$f})) {
                        Storage::disk('public')->delete($res->{$f});
                    }
                    $path = $request->file($f)->store($dir, 'public');
                    $res->{$f} = $path;
                }
            }

            // detalles votos
            $totalVotos = 0;
            foreach ($votos as $row) {
                $vv = (int)$row['votos'];
                $totalVotos += $vv;

                ResultadoMesaDetalle::updateOrCreate(
                    [
                        'resultado_mesa_id' => $res->id,
                        'partido_id' => $row['partido_id'],
                    ],
                    [
                        'votos' => $vv
                    ]
                );
            }

            $res->total_votos = $totalVotos;

            // validos: si viene explícito usamos, si no = suma votos partidos
            if ($request->has('total_validos')) {
                $res->total_validos = (int)($data['total_validos'] ?? 0);
            } else {
                $res->total_validos = $totalVotos;
            }

            $res->save();

            // estado automático
            if ($res->etapa_2) {
                $mesa->estado = 'FINALIZADA';
            } elseif ($res->etapa_1 || $res->aviso_antes || $res->aviso_manana || $res->aviso_mediodia || $res->aviso_tarde) {
                $mesa->estado = 'EN_PROCESO';
            } else {
                $mesa->estado = $mesa->delegado_id ? 'ASIGNADA' : 'PENDIENTE';
            }
            $mesa->save();

            return response()->json(['message' => 'Resultado guardado']);
        });
    }
}
