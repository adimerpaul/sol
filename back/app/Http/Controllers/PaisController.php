<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Pais;
use App\Models\Departamento;
use App\Models\Provincia;
use App\Models\Municipio;
use App\Models\Localidad;
use App\Models\Recinto;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PaisController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string)$request->get('search', ''));

        $q = Pais::query()
            ->select('id','id_original','nombre','created_at')
            ->when($search !== '', fn($qq) => $qq->where('nombre','like',"%{$search}%"));

        return $q->orderBy('nombre')->paginate($request->get('per_page', 25));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'id_original' => ['nullable','string','max:100'],
            'nombre'      => ['required','string','max:200'],
        ]);

        $pais = Pais::create($data);
        return response()->json($pais, 201);
    }

    public function show(Pais $paise) // route model binding (paises/{paise})
    {
        return $paise->loadCount('departamentos','recintos','mesas');
    }

    public function update(Request $request, Pais $paise)
    {
        $data = $request->validate([
            'id_original' => ['nullable','string','max:100'],
            'nombre'      => ['required','string','max:200'],
        ]);

        $paise->update($data);
        return response()->json($paise);
    }

    public function destroy(Pais $paise)
    {
        $paise->delete();
        return response()->json(['ok' => true]);
    }

    /**
     * Devuelve options para selects en cascada (front).
     */
    public function options()
    {
        $paises = Pais::select('id','nombre')->orderBy('nombre')->get();

        $departamentos = Departamento::select('id','pais_id','nombre')
            ->orderBy('nombre')->get();

        $provincias = Provincia::select('id','departamento_id','nombre')
            ->orderBy('nombre')->get();

        $municipios = Municipio::select('id','provincia_id','nombre')
            ->orderBy('nombre')->get();

        $localidades = Localidad::select('id','municipio_id','nombre')
            ->orderBy('nombre')->get();

        $recintos = Recinto::select('id','localidad_id','municipio_id','provincia_id','departamento_id','pais_id','nombre')
            ->orderBy('nombre')->get();

        return response()->json(compact(
            'paises','departamentos','provincias','municipios','localidades','recintos'
        ));
    }
}
