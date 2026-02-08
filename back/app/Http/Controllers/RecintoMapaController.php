<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use App\Http\Controllers\Controller;
use App\Models\Provincia;
use App\Models\Municipio;
use App\Models\Localidad;
use App\Models\Recinto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
class RecintoMapaController extends Controller
{
    public function geocodeOruro(Request $request)
    {
//        $apiKey = config('services.google.maps_key', env('GOOGLE_MAPS_API_KEY'));
        $apiKey = "XXXXs";

        if (!$apiKey) {
            return response()->json([
                'message' => 'Falta GOOGLE_MAPS_API_KEY en .env (o services.google.maps_key).'
            ], 422);
        }

        // Tus filtros (igual que tu SQL)
        $departamentoId = (int) $request->input('departamento_id', 5);
        $provinciaId    = (int) $request->input('provincia_id', 57);
        $municipioId    = (int) $request->input('municipio_id', 191);
        $localidadId    = (int) $request->input('localidad_id', 1988);

        // Control de lote para no quemar cuota
        $limit = (int) $request->input('limit', 50);
        if ($limit < 1) $limit = 1;
        if ($limit > 300) $limit = 300;

        // Solo los que NO tienen coordenadas
        $recintos = Recinto::query()
            ->with(['pais', 'departamento', 'provincia', 'municipio', 'localidad'])
            ->where('departamento_id', $departamentoId)
            ->where('provincia_id', $provinciaId)
            ->where('municipio_id', $municipioId)
            ->where('localidad_id', $localidadId)
            ->whereNull('latitud')
            ->whereNull('longitud')
            ->orderBy('id')
            ->limit($limit)
            ->get();

        $updated = 0;
        $errors = [];

        foreach ($recintos as $r) {
            // Arma una "dirección" lo más completa posible
            // OJO: Entre más contexto, mejores resultados.
            $parts = array_filter([
                $r->nombre,
                optional($r->localidad)->nombre,
                optional($r->municipio)->nombre,
                optional($r->provincia)->nombre,
                optional($r->departamento)->nombre,
                optional($r->pais)->nombre ?? 'Bolivia',
            ]);

            $address = implode(', ', $parts);

            try {
                $resp = Http::timeout(15)
                    ->retry(2, 300) // 2 reintentos, 300ms
                    ->get('https://maps.googleapis.com/maps/api/geocode/json', [
                        'address' => $address,
                        'key'     => $apiKey,
                        'region'  => 'bo',      // Bolivia
                        'language'=> 'es',
                    ]);

                if (!$resp->ok()) {
                    $errors[] = [
                        'recinto_id' => $r->id,
                        'nombre' => $r->nombre,
                        'address' => $address,
                        'error' => 'HTTP '.$resp->status(),
                    ];
                    continue;
                }

                $data = $resp->json();

                if (($data['status'] ?? null) !== 'OK') {
                    $errors[] = [
                        'recinto_id' => $r->id,
                        'nombre' => $r->nombre,
                        'address' => $address,
                        'error' => $data['status'] ?? 'UNKNOWN',
                        'message' => $data['error_message'] ?? null,
                    ];
                    continue;
                }

                $loc = $data['results'][0]['geometry']['location'] ?? null;
                if (!$loc || !isset($loc['lat'], $loc['lng'])) {
                    $errors[] = [
                        'recinto_id' => $r->id,
                        'nombre' => $r->nombre,
                        'address' => $address,
                        'error' => 'No geometry.location',
                    ];
                    continue;
                }

                $r->latitud  = (string) $loc['lat']; // si tu columna es decimal/string
                $r->longitud = (string) $loc['lng'];
                $r->save();

                $updated++;

                // Mini pausa para no saturar (ajusta si quieres)
                usleep(120000); // 120ms
            } catch (\Throwable $e) {
                Log::error('Geocode recinto failed', [
                    'recinto_id' => $r->id,
                    'address' => $address,
                    'ex' => $e->getMessage()
                ]);

                $errors[] = [
                    'recinto_id' => $r->id,
                    'nombre' => $r->nombre,
                    'address' => $address,
                    'error' => 'EXCEPTION',
                    'message' => $e->getMessage(),
                ];
            }
        }

        return response()->json([
            'filters' => [
                'departamento_id' => $departamentoId,
                'provincia_id' => $provinciaId,
                'municipio_id' => $municipioId,
                'localidad_id' => $localidadId,
                'limit' => $limit,
            ],
            'found' => $recintos->count(),
            'updated' => $updated,
            'errors_count' => count($errors),
            'errors' => $errors,
        ]);
    }
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
            ->withCount([
                'mesas',
                'mesas as mesas_asignadas_count' => function ($qq) {
                    $qq->whereNotNull('delegado_id');
                }
            ])
            ->orderBy('nombre');

        $items = $q->get()->map(function($r){
            $missing = empty($r->latitud) || empty($r->longitud);
            $totalMesas = (int)($r->mesas_count ?? 0);
            $asignadas = (int)($r->mesas_asignadas_count ?? 0);
            $delegadosOk = $totalMesas > 0 ? ($asignadas >= $totalMesas) : true;
            return [
                'id' => $r->id,
                'nombre' => $r->nombre,
                'distrito' => $r->distrito,
                'circunscripcion' => $r->circunscripcion,
                'latitud' => $r->latitud,
                'longitud' => $r->longitud,
                'missing' => $missing,
                'mesas_total' => $totalMesas,
                'mesas_asignadas' => $asignadas,
                'delegados_ok' => $delegadosOk,
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
