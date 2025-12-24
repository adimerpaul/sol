<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Provincia;
use Illuminate\Http\Request;

class ProvinciaController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string)$request->get('search', ''));
        $departamentoId = $request->get('departamento_id');

        $q = Provincia::query()
            ->with(['departamento:id,nombre'])
            ->select('id','id_original','departamento_id','nombre','created_at')
            ->when($departamentoId, fn($qq) => $qq->where('departamento_id', $departamentoId))
            ->when($search !== '', fn($qq) => $qq->where('nombre','like',"%{$search}%"));

        return $q->orderBy('nombre')->paginate($request->get('per_page', 25));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'id_original'      => ['nullable','string','max:100'],
            'departamento_id'  => ['required','exists:departamentos,id'],
            'nombre'           => ['required','string','max:200'],
        ]);

        $row = Provincia::create($data);
        return response()->json($row->load('departamento:id,nombre'), 201);
    }

    public function update(Request $request, Provincia $provincia)
    {
        $data = $request->validate([
            'id_original'      => ['nullable','string','max:100'],
            'departamento_id'  => ['required','exists:departamentos,id'],
            'nombre'           => ['required','string','max:200'],
        ]);

        $provincia->update($data);
        return response()->json($provincia->load('departamento:id,nombre'));
    }

    public function destroy(Provincia $provincia)
    {
        $provincia->delete();
        return response()->json(['ok' => true]);
    }
}
