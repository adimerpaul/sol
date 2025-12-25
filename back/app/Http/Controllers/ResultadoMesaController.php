<?php

namespace App\Http\Controllers;

use App\Models\ResultadoMesa;
use App\Models\Mesa;
use Illuminate\Http\Request;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class ResultadoMesaController extends Controller
{
    /**
     * Mesas del usuario (recintos asignados), incluyendo si ya tienen resultado:
     * - si NO tiene resultado => editable
     * - si tiene resultado estado=REALIZADO => solo ver
     * - si tiene resultado estado=PENDIENTE => permite re-cargar
     */
    public function mesasAsignadas(Request $request)
    {
        $user = $request->user();
        $recintoIds = $user->recintos->pluck('id');

        return Mesa::query()
            ->whereIn('recinto_id', $recintoIds)
            ->with([
                'recinto:id,nombre',
                'resultadoMesa:id,mesa_id,estado,total_votos,fecha_hora,verificado'
            ])
            ->orderBy('numero_mesa')
            ->get();
    }

    /**
     * Obtener detalle de una mesa + resultado (si existe)
     */
    public function showByMesa(Request $request, Mesa $mesa)
    {
        $user = $request->user();

        // seguridad: solo mesas de recintos asignados
        if (!$user->recintos->pluck('id')->contains($mesa->recinto_id)) {
            return response()->json(['message' => 'No autorizado para esta mesa'], 403);
        }

        $mesa->load([
            'recinto:id,nombre',
            'localidad:id,nombre',
            'municipio:id,nombre',
            'provincia:id,nombre',
            'departamento:id,nombre',
            'pais:id,nombre',
            'resultadoMesa'
        ]);

        return $mesa;
    }

    /**
     * Crear / Re-cargar resultado:
     * - si no existe => crea como REALIZADO
     * - si existe y estado=REALIZADO => NO permite
     * - si existe y estado=PENDIENTE => sobrescribe y vuelve a REALIZADO
     */
    public function store(Request $request)
    {
        $request->validate([
            'mesa_id'     => 'required|exists:mesas,id',
            'resultados'  => 'required',
            'foto_1'      => 'nullable|image',
            'foto_2'      => 'nullable|image',
            'observacion' => 'nullable|string|max:255',
        ]);

        $user = $request->user();

        // parse resultados (puede venir string JSON)
        $raw = $request->input('resultados');
        if (is_string($raw)) {
            $decoded = json_decode($raw, true);
            if (!is_array($decoded)) {
                return response()->json(['message' => 'Formato resultados inválido'], 422);
            }
            $resultados = $decoded;
        } elseif (is_array($raw)) {
            $resultados = $raw;
        } else {
            return response()->json(['message' => 'Formato resultados inválido'], 422);
        }

        $resultados = collect($resultados)->map(fn ($v) => (int)$v)->toArray();

        $mesa = Mesa::with(['recinto','localidad','municipio','provincia','departamento','pais'])
            ->findOrFail($request->mesa_id);

        // seguridad: mesa solo si recinto asignado al user
        if (!$user->recintos->pluck('id')->contains($mesa->recinto_id)) {
            return response()->json(['message' => 'No autorizado para esta mesa'], 403);
        }

        // Buscar si ya existe resultado
        $existing = ResultadoMesa::where('mesa_id', $mesa->id)->first();

        if ($existing && $existing->estado === 'REALIZADO') {
            return response()->json([
                'message' => 'Esta mesa ya está REALIZADA. Si necesitas re-cargar, el administrador debe cambiar a PENDIENTE.'
            ], 409);
        }

        // carpeta actas
        $actasDir = public_path('actas');
        if (!is_dir($actasDir)) {
            @mkdir($actasDir, 0777, true);
        }

        $imgManager = new ImageManager(new Driver());
        $fotos = [];

        foreach (['foto_1', 'foto_2'] as $campo) {
            if ($request->hasFile($campo)) {
                $file = $request->file($campo);
                $name = uniqid('acta_') . '.jpg';
                $path = $actasDir . DIRECTORY_SEPARATOR . $name;

                $imgManager->read($file->getPathname())
                    ->orient()              // respeta orientación real (EXIF)
//                        resize
                    ->resize(600)
                    ->toJpeg(85)            // solo compresión, NO tamaño
                    ->save($path);

                $fotos[$campo] = $name;
            }
        }

        // upsert
        $row = $existing ?: new ResultadoMesa();
        $row->fill([
            'user_id' => $user->id,
            'pais_id' => $mesa->pais_id,
            'departamento_id' => $mesa->departamento_id,
            'provincia_id' => $mesa->provincia_id,
            'municipio_id' => $mesa->municipio_id,
            'localidad_id' => $mesa->localidad_id,
            'recinto_id' => $mesa->recinto_id,
            'mesa_id' => $mesa->id,
            'resultados' => $resultados,
            'total_votos' => array_sum($resultados),
            'observacion' => $request->observacion,
            'estado' => 'REALIZADO',
        ]);

        // si re-carga y no mandó foto nueva, conserva la anterior
        if (isset($fotos['foto_1'])) $row->foto_1 = $fotos['foto_1'];
        if (isset($fotos['foto_2'])) $row->foto_2 = $fotos['foto_2'];

        $row->save();

        return response()->json($row, $existing ? 200 : 201);
    }
}
