<?php

namespace App\Http\Controllers;

use App\Models\ResultadoMesa;
use App\Models\ResultadoMesaDetalle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MobileResultadosController extends Controller
{
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
