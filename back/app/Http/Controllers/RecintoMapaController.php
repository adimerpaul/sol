<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Provincia;
use App\Models\Municipio;
use App\Models\Localidad;
use App\Models\Recinto;
use Illuminate\Http\Request;

class RecintoMapaController extends Controller
{
    /**
     * Opcional: si quieres restringir solo ORURO:
     * - Puedes filtrar por departamento_id fijo.
     * - O por nombre departamento.
     */
    private function deptOruroId(): ?int
    {
        // si ya tienes el id, ponlo fijo y listo:
        // return 4;

        // si prefieres buscar por nombre:
        $dep = \App\Models\Departamento::whereRaw('LOWER(nombre) = ?', ['oruro'])->first();
        return $dep?->id;
    }

    public function catalogo()
    {
        $deptId = $this->deptOruroId();

        $provincias = Provincia::query()
            ->when($deptId, fn($q) => $q->where('departamento_id', $deptId))
            ->select('id','nombre')
            ->orderBy('nombre')
            ->get();

        return response()->json([
            'provincias' => $provincias,
        ]);
    }

    public function municipios($provinciaId)
    {
        $items = Municipio::where('provincia_id', $provinciaId)
            ->select('id','nombre','provincia_id')
            ->orderBy('nombre')
            ->get();

        return response()->json($items);
    }

    public function localidades($municipioId)
    {
        $items = Localidad::where('municipio_id', $municipioId)
            ->select('id','nombre','municipio_id')
            ->orderBy('nombre')
            ->get();

        return response()->json($items);
    }

    /**
     * Lista recintos por filtros. Incluye flag "faltante".
     * query:
     * - provincia_id?
     * - municipio_id?
     * - localidad_id?
     * - only_missing? (1/0)
     * - search? (nombre)
     */
    public function recintos(Request $request)
    {
        $deptId = $this->deptOruroId();

        $q = Recinto::query()
            ->when($deptId, fn($qq) => $qq->where('departamento_id', $deptId))
            ->when($request->provincia_id, fn($qq) => $qq->where('provincia_id', $request->provincia_id))
            ->when($request->municipio_id, fn($qq) => $qq->where('municipio_id', $request->municipio_id))
            ->when($request->localidad_id, fn($qq) => $qq->where('localidad_id', $request->localidad_id))
            ->when($request->search, function($qq) use ($request){
                $s = trim($request->search);
                $qq->where('nombre', 'like', "%{$s}%");
            })
            ->when($request->only_missing == 1, function($qq){
                $qq->where(function($w){
                    $w->whereNull('latitud')
                        ->orWhereNull('longitud');
                });
            })
            ->with([
                'provincia:id,nombre',
                'municipio:id,nombre',
                'localidad:id,nombre',
            ])
            ->orderBy('nombre');

        $items = $q->get()->map(function($r){
            $missing = empty($r->latitud) || empty($r->longitud);
            return [
                'id' => $r->id,
                'nombre' => $r->nombre,
                'distrito' => $r->distrito,
                'circunscripcion' => $r->circunscripcion,
                'latitud' => $r->latitud,
                'longitud' => $r->longitud,
                'missing' => $missing,
                'provincia' => $r->provincia,
                'municipio' => $r->municipio,
                'localidad' => $r->localidad,
            ];
        });

        return response()->json($items);
    }

    public function show(Recinto $recinto)
    {
        $recinto->load(['provincia:id,nombre','municipio:id,nombre','localidad:id,nombre']);
        return response()->json($recinto);
    }

    /**
     * Guardar distrito/circunscripción/lat/lng.
     */
    public function update(Request $request, Recinto $recinto)
    {
        $data = $request->validate([
            'distrito' => 'nullable|string|max:255',
            'circunscripcion' => 'nullable|string|max:255',
            'latitud' => 'nullable|numeric',
            'longitud' => 'nullable|numeric',
        ]);

        $recinto->fill($data);
        $recinto->save();

        $recinto->load(['provincia:id,nombre','municipio:id,nombre','localidad:id,nombre']);

        return response()->json([
            'message' => 'Recinto actualizado',
            'data' => $recinto
        ]);
    }
}
