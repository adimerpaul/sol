<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Mesa;
use App\Models\Partido;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MobileAuthController extends Controller
{
    private const PARTIDOS_SEGUNDA_VUELTA = [11, 15];

    private function imagePathToBase64(?string $relativePath): ?string
    {
        if (empty($relativePath)) {
            return null;
        }

        $path = storage_path('app/public/' . ltrim($relativePath, '/'));
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

        $maxWidth = 1280;
        $srcW = imagesx($source);
        $srcH = imagesy($source);
        if ($srcW <= 0 || $srcH <= 0) {
            imagedestroy($source);
            return null;
        }

        $targetW = $srcW > $maxWidth ? $maxWidth : $srcW;
        $targetH = (int) round(($srcH * $targetW) / $srcW);
        $target = imagecreatetruecolor($targetW, $targetH);
        if ($target === false) {
            imagedestroy($source);
            return null;
        }

        imagecopyresampled($target, $source, 0, 0, 0, 0, $targetW, $targetH, $srcW, $srcH);

        ob_start();
        imagejpeg($target, null, 65);
        $jpg = ob_get_clean();

        imagedestroy($target);
        imagedestroy($source);

        if ($jpg === false || $jpg === '') {
            return null;
        }

        return 'data:image/jpeg;base64,' . base64_encode($jpg);
    }

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
        error_log("Login attempt - CI: {$data['ci']}, Fecha Nacimiento: {$fecha}");

        $user = User::query()
            ->where('username', $data['ci'])
            ->whereDate('fecha_nacimiento', $fecha)
            ->first();

        if (!$user) {
            return response()->json(['message' => 'CI o fecha de nacimiento incorrectos'], 401);
        }

        // ✅ Mesa asignada pendiente (la más simple)
        $mesas = Mesa::query()
            ->where('delegado_segunda_vuelta_id', $user->id)
            ->with([
                'recinto:id,nombre,latitud,longitud',
                'localidad:id,nombre',
                'municipio:id,nombre',
                'provincia:id,nombre',
                'departamento:id,nombre',
                'resultadoSegundaVuelta:id,mesa_id,observacion,blancos,nulos,papeletas_no_utilizadas,foto_pizarra,foto_acta',
                'resultadoSegundaVuelta.detalles:id,resultado_mesa_segunda_vuelta_id,partido_id,votos_gobernador',
            ])
            ->orderBy('numero_mesa')
            ->orderBy('id')
            ->get()
            ->map(function (Mesa $mesa) {
                $arr = $mesa->toArray();
                $arr['habilitados'] = (int) ($mesa->habilitados ?? 260);
                $r = $mesa->resultadoSegundaVuelta;

                if ($r) {
                    $resultado = [
                        'etapa_1' => true,
                        'etapa_2' => true,
                        'observacion' => $r->observacion,
                        'observacion_gobernador' => $r->observacion,
                        'observacion_asambleista_distrito' => null,
                        'observacion_asambleista_poblacion' => null,
                        'observacion_concejal' => null,
                        'observacion_alcalde' => null,
                        'blancos_gobernador' => (int) ($r->blancos ?? 0),
                        'nulos_gobernador' => (int) ($r->nulos ?? 0),
                        'papeletas_no_utilizadas_gobernador' => (int) ($r->papeletas_no_utilizadas ?? 0),
                        'blancos_asambleista_distrito' => 0,
                        'nulos_asambleista_distrito' => 0,
                        'papeletas_no_utilizadas_asambleista_distrito' => 0,
                        'blancos_asambleista_poblacion' => 0,
                        'nulos_asambleista_poblacion' => 0,
                        'papeletas_no_utilizadas_asambleista_poblacion' => 0,
                        'blancos_concejal' => 0,
                        'nulos_concejal' => 0,
                        'papeletas_no_utilizadas_concejal' => 0,
                        'blancos_alcalde' => 0,
                        'nulos_alcalde' => 0,
                        'papeletas_no_utilizadas_alcalde' => 0,
                        'detalles' => $r->detalles
                            ->map(fn ($d) => [
                                'partido_id' => (int) $d->partido_id,
                                'votos_gobernador' => (int) ($d->votos_gobernador ?? 0),
                                'votos_asambleista_distrito' => 0,
                                'votos_asambleista_poblacion' => 0,
                                'votos_concejal' => 0,
                                'votos_alcalde' => 0,
                            ])
                            ->values(),
                    ];

                    foreach (['foto1', 'foto2', 'foto3', 'foto4', 'foto7', 'foto8', 'foto9', 'foto10'] as $slot) {
                        $resultado[$slot . '_base64'] = null;
                    }
                    $resultado['foto5_base64'] = $this->imagePathToBase64($r->foto_pizarra);
                    $resultado['foto6_base64'] = $this->imagePathToBase64($r->foto_acta);

                    $arr['resultado'] = $resultado;
                } else {
                    $arr['resultado'] = null;
                }

                return $arr;
            })
            ->values();

        $mesaReferencia = Mesa::query()
            ->where('delegado_segunda_vuelta_id', $user->id)
            ->orderBy('numero_mesa')
            ->first();

        $partidos = $this->partidosSegundaVuelta()
            ->map(function ($p) {
                $iconoBase64 = $this->partidoIconoBase64($p->icono);
                return [
                    'id' => $p->id,
                    'sigla' => $p->sigla,
                    'nombre' => $p->nombre,
                    'color' => $p->color,
                    'icono' => $p->icono,
                    'icono_url' => null,
                    'icono_base64' => $iconoBase64,
                    'orden_municipal' => (int) ($p->orden_municipal ?? 0),
                    'orden_departamental' => (int) ($p->orden_departamental ?? 0),
                    'habilitado_gobernador' => true,
                    'habilitado_asambleista_poblacion' => false,
                    'habilitado_asambleista_distrito' => false,
                    'habilitado_concejal' => false,
                    'habilitado_alcalde' => false,
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

    private function partidosPorMesa(?Mesa $mesa)
    {
        $municipioId = $mesa?->municipio_id ?: $mesa?->recinto?->municipio_id;

        $baseQuery = Partido::query()
            ->whereNull('deleted_at')
            ->orderByRaw('CASE WHEN orden_municipal IS NULL OR orden_municipal = 0 THEN 1 ELSE 0 END')
            ->orderBy('orden_municipal')
            ->orderBy('sigla');

        if (!$municipioId) {
            return $baseQuery
                ->select([
                    'id',
                    'sigla',
                    'nombre',
                    'color',
                    'icono',
                    'orden_municipal',
                    'orden_departamental',
                    DB::raw('1 as habilitado_gobernador'),
                    DB::raw('1 as habilitado_asambleista_poblacion'),
                    DB::raw('1 as habilitado_asambleista_distrito'),
                    DB::raw('1 as habilitado_concejal'),
                    DB::raw('1 as habilitado_alcalde'),
                ])
                ->get();
        }

        $tieneConfig = DB::table('municipio_partido')
            ->where('municipio_id', $municipioId)
            ->exists();

        if (!$tieneConfig) {
            return $baseQuery
                ->select([
                    'id',
                    'sigla',
                    'nombre',
                    'color',
                    'icono',
                    'orden_municipal',
                    'orden_departamental',
                    DB::raw('1 as habilitado_gobernador'),
                    DB::raw('1 as habilitado_asambleista_poblacion'),
                    DB::raw('1 as habilitado_asambleista_distrito'),
                    DB::raw('1 as habilitado_concejal'),
                    DB::raw('1 as habilitado_alcalde'),
                ])
                ->get();
        }

        return Partido::query()
            ->join('municipio_partido as mp', function ($join) use ($municipioId) {
                $join->on('mp.partido_id', '=', 'partidos.id')
                    ->where('mp.municipio_id', '=', $municipioId);
            })
            ->whereNull('partidos.deleted_at')
            ->select([
                'partidos.id',
                'partidos.sigla',
                'partidos.nombre',
                'partidos.color',
                'partidos.icono',
                'partidos.orden_municipal',
                'partidos.orden_departamental',
                'mp.habilitado_gobernador',
                'mp.habilitado_asambleista_poblacion',
                'mp.habilitado_asambleista_distrito',
                'mp.habilitado_concejal',
                'mp.habilitado_alcalde',
            ])
            ->where(function ($qq) {
                $qq->where('mp.habilitado_gobernador', true)
                    ->orWhere('mp.habilitado_asambleista_poblacion', true)
                    ->orWhere('mp.habilitado_asambleista_distrito', true)
                    ->orWhere('mp.habilitado_concejal', true)
                    ->orWhere('mp.habilitado_alcalde', true);
            })
            ->orderByRaw('CASE WHEN partidos.orden_municipal IS NULL OR partidos.orden_municipal = 0 THEN 1 ELSE 0 END')
            ->orderBy('partidos.orden_municipal')
            ->orderBy('partidos.sigla')
            ->get();
    }

    private function partidosSegundaVuelta()
    {
        return Partido::query()
            ->whereNull('deleted_at')
            ->whereIn('id', self::PARTIDOS_SEGUNDA_VUELTA)
            ->select([
                'id',
                'sigla',
                'nombre',
                'color',
                'icono',
                DB::raw('0 as orden_municipal'),
                DB::raw('0 as orden_departamental'),
            ])
            ->orderByRaw('CASE id WHEN 11 THEN 1 WHEN 15 THEN 2 ELSE 99 END')
            ->get();
    }
}
