<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Municipio;
use App\Models\Partido;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MunicipioController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string)$request->get('search', ''));
        $provinciaId = $request->get('provincia_id');

        $q = Municipio::query()
            ->with(['provincia:id,nombre'])
            ->select('id','id_original','provincia_id','nombre','created_at')
            ->when($provinciaId, fn($qq) => $qq->where('provincia_id', $provinciaId))
            ->when($request->has('departamento_id'), function($qq) use ($request) {
                $qq->whereHas('provincia', function($qqq) use ($request)                    {
                    $qqq->where('departamento_id', $request->get('departamento_id'));
                });
            })
            ->when($search !== '', fn($qq) => $qq->where('nombre','like',"%{$search}%"));

        return $q->orderBy('nombre')->paginate($request->get('per_page', 25));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'id_original'   => ['nullable','string','max:100'],
            'provincia_id'  => ['required','exists:provincias,id'],
            'nombre'        => ['required','string','max:200'],
        ]);

        $row = Municipio::create($data);
        return response()->json($row->load('provincia:id,nombre'), 201);
    }

    public function update(Request $request, Municipio $municipio)
    {
        $data = $request->validate([
            'id_original'   => ['nullable','string','max:100'],
            'provincia_id'  => ['required','exists:provincias,id'],
            'nombre'        => ['required','string','max:200'],
        ]);

        $municipio->update($data);
        return response()->json($municipio->load('provincia:id,nombre'));
    }

    public function destroy(Municipio $municipio)
    {
        $municipio->delete();
        return response()->json(['ok' => true]);
    }

    public function partidos(Municipio $municipio)
    {
        $partidos = Partido::query()
            ->leftJoin('municipio_partido as mp', function ($join) use ($municipio) {
                $join->on('mp.partido_id', '=', 'partidos.id')
                    ->where('mp.municipio_id', '=', $municipio->id);
            })
            ->select([
                'partidos.id',
                'partidos.sigla',
                'partidos.nombre',
                'partidos.icono',
                'partidos.tipo',
                'partidos.color',
                DB::raw('COALESCE(mp.habilitado_gobernador, 1) as habilitado_gobernador'),
                DB::raw('COALESCE(mp.habilitado_asambleista_poblacion, 1) as habilitado_asambleista_poblacion'),
                DB::raw('COALESCE(mp.habilitado_asambleista_distrito, 1) as habilitado_asambleista_distrito'),
                DB::raw('COALESCE(mp.habilitado_alcalde, 1) as habilitado_alcalde'),
                DB::raw('COALESCE(mp.habilitado_concejal, 1) as habilitado_concejal'),
            ])
            ->orderBy('partidos.sigla')
            ->get()
            ->map(function ($partido) {
                return [
                    'id' => $partido->id,
                    'sigla' => $partido->sigla,
                    'nombre' => $partido->nombre,
                    'icono' => $partido->icono,
                    'tipo' => $partido->tipo,
                    'color' => $partido->color,
                    'habilitado_gobernador' => (bool) $partido->habilitado_gobernador,
                    'habilitado_asambleista_poblacion' => (bool) $partido->habilitado_asambleista_poblacion,
                    'habilitado_asambleista_distrito' => (bool) $partido->habilitado_asambleista_distrito,
                    'habilitado_alcalde' => (bool) $partido->habilitado_alcalde,
                    'habilitado_concejal' => (bool) $partido->habilitado_concejal,
                ];
            })
            ->values();

        $municipio->load('provincia:id,nombre');

        return response()->json([
            'municipio' => [
                'id' => $municipio->id,
                'nombre' => $municipio->nombre,
                'provincia' => $municipio->provincia ? [
                    'id' => $municipio->provincia->id,
                    'nombre' => $municipio->provincia->nombre,
                ] : null,
            ],
            'partidos' => $partidos,
        ]);
    }

    public function syncPartidos(Request $request, Municipio $municipio)
    {
        $data = $request->validate([
            'partidos' => ['required', 'array', 'min:1'],
            'partidos.*.partido_id' => ['required', 'integer', 'exists:partidos,id'],
            'partidos.*.habilitado_gobernador' => ['required', 'boolean'],
            'partidos.*.habilitado_asambleista_poblacion' => ['required', 'boolean'],
            'partidos.*.habilitado_asambleista_distrito' => ['required', 'boolean'],
            'partidos.*.habilitado_alcalde' => ['required', 'boolean'],
            'partidos.*.habilitado_concejal' => ['required', 'boolean'],
        ]);

        $rows = collect($data['partidos'])
            ->unique('partido_id')
            ->map(function ($row) use ($municipio) {
                return [
                    'municipio_id' => $municipio->id,
                    'partido_id' => (int) $row['partido_id'],
                    'habilitado_gobernador' => (bool) $row['habilitado_gobernador'],
                    'habilitado_asambleista_poblacion' => (bool) $row['habilitado_asambleista_poblacion'],
                    'habilitado_asambleista_distrito' => (bool) $row['habilitado_asambleista_distrito'],
                    'habilitado_alcalde' => (bool) $row['habilitado_alcalde'],
                    'habilitado_concejal' => (bool) $row['habilitado_concejal'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            })
            ->values();

        DB::transaction(function () use ($municipio, $rows) {
            DB::table('municipio_partido')
                ->where('municipio_id', $municipio->id)
                ->whereNotIn('partido_id', $rows->pluck('partido_id'))
                ->delete();

            DB::table('municipio_partido')->upsert(
                $rows->all(),
                ['municipio_id', 'partido_id'],
                [
                    'habilitado_gobernador',
                    'habilitado_asambleista_poblacion',
                    'habilitado_asambleista_distrito',
                    'habilitado_alcalde',
                    'habilitado_concejal',
                    'updated_at',
                ]
            );
        });

        return response()->json(['ok' => true]);
    }
}
