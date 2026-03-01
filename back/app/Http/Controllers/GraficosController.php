<?php

namespace App\Http\Controllers;

use App\Models\Mesa;
use App\Models\ResultadoMesa;
use Illuminate\Support\Facades\DB;

class GraficosController extends Controller
{
    public function index()
    {
        $scope = [
            'departamento_id' => 5,
            'provincia_id' => 57,
            'municipio_id' => 191,
        ];

        $partidosRaw = DB::table('partidos as p')
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
                    ->where('m.departamento_id', '=', $scope['departamento_id'])
                    ->where('m.provincia_id', '=', $scope['provincia_id'])
                    ->where('m.municipio_id', '=', $scope['municipio_id']);
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

        $ranking = $partidosRaw->sortByDesc('votos_validos')->values();
        $rankingAlcalde = $partidosRaw->sortByDesc('votos_alcalde')->values();
        $rankingConcejal = $partidosRaw->sortByDesc('votos_concejal')->values();
        $rankingGobernador = $partidosRaw->sortByDesc('votos_gobernador')->values();
        $rankingAsd = $partidosRaw->sortByDesc('votos_asambleista_distrito')->values();
        $rankingAsp = $partidosRaw->sortByDesc('votos_asambleista_poblacion')->values();

        $votosValidosTotal = (int) $partidosRaw->sum('votos_validos');

        $mesasBase = Mesa::query()
            ->where('departamento_id', $scope['departamento_id'])
            ->where('provincia_id', $scope['provincia_id'])
            ->where('municipio_id', $scope['municipio_id']);
        $mesasTotal = (int) $mesasBase->count();
        $mesasConResultado = (int) ResultadoMesa::query()
            ->whereHas('mesa', function ($q) use ($scope) {
                $q->where('departamento_id', $scope['departamento_id'])
                    ->where('provincia_id', $scope['provincia_id'])
                    ->where('municipio_id', $scope['municipio_id']);
            })
            ->distinct('mesa_id')
            ->count('mesa_id');
        $mesasFaltantes = max(0, $mesasTotal - $mesasConResultado);

        return response()->json([
            'votos_validos_total' => $votosValidosTotal,
            'ranking_validos' => $ranking,
            'categorias' => [
                'alcalde' => [
                    'total' => (int) $partidosRaw->sum('votos_alcalde'),
                    'ranking' => $rankingAlcalde,
                ],
                'concejal' => [
                    'total' => (int) $partidosRaw->sum('votos_concejal'),
                    'ranking' => $rankingConcejal,
                ],
                'gobernador' => [
                    'total' => (int) $partidosRaw->sum('votos_gobernador'),
                    'ranking' => $rankingGobernador,
                ],
                'asambleista_distrito' => [
                    'total' => (int) $partidosRaw->sum('votos_asambleista_distrito'),
                    'ranking' => $rankingAsd,
                ],
                'asambleista_poblacion' => [
                    'total' => (int) $partidosRaw->sum('votos_asambleista_poblacion'),
                    'ranking' => $rankingAsp,
                ],
            ],
            'mesas' => [
                'total' => $mesasTotal,
                'con_resultado' => $mesasConResultado,
                'faltantes' => $mesasFaltantes,
            ],
            'scope' => $scope,
            'generated_at' => now()->toIso8601String(),
        ]);
    }
}
