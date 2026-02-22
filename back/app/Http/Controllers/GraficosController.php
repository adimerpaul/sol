<?php

namespace App\Http\Controllers;

use App\Models\Mesa;
use App\Models\ResultadoMesa;
use Illuminate\Support\Facades\DB;

class GraficosController extends Controller
{
    public function index()
    {
        $ranking = DB::table('partidos as p')
            ->leftJoin('resultado_mesa_detalles as d', function ($join) {
                $join->on('d.partido_id', '=', 'p.id')
                    ->whereNull('d.deleted_at');
            })
//            solo de orruo
//                ->where('p.departamento_id', 9)
            ->whereNull('p.deleted_at')
            ->groupBy('p.id', 'p.sigla', 'p.nombre', 'p.color')
            ->selectRaw('
                p.id,
                p.sigla,
                p.nombre,
                p.color,
                (COALESCE(SUM(d.votos_concejal), 0) + COALESCE(SUM(d.votos_alcalde), 0)) as votos_validos
            ')
            ->orderByDesc('votos_validos')
            ->get()
            ->map(function ($r) {
                return [
                    'id' => (int) $r->id,
                    'sigla' => (string) ($r->sigla ?? ''),
                    'nombre' => (string) ($r->nombre ?? ''),
                    'color' => $r->color ?: null,
                    'votos_validos' => (int) ($r->votos_validos ?? 0),
                ];
            })
            ->values();

        $votosValidosTotal = (int) $ranking->sum('votos_validos');

        $mesasTotal = (int) Mesa::query()->count();
        $mesasConResultado = (int) ResultadoMesa::query()
            ->whereNotNull('mesa_id')
            ->distinct('mesa_id')
            ->count('mesa_id');
        $mesasFaltantes = max(0, $mesasTotal - $mesasConResultado);

        return response()->json([
            'votos_validos_total' => $votosValidosTotal,
            'ranking_validos' => $ranking,
            'mesas' => [
                'total' => $mesasTotal,
                'con_resultado' => $mesasConResultado,
                'faltantes' => $mesasFaltantes,
            ],
            'generated_at' => now()->toIso8601String(),
        ]);
    }
}

