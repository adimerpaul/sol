<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Recinto;
use Illuminate\Http\Request;

class RecintoController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string)$request->get('search', ''));

        $paisId = $request->get('pais_id');
        $departamentoId = $request->get('departamento_id');
        $provinciaId = $request->get('provincia_id');
        $municipioId = $request->get('municipio_id');
        $localidadId = $request->get('localidad_id');

        $q = Recinto::query()
            ->with([
                'pais:id,nombre',
                'departamento:id,nombre',
                'provincia:id,nombre',
                'municipio:id,nombre',
                'localidad:id,nombre',
            ])
            ->select('id','id_original','pais_id','departamento_id','provincia_id','municipio_id','localidad_id','nombre','created_at')
            ->when($paisId, fn($qq) => $qq->where('pais_id', $paisId))
            ->when($departamentoId, fn($qq) => $qq->where('departamento_id', $departamentoId))
            ->when($provinciaId, fn($qq) => $qq->where('provincia_id', $provinciaId))
            ->when($municipioId, fn($qq) => $qq->where('municipio_id', $municipioId))
            ->when($localidadId, fn($qq) => $qq->where('localidad_id', $localidadId))
            ->when($search !== '', fn($qq) => $qq->where('nombre','like',"%{$search}%"));

        return $q->orderBy('nombre')->paginate($request->get('per_page', 25));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'id_original'       => ['nullable','string','max:100'],
            'pais_id'           => ['required','exists:paises,id'],
            'departamento_id'   => ['required','exists:departamentos,id'],
            'provincia_id'      => ['required','exists:provincias,id'],
            'municipio_id'      => ['required','exists:municipios,id'],
            'localidad_id'      => ['required','exists:localidades,id'],
            'nombre'            => ['required','string','max:250'],
        ]);

        $row = Recinto::create($data);
        return response()->json($row->load(['pais:id,nombre','departamento:id,nombre','provincia:id,nombre','municipio:id,nombre','localidad:id,nombre']), 201);
    }

    public function update(Request $request, Recinto $recinto)
    {
        $data = $request->validate([
            'id_original'       => ['nullable','string','max:100'],
            'pais_id'           => ['required','exists:paises,id'],
            'departamento_id'   => ['required','exists:departamentos,id'],
            'provincia_id'      => ['required','exists:provincias,id'],
            'municipio_id'      => ['required','exists:municipios,id'],
            'localidad_id'      => ['required','exists:localidades,id'],
            'nombre'            => ['required','string','max:250'],
        ]);

        $recinto->update($data);
        return response()->json($recinto->load(['pais:id,nombre','departamento:id,nombre','provincia:id,nombre','municipio:id,nombre','localidad:id,nombre']));
    }

    public function destroy(Recinto $recinto)
    {
        $recinto->delete();
        return response()->json(['ok' => true]);
    }
}
