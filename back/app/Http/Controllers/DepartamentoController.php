<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Departamento;
use Illuminate\Http\Request;

class DepartamentoController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string)$request->get('search', ''));
        $paisId = $request->get('pais_id');

        $q = Departamento::query()
            ->with(['pais:id,nombre'])
            ->select('id','id_original','pais_id','nombre','created_at')
            ->when($paisId, fn($qq) => $qq->where('pais_id', $paisId))
            ->when($search !== '', fn($qq) => $qq->where('nombre','like',"%{$search}%"));

        return $q->orderBy('nombre')->paginate($request->get('per_page', 25));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'id_original' => ['nullable','string','max:100'],
            'pais_id'     => ['required','exists:paises,id'],
            'nombre'      => ['required','string','max:200'],
        ]);

        $row = Departamento::create($data);
        return response()->json($row->load('pais:id,nombre'), 201);
    }

    public function update(Request $request, Departamento $departamento)
    {
        $data = $request->validate([
            'id_original' => ['nullable','string','max:100'],
            'pais_id'     => ['required','exists:paises,id'],
            'nombre'      => ['required','string','max:200'],
        ]);

        $departamento->update($data);
        return response()->json($departamento->load('pais:id,nombre'));
    }

    public function destroy(Departamento $departamento)
    {
        $departamento->delete();
        return response()->json(['ok' => true]);
    }
}
