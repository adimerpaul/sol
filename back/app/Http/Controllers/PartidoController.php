<?php

namespace App\Http\Controllers;

use App\Models\Partido;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class PartidoController extends Controller
{
    private function saveIcono(Request $request, ?string $oldFilename = null): ?string
    {
        if (!$request->hasFile('icono')) return $oldFilename;

        $file = $request->file('icono');
        if (!$file->isValid()) return $oldFilename;

        $dir = public_path('images/partidos');
        if (!File::exists($dir)) File::makeDirectory($dir, 0775, true);

        // borrar anterior
        if ($oldFilename) {
            $oldPath = $dir . DIRECTORY_SEPARATOR . $oldFilename;
            if (File::exists($oldPath)) File::delete($oldPath);
        }

        $ext = strtolower($file->getClientOriginalExtension());
        $filename = time() . '_' . uniqid() . '.jpg'; // normalizamos a jpg
        $path = $dir . DIRECTORY_SEPARATOR . $filename;

        $manager = new ImageManager(new Driver());

        $manager->read($file->getPathname())
            ->cover(300, 300)    // recorta/ajusta a 300x300 (queda cuadrado tipo avatar)
            ->toJpeg(75)         // calidad 75
            ->save($path);

        return $filename;
    }

    public function index(Request $request)
    {
        $search = trim((string)$request->get('search', ''));
        $perPage = (int)($request->get('per_page', 25));

        return Partido::query()
            ->when($search !== '', function ($q) use ($search) {
                $q->where('nombre', 'like', "%{$search}%")
                    ->orWhere('sigla', 'like', "%{$search}%")
                    ->orWhere('alcalde', 'like', "%{$search}%");
            })
            ->orderBy('sigla')
            ->paginate($perPage);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'sigla'   => 'required|string|max:50|unique:partidos,sigla',
            'nombre'  => 'required|string|max:150',
            'tipo'    => 'required|in:PARTIDO,AGRUPACION,INDIGENA',
            'alcalde' => 'nullable|string|max:150',
            'icono'   => 'nullable|file|mimes:jpg,jpeg,png,webp|max:4096',
        ]);

        $data['icono'] = $this->saveIcono($request, null);

        $row = Partido::create($data);

        return response()->json($row, 201);
    }

    public function update(Request $request, Partido $partido)
    {
        $data = $request->validate([
            'sigla'   => 'required|string|max:50|unique:partidos,sigla,' . $partido->id,
            'nombre'  => 'required|string|max:150',
            'tipo'    => 'required|in:PARTIDO,AGRUPACION,INDIGENA',
            'alcalde' => 'nullable|string|max:150',
            'icono'   => 'nullable|file|mimes:jpg,jpeg,png,webp|max:4096',
        ]);

        $data['icono'] = $this->saveIcono($request, $partido->icono);

        $partido->update($data);

        return response()->json($partido);
    }

    public function destroy(Partido $partido)
    {
        // borrar icono
        if ($partido->icono) {
            $path = public_path('images/partidos/' . $partido->icono);
            if (File::exists($path)) File::delete($path);
        }

        $partido->delete();

        return response()->json(['ok' => true]);
    }
}
