<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Mesa;
use App\Models\Partido;
use Illuminate\Http\Request;

class MobileAuthController extends Controller
{
    private function partidoIconoBase64(?string $icono): ?string
    {
        if (empty($icono)) {
            return null;
        }
        $path = public_path('images/partidos/' . $icono);
        if (!is_file($path)) {
            return null;
        }
        $binary = @file_get_contents($path);
        if ($binary === false || $binary === '') {
            return null;
        }

        if (!function_exists('imagecreatefromstring')) {
            return 'data:image/jpeg;base64,' . base64_encode($binary);
        }

        $source = @imagecreatefromstring($binary);
        if ($source === false) {
            return 'data:image/jpeg;base64,' . base64_encode($binary);
        }

        $targetSize = 24;
        $target = imagecreatetruecolor($targetSize, $targetSize);
        if ($target === false) {
            imagedestroy($source);
            return null;
        }

        imagefill($target, 0, 0, imagecolorallocate($target, 255, 255, 255));
        imagecopyresampled(
            $target,
            $source,
            0,
            0,
            0,
            0,
            $targetSize,
            $targetSize,
            imagesx($source),
            imagesy($source)
        );

        ob_start();
        imagejpeg($target, null, 55);
        $jpg = ob_get_clean();

        imagedestroy($target);
        imagedestroy($source);

        if ($jpg === false || $jpg === '') {
            return null;
        }

        return 'data:image/jpeg;base64,' . base64_encode($jpg);
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'ci' => 'required|string|max:30',
            'fecha_nacimiento' => 'required|string',
        ]);

        $fecha = substr($data['fecha_nacimiento'], 0, 10);
//        error_log("Login attempt - CI: {$data['ci']}, Fecha Nacimiento: {$fecha}");

        $user = User::query()
            ->where('ci', $data['ci'])
            ->whereDate('fecha_nacimiento', $fecha)
            ->first();

        if (!$user) {
            return response()->json(['message' => 'CI o fecha de nacimiento incorrectos'], 401);
        }

        // ✅ Mesa asignada pendiente (la más simple)
        $mesas = Mesa::query()
            ->where('delegado_id', $user->id)
            ->with([
                'recinto:id,nombre,latitud,longitud',
                'localidad:id,nombre',
                'municipio:id,nombre',
                'provincia:id,nombre',
                'departamento:id,nombre',
            ])
            ->orderBy('numero_mesa')
            ->orderBy('id')
            ->get();

        $partidos = Partido::query()
            ->select('id', 'sigla', 'nombre', 'icono', 'orden_municipal', 'orden_departamental')
            ->whereNull('deleted_at')
            ->orderBy('orden_municipal')
            ->orderBy('sigla')
            ->get()
            ->map(function ($p) {
                $iconoBase64 = $this->partidoIconoBase64($p->icono);
                return [
                    'id' => $p->id,
                    'sigla' => $p->sigla,
                    'nombre' => $p->nombre,
                    'icono' => $p->icono,
                    'icono_url' => null,
                    'icono_base64' => $iconoBase64,
                    'orden_municipal' => (int) ($p->orden_municipal ?? 0),
                    'orden_departamental' => (int) ($p->orden_departamental ?? 0),
                ];
            })
            ->values();

        // Jerarquia via tablas pivote:
        // delegado -> jefe (jefe_delegado), jefe -> supervisor (supervisor_jefe)
        // Se devuelve cada jefe con sus supervisores.
        $jefes = $user->jefes()
            ->select('users.id', 'users.name', 'users.nombres', 'users.celular')
            ->get()
            ->map(function ($jefe) {
                $supervisores = $jefe->supervisores()
                    ->select('users.id', 'users.name', 'users.nombres', 'users.celular')
                    ->get();

                return [
                    'id' => $jefe->id,
                    'name' => $jefe->name,
                    'nombres' => $jefe->nombres,
                    'celular' => $jefe->celular,
                    'supervisores' => $supervisores,
                ];
            })
            ->values();

        // Compatibilidad: lista unica de supervisores a partir de los jefes.
        $supervisores = $jefes
            ->flatMap(fn ($j) => $j['supervisores'])
            ->unique('id')
            ->values();

        // Token Sanctum
        $token = $user->createToken('mobile')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name
                    ?? trim(($user->nombres ?? '').' '.($user->apellido_paterno ?? '').' '.($user->apellido_materno ?? '')),
                'ci' => $user->ci,
                'fecha_nacimiento' => $user->fecha_nacimiento,
                'role' => $user->role ?? null,
                'celular' => $user->celular ?? null,
            ],
            'jerarquia' => [
                'jefes' => $jefes,
                'supervisor' => $supervisores,
            ],
            'mesas' => $mesas,
            'partidos' => $partidos,
        ]);
    }

    public function me(Request $request)
    {
        $u = $request->user();
        return response()->json([
            'user' => [
                'id' => $u->id,
                'name' => $u->name ?? trim(($u->nombres ?? '').' '.($u->apellido_paterno ?? '').' '.($u->apellido_materno ?? '')),
                'ci' => $u->ci,
                'role' => $u->role ?? null,
                'celular' => $u->celular ?? null,
            ],
        ]);
    }
}
