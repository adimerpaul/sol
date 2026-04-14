<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Mesa;
use App\Models\Partido;
use App\Models\Recinto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MobileAuthController extends Controller
{
    private const PARTIDOS_APP = [11, 15];

    private const PARTIDOS_SEGUNDA_VUELTA = [11, 15];

    private function buildJerarquiaFromRecintos($mesas): array
    {
        $recintoIds = collect($mesas)
            ->pluck('recinto_id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        if ($recintoIds->isEmpty()) {
            return [
                'jefes' => collect(),
                'supervisores' => collect(),
            ];
        }

        $recintos = Recinto::query()
            ->whereIn('id', $recintoIds)
            ->with([
                'jefe:id,name,nombres,username,celular',
                'jefe.supervisores:id,name,nombres,username,celular',
            ])
            ->get();

        $jefes = $recintos
            ->flatMap(fn ($recinto) => collect($recinto->jefe ?? []))
            ->unique('id')
            ->sortBy('name')
            ->values()
            ->map(function ($jefe) {
                $supervisores = collect($jefe->supervisores ?? [])
                    ->unique('id')
                    ->sortBy('name')
                    ->values()
                    ->map(fn ($supervisor) => [
                        'id' => $supervisor->id,
                        'name' => $supervisor->name,
                        'nombres' => $supervisor->nombres,
                        'celular' => $supervisor->celular,
                    ])
                    ->values();

                return [
                    'id' => $jefe->id,
                    'name' => $jefe->name,
                    'nombres' => $jefe->nombres,
                    'celular' => $jefe->celular,
                    'supervisores' => $supervisores,
                ];
            })
            ->values();

        $supervisores = $jefes
            ->flatMap(fn ($jefe) => $jefe['supervisores'])
            ->unique('id')
            ->sortBy('name')
            ->values();

        return [
            'jefes' => $jefes,
            'supervisores' => $supervisores,
        ];
    }

    private function buildAsistenciaPanel(User $user): array
    {
        $jefeRecintoIds = $user->recintosComoJefe()
            ->pluck('recintos.id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        if ($jefeRecintoIds->isNotEmpty()) {
            $recintosQuery = Recinto::query()
                ->whereIn('id', $jefeRecintoIds);
        } else {
            $recintosQuery = match ($user->role) {
                'Administrador' => Recinto::query()
                    ->where('pais_id', 1)
                    ->where('departamento_id', 5),
                'Supervisor' => Recinto::query()
                    ->whereHas('users', fn ($q) => $q->where('users.id', $user->id)),
                default => null,
            };
        }

        if (!$recintosQuery) {
            return [
                'titulares' => [],
                'suplentes' => [],
            ];
        }

        $recintos = $recintosQuery
            ->with([
                'mesas:id,recinto_id,numero_mesa,delegado_id',
                'mesas.recinto:id,nombre',
                'mesas.delegado:id,name,nombres,apellido_paterno,apellido_materno,username,celular',
                'mesas.asistencia:id,mesa_id,aviso_antes,aviso_manana,aviso_mediodia,aviso_tarde',
            ])
            ->get();

        $titulares = $recintos
            ->flatMap(function ($recinto) {
                return collect($recinto->mesas ?? [])
                    ->filter(fn ($mesa) => !empty($mesa->delegado))
                    ->map(function ($mesa) {
                        $delegado = $mesa->delegado;
                        $asistencia = $mesa->asistencia;
                        $name = $delegado->name
                            ?? trim(
                                ($delegado->nombres ?? '') . ' ' .
                                ($delegado->apellido_paterno ?? '') . ' ' .
                                ($delegado->apellido_materno ?? '')
                            );

                        return [
                            'mesa_id' => $mesa->id,
                            'numero_mesa' => $mesa->numero_mesa,
                            'recinto_nombre' => $mesa->recinto?->nombre ?? $recinto->nombre,
                            'delegado_id' => $delegado->id,
                            'name' => trim((string) $name),
                            'celular' => $delegado->celular,
                            'aviso_antes' => (bool) ($asistencia->aviso_antes ?? false),
                            'aviso_manana' => (bool) ($asistencia->aviso_manana ?? false),
                            'aviso_mediodia' => (bool) ($asistencia->aviso_mediodia ?? false),
                            'aviso_tarde' => (bool) ($asistencia->aviso_tarde ?? false),
                        ];
                    });
            })
            ->unique(fn ($item) => ($item['mesa_id'] ?? 0) . '-' . ($item['delegado_id'] ?? 0))
            ->sortBy('numero_mesa')
            ->values();

        $titularIds = $titulares
            ->pluck('delegado_id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $recintoIds = $recintos
            ->pluck('id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $suplentes = User::query()
            ->whereIn('recinto_id', $recintoIds)
            ->whereIn('role', ['Delegado de Mesa', 'Jefe de Recinto', 'Administrador'])
            ->when(
                $titularIds->isNotEmpty(),
                fn ($q) => $q->whereNotIn('id', $titularIds)
            )
            ->get([
                'id',
                'recinto_id',
                'name',
                'nombres',
                'apellido_paterno',
                'apellido_materno',
                'celular',
            ])
            ->map(function ($delegado) use ($recintos) {
                $name = $delegado->name
                    ?? trim(
                        ($delegado->nombres ?? '') . ' ' .
                        ($delegado->apellido_paterno ?? '') . ' ' .
                        ($delegado->apellido_materno ?? '')
                    );

                $recinto = $recintos->firstWhere('id', $delegado->recinto_id);

                return [
                    'mesa_id' => null,
                    'numero_mesa' => null,
                    'recinto_nombre' => $recinto?->nombre,
                    'delegado_id' => $delegado->id,
                    'name' => trim((string) $name),
                    'celular' => $delegado->celular,
                    'aviso_antes' => false,
                    'aviso_manana' => false,
                    'aviso_mediodia' => false,
                    'aviso_tarde' => false,
                ];
            })
            ->unique('delegado_id')
            ->sortBy(['recinto_nombre', 'name'])
            ->values();

        return [
            'titulares' => $titulares,
            'suplentes' => $suplentes,
        ];
    }

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

        // Mesas asignadas en flujo normal
        $mesas = Mesa::query()
            ->where('delegado_id', $user->id)
            ->with([
                'recinto:id,nombre,latitud,longitud',
                'localidad:id,nombre',
                'municipio:id,nombre',
                'provincia:id,nombre',
                'departamento:id,nombre',
                'resultado:id,mesa_id,observacion,observacion_gobernador,observacion_asambleista_distrito,observacion_asambleista_poblacion,observacion_concejal,observacion_alcalde,blancos_gobernador,nulos_gobernador,papeletas_no_utilizadas_gobernador,blancos_asambleista_distrito,nulos_asambleista_distrito,papeletas_no_utilizadas_asambleista_distrito,blancos_asambleista_poblacion,nulos_asambleista_poblacion,papeletas_no_utilizadas_asambleista_poblacion,blancos_concejal,nulos_concejal,papeletas_no_utilizadas_concejal,blancos_alcalde,nulos_alcalde,papeletas_no_utilizadas_alcalde,foto1,foto2,foto3,foto4,foto5,foto6,foto7,foto8,foto9,foto10,etapa_1,etapa_2',
                'resultado.detalles:id,resultado_mesa_id,partido_id,votos_gobernador,votos_asambleista_distrito,votos_asambleista_poblacion,votos_concejal,votos_alcalde',
            ])
            ->orderBy('numero_mesa')
            ->orderBy('id')
            ->get()
            ->map(function (Mesa $mesa) {
                $arr = $mesa->toArray();
                $arr['habilitados'] = (int) ($mesa->habilitados ?? 260);
                $r = $mesa->resultado;

                if ($r) {
                    $resultado = [
                        'etapa_1' => (bool) $r->etapa_1,
                        'etapa_2' => (bool) $r->etapa_2,
                        'observacion' => $r->observacion,
                        'observacion_gobernador' => $r->observacion_gobernador,
                        'observacion_asambleista_distrito' => $r->observacion_asambleista_distrito,
                        'observacion_asambleista_poblacion' => $r->observacion_asambleista_poblacion,
                        'observacion_concejal' => $r->observacion_concejal,
                        'observacion_alcalde' => $r->observacion_alcalde,
                        'blancos_gobernador' => (int) ($r->blancos_gobernador ?? 0),
                        'nulos_gobernador' => (int) ($r->nulos_gobernador ?? 0),
                        'papeletas_no_utilizadas_gobernador' => (int) ($r->papeletas_no_utilizadas_gobernador ?? 0),
                        'blancos_asambleista_distrito' => (int) ($r->blancos_asambleista_distrito ?? 0),
                        'nulos_asambleista_distrito' => (int) ($r->nulos_asambleista_distrito ?? 0),
                        'papeletas_no_utilizadas_asambleista_distrito' => (int) ($r->papeletas_no_utilizadas_asambleista_distrito ?? 0),
                        'blancos_asambleista_poblacion' => (int) ($r->blancos_asambleista_poblacion ?? 0),
                        'nulos_asambleista_poblacion' => (int) ($r->nulos_asambleista_poblacion ?? 0),
                        'papeletas_no_utilizadas_asambleista_poblacion' => (int) ($r->papeletas_no_utilizadas_asambleista_poblacion ?? 0),
                        'blancos_concejal' => (int) ($r->blancos_concejal ?? 0),
                        'nulos_concejal' => (int) ($r->nulos_concejal ?? 0),
                        'papeletas_no_utilizadas_concejal' => (int) ($r->papeletas_no_utilizadas_concejal ?? 0),
                        'blancos_alcalde' => (int) ($r->blancos_alcalde ?? 0),
                        'nulos_alcalde' => (int) ($r->nulos_alcalde ?? 0),
                        'papeletas_no_utilizadas_alcalde' => (int) ($r->papeletas_no_utilizadas_alcalde ?? 0),
                        'detalles' => $r->detalles
                            ->map(fn ($d) => [
                                'partido_id' => (int) $d->partido_id,
                                'votos_gobernador' => (int) ($d->votos_gobernador ?? 0),
                                'votos_asambleista_distrito' => (int) ($d->votos_asambleista_distrito ?? 0),
                                'votos_asambleista_poblacion' => (int) ($d->votos_asambleista_poblacion ?? 0),
                                'votos_concejal' => (int) ($d->votos_concejal ?? 0),
                                'votos_alcalde' => (int) ($d->votos_alcalde ?? 0),
                            ])
                            ->values(),
                    ];

                    foreach (['foto1', 'foto2', 'foto3', 'foto4', 'foto5', 'foto6', 'foto7', 'foto8', 'foto9', 'foto10'] as $slot) {
                        $resultado[$slot . '_base64'] = $this->imagePathToBase64($r->{$slot});
                    }

                    $arr['resultado'] = $resultado;
                } else {
                    $arr['resultado'] = null;
                }

                return $arr;
            })
            ->values();

        $mesaReferencia = Mesa::query()
            ->where('delegado_id', $user->id)
            ->orderBy('numero_mesa')
            ->first();

        $partidos = $this->partidosPorMesa($mesaReferencia)
            ->whereIn('id', self::PARTIDOS_APP)
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

        $jerarquia = $this->buildJerarquiaFromRecintos($mesas);
        $jefes = $jerarquia['jefes'];
        $supervisores = $jerarquia['supervisores'];
        $asistenciaPanel = $this->buildAsistenciaPanel($user);

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
            'asistencia_panel' => $asistenciaPanel,
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
            ->whereIn('partidos.id', self::PARTIDOS_APP)
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
                $qq->where('mp.habilitado_gobernador', true);
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
