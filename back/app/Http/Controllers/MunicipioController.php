<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Municipio;
use Illuminate\Http\Request;

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
}
