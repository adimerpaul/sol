<?php

namespace App\Http\Controllers;

use App\Models\Localidad;
use App\Models\Mesa;
use App\Models\Municipio;
use App\Models\Provincia;
use App\Models\ResultadoMesa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GraficosController extends Controller
{
    public function index(Request $request)
    {
        $departamentoId = 5;
        $provinciaId = $request->input('provincia_id');
        $municipioId = $request->input('municipio_id');
        $localidadId = $request->input('localidad_id');

        $scope = [
            'departamento_id' => $departamentoId,
            'provincia_id' => $provinciaId ? (int) $provinciaId : null,
            'municipio_id' => $municipioId ? (int) $municipioId : null,
            'localidad_id' => $localidadId ? (int) $localidadId : null,
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
            ->when($scope['provincia_id'], fn ($q) => $q->where('provincia_id', $scope['provincia_id']))
            ->when($scope['municipio_id'], fn ($q) => $q->where('municipio_id', $scope['municipio_id']))
            ->when($scope['localidad_id'], fn ($q) => $q->where('localidad_id', $scope['localidad_id']));
        $mesasTotal = (int) $mesasBase->count();
        $mesasConResultado = (int) ResultadoMesa::query()
            ->whereHas('mesa', function ($q) use ($scope) {
                $q->where('departamento_id', $scope['departamento_id'])
                    ->when($scope['provincia_id'], fn ($qq) => $qq->where('provincia_id', $scope['provincia_id']))
                    ->when($scope['municipio_id'], fn ($qq) => $qq->where('municipio_id', $scope['municipio_id']))
                    ->when($scope['localidad_id'], fn ($qq) => $qq->where('localidad_id', $scope['localidad_id']));
            })
            ->distinct('mesa_id')
            ->count('mesa_id');
        $mesasFaltantes = max(0, $mesasTotal - $mesasConResultado);

        $provincias = Provincia::query()
            ->select('id', 'nombre')
            ->where('departamento_id', $departamentoId)
            ->orderBy('nombre')
            ->get();

        $municipios = Municipio::query()
            ->select('id', 'nombre', 'provincia_id')
            ->whereHas('provincia', function ($q) use ($departamentoId) {
                $q->where('departamento_id', $departamentoId);
            })
            ->when($scope['provincia_id'], fn ($q) => $q->where('provincia_id', $scope['provincia_id']))
            ->orderBy('nombre')
            ->get();

        $localidades = Localidad::query()
            ->select('id', 'nombre', 'municipio_id')
            ->whereHas('municipio.provincia', function ($q) use ($departamentoId) {
                $q->where('departamento_id', $departamentoId);
            })
            ->when($scope['municipio_id'], fn ($q) => $q->where('municipio_id', $scope['municipio_id']))
            ->orderBy('nombre')
            ->get();

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
            'options' => [
                'provincias' => $provincias,
                'municipios' => $municipios,
                'localidades' => $localidades,
            ],
            'scope' => $scope,
            'generated_at' => now()->toIso8601String(),
        ]);
    }
}
