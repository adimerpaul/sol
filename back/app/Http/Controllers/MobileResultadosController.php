<?php

namespace App\Http\Controllers;

use App\Models\Mesa;
use App\Models\ResultadoMesa;
use App\Models\ResultadoMesaDetalle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MobileResultadosController extends Controller
{
    private array $asistenciaFields = [
        'aviso_antes',
        'aviso_manana',
        'aviso_mediodia',
        'aviso_tarde',
        'etapa_1',
        'etapa_2',
    ];

    public function asistencia(Request $request)
    {
        $user = $request->user();
        $mesas = Mesa::query()
            ->where('delegado_id', $user->id)
            ->pluck('id');

        if ($mesas->isEmpty()) {
            return response()->json([
                'mesas' => 0,
                'state' => [
                    'aviso_antes' => false,
                    'aviso_manana' => false,
                    'aviso_mediodia' => false,
                    'aviso_tarde' => false,
                    'etapa_1' => false,
                    'etapa_2' => false,
                ],
            ]);
        }

        $rows = ResultadoMesa::query()
            ->whereIn('mesa_id', $mesas)
            ->get($this->asistenciaFields);

        $state = [];
        foreach ($this->asistenciaFields as $f) {
            if ($rows->isEmpty()) {
                $state[$f] = false;
                continue;
            }
            $allTrue = $rows->every(fn ($r) => (bool) ($r->{$f} ?? false));
            $state[$f] = $allTrue;
        }

        return response()->json([
            'mesas' => $mesas->count(),
            'state' => $state,
        ]);
    }

    public function asistenciaUpdate(Request $request)
    {
        $data = $request->validate([
            'field' => 'required|string|in:aviso_antes,aviso_manana,aviso_mediodia,aviso_tarde,etapa_1,etapa_2',
            'value' => 'required|boolean',
        ]);

        $user = $request->user();
        $mesas = Mesa::query()
            ->where('delegado_id', $user->id)
            ->get(['id', 'delegado_id', 'estado']);

        if ($mesas->isEmpty()) {
            return response()->json(['message' => 'No hay mesas asignadas al delegado'], 422);
        }

        // Regla irreversible: una vez que un campo pasa a true, no puede volver a false.
        if ((bool) $data['value'] === false) {
            $alreadyTrue = ResultadoMesa::query()
                ->whereIn('mesa_id', $mesas->pluck('id'))
                ->where($data['field'], true)
                ->exists();
            if ($alreadyTrue) {
                return response()->json([
                    'message' => 'Este campo ya fue confirmado y no puede revertirse',
                ], 422);
            }
        }

        DB::transaction(function () use ($mesas, $user, $data) {
            foreach ($mesas as $mesa) {
                $rm = ResultadoMesa::updateOrCreate(
                    ['mesa_id' => $mesa->id],
                    ['registrado_por' => $user->id]
                );

                $rm->{$data['field']} = (bool) $data['value'];
                $rm->registrado_por = $user->id;
                $rm->save();

                if ((bool) $rm->etapa_2) {
                    $mesa->estado = 'FINALIZADA';
                } elseif (
                    (bool) $rm->etapa_1 ||
                    (bool) $rm->aviso_antes ||
                    (bool) $rm->aviso_manana ||
                    (bool) $rm->aviso_mediodia ||
                    (bool) $rm->aviso_tarde
                ) {
                    $mesa->estado = 'EN_PROCESO';
                } else {
                    $mesa->estado = $mesa->delegado_id ? 'ASIGNADA' : 'PENDIENTE';
                }
                $mesa->save();
            }
        });

        return response()->json([
            'ok' => true,
            'field' => $data['field'],
            'value' => (bool) $data['value'],
            'updated_mesas' => $mesas->count(),
        ]);
    }

    public function sync(Request $request)
    {
        $data = $request->validate([
            'mesa_id' => 'required|integer|exists:mesas,id',
            'payload' => 'required|array',
            'payload.total_votos' => 'required|integer|min:0',
            'payload.total_validos' => 'required|integer|min:0',
            'payload.total_blancos' => 'required|integer|min:0',
            'payload.total_nulos' => 'required|integer|min:0',
            'payload.observacion' => 'nullable|string',
            'payload.latitud' => 'nullable|numeric',
            'payload.longitud' => 'nullable|numeric',
            'payload.detalles' => 'required|array',
            'payload.detalles.*.partido_id' => 'required|integer|exists:partidos,id',
            'payload.detalles.*.votos_gobernador' => 'required|integer|min:0',
            'payload.detalles.*.votos_asambleista_distrito' => 'required|integer|min:0',
            'payload.detalles.*.votos_asambleista_poblacion' => 'required|integer|min:0',
            'payload.detalles.*.votos_concejal' => 'required|integer|min:0',
            'payload.detalles.*.votos_alcalde' => 'required|integer|min:0',
        ]);

        $user = $request->user();

        DB::transaction(function () use ($data, $user) {
            $rm = ResultadoMesa::updateOrCreate(
                ['mesa_id' => $data['mesa_id']],
                [
                    'registrado_por' => $user->id,
                    'total_votos' => $data['payload']['total_votos'],
                    'total_validos' => $data['payload']['total_validos'],
                    'total_blancos' => $data['payload']['total_blancos'],
                    'total_nulos' => $data['payload']['total_nulos'],
                    'observacion' => $data['payload']['observacion'] ?? null,
                    'latitud' => $data['payload']['latitud'] ?? null,
                    'longitud' => $data['payload']['longitud'] ?? null,
                ]
            );

            foreach ($data['payload']['detalles'] as $d) {
                ResultadoMesaDetalle::updateOrCreate(
                    [
                        'resultado_mesa_id' => $rm->id,
                        'partido_id' => $d['partido_id'],
                    ],
                    [
                        'votos_gobernador' => $d['votos_gobernador'],
                        'votos_asambleista_distrito' => $d['votos_asambleista_distrito'],
                        'votos_asambleista_poblacion' => $d['votos_asambleista_poblacion'],
                        'votos_concejal' => $d['votos_concejal'],
                        'votos_alcalde' => $d['votos_alcalde'],
                    ]
                );
            }
        });

        return response()->json(['ok' => true]);
    }
}
