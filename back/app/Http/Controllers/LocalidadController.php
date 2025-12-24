<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Localidad;
use Illuminate\Http\Request;

class LocalidadController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string)$request->get('search', ''));
        $municipioId = $request->get('municipio_id');

        $q = Localidad::query()
            ->with(['municipio:id,nombre'])
            ->select('id','id_original','municipio_id','nombre','created_at')
            ->when($municipioId, fn($qq) => $qq->where('municipio_id', $municipioId))
            ->when($search !== '', fn($qq) => $qq->where('nombre','like',"%{$search}%"));

        return $q->orderBy('nombre')->paginate($request->get('per_page', 25));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'id_original'   => ['nullable','string','max:100'],
            'municipio_id'  => ['required','exists:municipios,id'],
            'nombre'        => ['required','string','max:200'],
        ]);

        $row = Localidad::create($data);
        return response()->json($row->load('municipio:id,nombre'), 201);
    }

    public function update(Request $request, Localidad $localidad)
    {
        $data = $request->validate([
            'id_original'   => ['nullable','string','max:100'],
            'municipio_id'  => ['required','exists:municipios,id'],
            'nombre'        => ['required','string','max:200'],
        ]);

        $localidad->update($data);
        return response()->json($localidad->load('municipio:id,nombre'));
    }

    public function destroy(Localidad $localidad)
    {
        $localidad->delete();
        return response()->json(['ok' => true]);
    }
}
