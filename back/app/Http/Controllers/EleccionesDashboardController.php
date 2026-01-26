<?php

namespace App\Http\Controllers;

use App\Models\Partido;
use App\Models\ResultadoMesa;
use Illuminate\Http\Request;

class EleccionesDashboardController extends Controller
{
    public function resumen(Request $request)
    {
        // ====== filtros opcionales ======
        $paisId         = $request->input('pais_id');
        $departamentoId = $request->input('departamento_id');
        $provinciaId    = $request->input('provincia_id');
        $municipioId    = $request->input('municipio_id');
        $localidadId    = $request->input('localidad_id');
        $recintoId      = $request->input('recinto_id');

        // por defecto: SOLO REALIZADO
        $soloRealizado = $request->boolean('solo_realizado', true);

        // ====== Partidos ======
        $partidos = Partido::query()
            ->select(['id', 'sigla', 'nombre', 'icono', 'tipo'])
            ->orderBy('sigla')
            ->get();

        // init totales por partido
        $totales = [];
        foreach ($partidos as $p) {
            $totales[(string)$p->id] = 0;
        }

        // ====== Base query (solo filtros, SIN select) ======
        $base = ResultadoMesa::query()
            ->when($soloRealizado, fn($qq) => $qq->where('estado', 'REALIZADO'))
            ->when(!$soloRealizado, fn($qq) => $qq->whereIn('estado', ['REALIZADO', 'PENDIENTE']))
            ->when($paisId, fn($qq) => $qq->where('pais_id', $paisId))
            ->when($departamentoId, fn($qq) => $qq->where('departamento_id', $departamentoId))
            ->when($provinciaId, fn($qq) => $qq->where('provincia_id', $provinciaId))
            ->when($municipioId, fn($qq) => $qq->where('municipio_id', $municipioId))
            ->when($localidadId, fn($qq) => $qq->where('localidad_id', $localidadId))
            ->when($recintoId, fn($qq) => $qq->where('recinto_id', $recintoId));

        // ====== métricas de mesas por estado (cards) ======
        $statsEstados = (clone $base)
            ->selectRaw('estado, COUNT(*) as c')
            ->groupBy('estado')
            ->pluck('c', 'estado');

        $mesasRealizadas = (int)($statsEstados['REALIZADO'] ?? 0);
        $mesasPendientes = (int)($statsEstados['PENDIENTE'] ?? 0);
        $mesasTotal      = $mesasRealizadas + $mesasPendientes;

        // ====== Query para leer resultados y sumar votos ======
        $q = (clone $base)->select(['id', 'resultados', 'total_votos', 'estado']);

        $votosTotales = 0;

        $q->orderBy('id')->chunkById(800, function ($rows) use (&$totales, &$votosTotales) {
            foreach ($rows as $row) {
                $resultados = $row->resultados;

                // por si viene como string
                if (is_string($resultados)) {
                    $decoded = json_decode($resultados, true);
                    $resultados = is_array($decoded) ? $decoded : [];
                }

                if (!is_array($resultados)) $resultados = [];

                foreach ($resultados as $partidoId => $v) {
                    $k = (string)$partidoId;
                    if (!array_key_exists($k, $totales)) continue;

                    $vv = (int)($v ?? 0);
                    $totales[$k] += $vv;
                    $votosTotales += $vv;
                }
            }
        });

        // ====== ranking ======
        $ranking = $partidos->map(function ($p) use ($totales) {
            $id = (string)$p->id;
            return [
                'id'     => $p->id,
                'sigla'  => $p->sigla,
                'nombre' => $p->nombre,
                'icono'  => $p->icono,
                'tipo'   => $p->tipo,
                'votos'  => (int)($totales[$id] ?? 0),
            ];
        })->sortByDesc('votos')->values();

        $ganador = $ranking->first();

        return response()->json([
            'filters' => [
                'pais_id' => $paisId,
                'departamento_id' => $departamentoId,
                'provincia_id' => $provinciaId,
                'municipio_id' => $municipioId,
                'localidad_id' => $localidadId,
                'recinto_id' => $recintoId,
                'solo_realizado' => $soloRealizado,
            ],
            'stats' => [
                'votos_totales' => $votosTotales,
                'mesas_total' => $mesasTotal,
                'mesas_realizadas' => $mesasRealizadas,
                'mesas_pendientes' => $mesasPendientes,
            ],
            'ganador' => $ganador,
            'ranking' => $ranking,
        ]);
    }
}
