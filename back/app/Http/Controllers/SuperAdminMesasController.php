<?php

namespace App\Http\Controllers;

use App\Models\Mesa;
use App\Models\Partido;
use App\Models\ResultadoMesa;
use App\Models\ResultadoMesaDetalle;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SuperAdminMesasController extends Controller
{
    // GET /api/admin/mesas?recinto_id=&mesa=&asignado=&estado=&con_resultado=&page=&per_page=
    public function index(Request $request)
    {
        $recintoId     = $request->get('recinto_id');
        $mesa          = $request->get('mesa'); // numero_mesa
        $asignado      = $request->get('asignado'); // ALL | YES | NO
        $estado        = $request->get('estado'); // PENDIENTE|ASIGNADA|...
        $conResultado  = $request->get('con_resultado'); // ALL | YES | NO
        $perPage       = (int) $request->get('per_page', 25);

        $q = Mesa::query()
            ->with([
                'recinto:id,nombre',
                'delegado:id,name,username',
                'resultado:id,mesa_id,aviso_antes,aviso_manana,aviso_mediodia,aviso_tarde,etapa_1,etapa_2,total_votos,total_validos,total_blancos,total_nulos'
            ])
            ->when($recintoId, fn($qq) => $qq->where('recinto_id', $recintoId))
            ->when($mesa, fn($qq) => $qq->where('numero_mesa', $mesa))
            ->when($estado, fn($qq) => $qq->where('estado', $estado))
            ->when($asignado && $asignado !== 'ALL', function ($qq) use ($asignado) {
                if ($asignado === 'YES') $qq->whereNotNull('delegado_id');
                if ($asignado === 'NO')  $qq->whereNull('delegado_id');
            })
            ->when($conResultado && $conResultado !== 'ALL', function ($qq) use ($conResultado) {
                if ($conResultado === 'YES') $qq->whereHas('resultado');
                if ($conResultado === 'NO')  $qq->whereDoesntHave('resultado');
            })
            ->orderBy('recinto_id')
            ->orderBy('numero_mesa');

        return $q->paginate($perPage);
    }

    // combos (recintos)
    public function recintosOptions(Request $request)
    {
        return DB::table('recintos')
            ->select('id', 'nombre')
            ->whereNull('deleted_at')
            ->orderBy('nombre')
            ->get();
    }

    // combos (delegados)
    public function delegadosOptions(Request $request)
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

        // opcional: validar rol
        $delegado = User::findOrFail($data['delegado_id']);
        if ($delegado->role !== 'Delegado de Mesa') {
            return response()->json(['message' => 'El usuario no es Delegado de Mesa'], 422);
        }

        $mesa->delegado_id = $data['delegado_id'];
        $mesa->estado = $data['estado'] ?? 'ASIGNADA';
        $mesa->save();

        return response()->json(['message' => 'Delegado asignado', 'mesa' => $mesa->load('delegado')]);
    }

    // GET /api/admin/mesas/{mesa}/resultado  -> devuelve cabecera + detalles (o estructura vacía)
    public function resultado(Mesa $mesa)
    {
        $partidos = Partido::query()
            ->select('id','sigla','nombre','color','orden')
            ->orderBy('orden')
            ->orderBy('sigla')
            ->get();

        $res = ResultadoMesa::with(['detalles.partido:id,sigla,nombre,color'])
            ->where('mesa_id', $mesa->id)
            ->first();

        if (!$res) {
            // build vacío
            return response()->json([
                'mesa' => $mesa->load('recinto','delegado'),
                'resultado' => null,
                'partidos' => $partidos,
            ]);
        }

        return response()->json([
            'mesa' => $mesa->load('recinto','delegado'),
            'resultado' => $res,
            'partidos' => $partidos,
        ]);
    }

    // PUT /api/admin/mesas/{mesa}/resultado  (upsert)
    public function guardarResultado(Request $request, Mesa $mesa)
    {
        if (!$mesa->delegado_id) {
            return response()->json(['message' => 'Esta mesa no tiene delegado asignado'], 422);
        }

        $data = $request->validate([
            'aviso_antes' => 'boolean',
            'aviso_manana' => 'boolean',
            'aviso_mediodia' => 'boolean',
            'aviso_tarde' => 'boolean',
            'etapa_1' => 'boolean',
            'etapa_2' => 'boolean',

            'total_validos' => 'nullable|integer|min:0',
            'total_blancos' => 'nullable|integer|min:0',
            'total_nulos' => 'nullable|integer|min:0',
            'observacion' => 'nullable|string',

            'votos' => 'array', // [{partido_id, votos}]
            'votos.*.partido_id' => 'required|exists:partidos,id',
            'votos.*.votos' => 'required|integer|min:0',
        ]);

        return DB::transaction(function () use ($data, $mesa, $request) {

            $res = ResultadoMesa::with('detalles')
                ->firstOrCreate(
                    ['mesa_id' => $mesa->id],
                    ['registrado_por' => $request->user()->id]
                );

            $res->fill([
                'registrado_por' => $request->user()->id,
                'aviso_antes' => (bool)($data['aviso_antes'] ?? $res->aviso_antes),
                'aviso_manana' => (bool)($data['aviso_manana'] ?? $res->aviso_manana),
                'aviso_mediodia' => (bool)($data['aviso_mediodia'] ?? $res->aviso_mediodia),
                'aviso_tarde' => (bool)($data['aviso_tarde'] ?? $res->aviso_tarde),
                'etapa_1' => (bool)($data['etapa_1'] ?? $res->etapa_1),
                'etapa_2' => (bool)($data['etapa_2'] ?? $res->etapa_2),
                'observacion' => $data['observacion'] ?? $res->observacion,
            ]);

            // detalles votos
            $totalVotos = 0;
            foreach (($data['votos'] ?? []) as $row) {
                $totalVotos += (int)$row['votos'];

                ResultadoMesaDetalle::updateOrCreate(
                    [
                        'resultado_mesa_id' => $res->id,
                        'partido_id' => $row['partido_id'],
                    ],
                    [
                        'votos' => (int)$row['votos']
                    ]
                );
            }

            $res->total_votos = $totalVotos;

            $validos  = (int)($data['total_validos'] ?? $res->total_validos);
            $blancos  = (int)($data['total_blancos'] ?? $res->total_blancos);
            $nulos    = (int)($data['total_nulos'] ?? $res->total_nulos);

            // si no te mandan validos, lo puedes autocalcular como totalVotos
            if (!array_key_exists('total_validos', $data)) $validos = $totalVotos;

            $res->total_validos = $validos;
            $res->total_blancos = $blancos;
            $res->total_nulos   = $nulos;

            $res->save();

            // estado automático
            if ($res->etapa_2) {
                $mesa->estado = 'FINALIZADA';
            } else if ($res->etapa_1 || $res->aviso_antes || $res->aviso_manana || $res->aviso_mediodia || $res->aviso_tarde) {
                $mesa->estado = 'EN_PROCESO';
            } else {
                $mesa->estado = $mesa->delegado_id ? 'ASIGNADA' : 'PENDIENTE';
            }
            $mesa->save();

            return response()->json([
                'message' => 'Resultado guardado',
                'mesa' => $mesa->load('recinto','delegado','resultado'),
            ]);
        });
    }
}
