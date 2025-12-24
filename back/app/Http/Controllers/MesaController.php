<?php

namespace App\Http\Controllers;

use App\Models\Mesa;
use Illuminate\Http\Request;

class MesaController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->get('search', ''));

        $recintoId = $request->get('recinto_id');
        $localidadId = $request->get('localidad_id');
        $municipioId = $request->get('municipio_id');
        $provinciaId = $request->get('provincia_id');
        $departamentoId = $request->get('departamento_id');
        $paisId = $request->get('pais_id');

        $perPage = (int) $request->get('per_page', 25);
        if ($perPage < 1) $perPage = 25;
        if ($perPage > 200) $perPage = 200; // opcional límite

        $q = Mesa::query()
            ->with([
                'pais:id,nombre',
                'departamento:id,nombre',
                'provincia:id,nombre',
                'municipio:id,nombre',
                'localidad:id,nombre',
                'recinto:id,nombre',
            ])
            ->select('id','id_original','pais_id','departamento_id','provincia_id','municipio_id','localidad_id','recinto_id','numero_mesa','created_at')
            ->when($paisId, fn($qq) => $qq->where('pais_id', $paisId))
            ->when($departamentoId, fn($qq) => $qq->where('departamento_id', $departamentoId))
            ->when($provinciaId, fn($qq) => $qq->where('provincia_id', $provinciaId))
            ->when($municipioId, fn($qq) => $qq->where('municipio_id', $municipioId))
            ->when($localidadId, fn($qq) => $qq->where('localidad_id', $localidadId))
            ->when($recintoId, fn($qq) => $qq->where('recinto_id', $recintoId))
            ->when($search !== '', fn($qq) => $qq->where('numero_mesa', 'like', "%{$search}%"));

        return $q->orderBy('numero_mesa')->paginate($perPage);
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
            'recinto_id'        => ['required','exists:recintos,id'],
            // el front usa "nombre" para todo; acá lo convertimos
            'nombre'            => ['required','string','max:50'],
        ]);

        $data['numero_mesa'] = $data['nombre'];
        unset($data['nombre']);

        $row = Mesa::create($data);

        return response()->json(
            $row->load(['pais:id,nombre','departamento:id,nombre','provincia:id,nombre','municipio:id,nombre','localidad:id,nombre','recinto:id,nombre']),
            201
        );
    }

    public function update(Request $request, Mesa $mesa)
    {
        // en update tu componente manda numero_mesa
        $data = $request->validate([
            'id_original'       => ['nullable','string','max:100'],
            'pais_id'           => ['required','exists:paises,id'],
            'departamento_id'   => ['required','exists:departamentos,id'],
            'provincia_id'      => ['required','exists:provincias,id'],
            'municipio_id'      => ['required','exists:municipios,id'],
            'localidad_id'      => ['required','exists:localidades,id'],
            'recinto_id'        => ['required','exists:recintos,id'],
            'numero_mesa'       => ['required','string','max:50'],
        ]);

        $mesa->update($data);

        return response()->json(
            $mesa->load(['pais:id,nombre','departamento:id,nombre','provincia:id,nombre','municipio:id,nombre','localidad:id,nombre','recinto:id,nombre'])
        );
    }

    public function destroy(Mesa $mesa)
    {
        $mesa->delete();
        return response()->json(['ok' => true]);
    }
}
