<?php

namespace App\Http\Controllers;

use App\Models\Mesa;
use App\Models\Partido;
use App\Models\ResultadoMesa;
use App\Models\ResultadoMesaDetalle;
use App\Services\SocketEmitter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class MobileResultadosController extends Controller
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

    private array $asistenciaFields = [
        'aviso_antes',
        'aviso_manana',
        'aviso_mediodia',
        'aviso_tarde',
        'hora_apertura_mesa',
    ];

    public function asistencia(Request $request)
    {
        $user = $request->user();
        $mesas = Mesa::query()
            ->where('delegado_id', $user->id)
            ->pluck('id');

        if ($mesas->isEmpty()) {
            return response()->json([
                'mesas' => 0,
                'state' => [
                    'aviso_antes' => false,
                    'aviso_manana' => false,
                    'aviso_mediodia' => false,
                    'aviso_tarde' => false,
                    'hora_apertura_mesa' => null,
                    'etapa_1' => null,
                    'etapa_2' => null,
                ],
            ]);
        }

        $rows = ResultadoMesa::query()
            ->whereIn('mesa_id', $mesas)
            ->get($this->asistenciaFields);

        $state = [];
        foreach ($this->asistenciaFields as $f) {
            if ($rows->isEmpty()) {
                $state[$f] = $f === 'hora_apertura_mesa' ? null : false;
                continue;
            }
            $allTrue = $rows->every(fn ($r) => (bool) ($r->{$f} ?? false));
            $state[$f] = $f === 'hora_apertura_mesa'
                ? $rows->pluck('hora_apertura_mesa')->filter()->first()
                : $allTrue;
        }
        $state['etapa_1'] = null;
        $state['etapa_2'] = null;

        return response()->json([
            'mesas' => $mesas->count(),
            'state' => $state,
        ]);
    }

    public function asistenciaUpdate(Request $request)
    {
        $data = $request->validate([
            'field' => 'required|string|in:aviso_antes,aviso_manana,aviso_mediodia,aviso_tarde',
            'value' => 'required|boolean',
            'hora_apertura_mesa' => 'nullable|string|max:5',
        ]);

        if (($data['field'] ?? null) === 'aviso_manana' && (bool) ($data['value'] ?? false)) {
            if (!$this->isHoraAperturaValida($data['hora_apertura_mesa'] ?? null)) {
                return response()->json([
                    'message' => 'La hora de apertura debe estar entre 08:00 y 04:00',
                ], 422);
            }
        }

        $user = $request->user();
        $mesas = Mesa::query()
            ->where('delegado_id', $user->id)
            ->get(['id', 'delegado_id', 'estado']);

        if ($mesas->isEmpty()) {
            return response()->json(['message' => 'No hay mesas asignadas al delegado'], 422);
        }

        // Regla irreversible: una vez que un campo pasa a true, no puede volver a false.
        if ((bool) $data['value'] === false) {
            $alreadyTrue = ResultadoMesa::query()
                ->whereIn('mesa_id', $mesas->pluck('id'))
                ->where($data['field'], true)
                ->exists();
            if ($alreadyTrue) {
                return response()->json([
                    'message' => 'Este campo ya fue confirmado y no puede revertirse',
                ], 422);
            }
        }

        DB::transaction(function () use ($mesas, $user, $data) {
            foreach ($mesas as $mesa) {
                $rm = ResultadoMesa::updateOrCreate(
                    ['mesa_id' => $mesa->id],
                    ['registrado_por' => $user->id]
                );

                $rm->{$data['field']} = (bool) $data['value'];
                if (($data['field'] ?? null) === 'aviso_manana') {
                    $rm->hora_apertura_mesa = $data['hora_apertura_mesa'] ?? null;
                }
                $rm->etapa_1 = null;
                $rm->etapa_2 = null;
                $rm->registrado_por = $user->id;
                $rm->save();

                if (
                    (bool) $rm->aviso_antes ||
                    (bool) $rm->aviso_manana ||
                    (bool) $rm->aviso_mediodia ||
                    (bool) $rm->aviso_tarde
                ) {
                    $mesa->estado = 'EN_PROCESO';
                } else {
                    $mesa->estado = $mesa->delegado_id ? 'ASIGNADA' : 'PENDIENTE';
                }
                $mesa->save();
            }
        });

        $labels = [
            'aviso_antes' => 'Estoy presente en mi mesa',
            'aviso_manana' => 'Abrí la mesa',
            'aviso_mediodia' => 'Tengo el acta de la alcaldia en mi poder',
            'aviso_tarde' => 'Tengo el acta de la gobernacion en mi poder',
        ];
        $field = $data['field'];
        $message = trim(sprintf(
            '%s actualizó "%s" en %d mesa(s)%s',
            $user->name ?? 'Usuario',
            $labels[$field] ?? $field,
            $mesas->count(),
            (!empty($data['hora_apertura_mesa']) && $field === 'aviso_manana')
                ? (' · Hora: ' . $data['hora_apertura_mesa'])
                : ''
        ));

        SocketEmitter::votacion([
            'title' => 'Nuevo dato registrado',
            'message' => $message,
            'kind' => 'asistencia',
            'field' => $field,
            'field_label' => $labels[$field] ?? $field,
            'value' => (bool) $data['value'],
            'hora_apertura_mesa' => $data['hora_apertura_mesa'] ?? null,
            'updated_mesas' => $mesas->count(),
            'user_id' => $user->id ?? null,
            'user_name' => $user->name ?? null,
            'username' => $user->username ?? null,
        ]);

        return response()->json([
            'ok' => true,
            'field' => $data['field'],
            'value' => (bool) $data['value'],
            'hora_apertura_mesa' => $data['hora_apertura_mesa'] ?? null,
            'updated_mesas' => $mesas->count(),
        ]);
    }

    private function isHoraAperturaValida(?string $hora): bool
    {
        if ($hora === null || $hora === '') {
            return false;
        }

        $dt = \DateTime::createFromFormat('H:i', $hora);
        if (!$dt || $dt->format('H:i') !== $hora) {
            return false;
        }

        $h = (int) $dt->format('G');
        return $h >= 8 || $h <= 4;
    }

    public function votacionCatalogo(Request $request)
    {
        $user = $request->user();

        $mesasCollection = Mesa::query()
            ->where('delegado_id', $user->id)
            ->with([
                'recinto:id,nombre',
                'resultado:id,mesa_id,total_validos,total_blancos,total_nulos,etapa_2',
            ])
            ->orderBy('numero_mesa')
            ->get();

        $mesas = $mesasCollection
            ->map(function ($m) {
                return [
                    'id' => $m->id,
                    'numero_mesa' => $m->numero_mesa,
                    'estado' => $m->estado,
                    'recinto_nombre' => $m->recinto?->nombre,
                    'tiene_resultado' => (bool) $m->resultado,
                    'finalizada' => (bool) optional($m->resultado)->etapa_2,
                ];
            })
            ->values();

        $partidos = $this->partidosPorMesa($mesasCollection->first())
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
                    'habilitado_gobernador' => (bool) ($p->habilitado_gobernador ?? true),
                    'habilitado_asambleista_poblacion' => (bool) ($p->habilitado_asambleista_poblacion ?? true),
                    'habilitado_asambleista_distrito' => (bool) ($p->habilitado_asambleista_distrito ?? true),
                    'habilitado_concejal' => (bool) ($p->habilitado_concejal ?? true),
                    'habilitado_alcalde' => (bool) ($p->habilitado_alcalde ?? true),
                ];
            })
            ->values();

        return response()->json([
            'mesas' => $mesas,
            'partidos' => $partidos,
        ]);
    }

    public function votacionMesa(Request $request, Mesa $mesa)
    {
        $user = $request->user();
        if ((int) $mesa->delegado_id !== (int) $user->id) {
            return response()->json(['message' => 'Mesa no asignada al usuario'], 403);
        }

        $resultado = ResultadoMesa::query()
            ->with('detalles')
            ->where('mesa_id', $mesa->id)
            ->first();

        if ($resultado) {
            $resultado->foto1_url = $resultado->foto1 ? Storage::url($resultado->foto1) : null;
            $resultado->foto2_url = $resultado->foto2 ? Storage::url($resultado->foto2) : null;
            $resultado->foto3_url = $resultado->foto3 ? Storage::url($resultado->foto3) : null;
            $resultado->foto4_url = $resultado->foto4 ? Storage::url($resultado->foto4) : null;
            $resultado->foto5_url = $resultado->foto5 ? Storage::url($resultado->foto5) : null;
            $resultado->foto6_url = $resultado->foto6 ? Storage::url($resultado->foto6) : null;
            $resultado->foto7_url = $resultado->foto7 ? Storage::url($resultado->foto7) : null;
            $resultado->foto8_url = $resultado->foto8 ? Storage::url($resultado->foto8) : null;
            $resultado->foto9_url = $resultado->foto9 ? Storage::url($resultado->foto9) : null;
            $resultado->foto10_url = $resultado->foto10 ? Storage::url($resultado->foto10) : null;
        }

        $partidos = $this->partidosPorMesa($mesa)
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
                    'habilitado_gobernador' => (bool) ($p->habilitado_gobernador ?? true),
                    'habilitado_asambleista_poblacion' => (bool) ($p->habilitado_asambleista_poblacion ?? true),
                    'habilitado_asambleista_distrito' => (bool) ($p->habilitado_asambleista_distrito ?? true),
                    'habilitado_concejal' => (bool) ($p->habilitado_concejal ?? true),
                    'habilitado_alcalde' => (bool) ($p->habilitado_alcalde ?? true),
                ];
            })
            ->values();

        return response()->json([
            'mesa_id' => $mesa->id,
            'resultado' => $resultado,
            'partidos' => $partidos,
        ]);
    }

    public function votacionGuardar(Request $request, Mesa $mesa)
    {
        $user = $request->user();
        if ((int) $mesa->delegado_id !== (int) $user->id) {
            return response()->json(['message' => 'Mesa no asignada al usuario'], 403);
        }

        $data = $request->validate([
            'finalizar' => 'nullable|boolean',
            'observacion' => 'nullable|string',
            'observacion_gobernador' => 'nullable|string',
            'observacion_asambleista_distrito' => 'nullable|string',
            'observacion_asambleista_poblacion' => 'nullable|string',
            'observacion_concejal' => 'nullable|string',
            'observacion_alcalde' => 'nullable|string',
            'votos' => 'required',

            'blancos_gobernador' => 'nullable|integer|min:0',
            'nulos_gobernador' => 'nullable|integer|min:0',
            'papeletas_no_utilizadas_gobernador' => 'nullable|integer|min:0',
            'blancos_asambleista_distrito' => 'nullable|integer|min:0',
            'nulos_asambleista_distrito' => 'nullable|integer|min:0',
            'papeletas_no_utilizadas_asambleista_distrito' => 'nullable|integer|min:0',
            'blancos_asambleista_poblacion' => 'nullable|integer|min:0',
            'nulos_asambleista_poblacion' => 'nullable|integer|min:0',
            'papeletas_no_utilizadas_asambleista_poblacion' => 'nullable|integer|min:0',
            'blancos_concejal' => 'nullable|integer|min:0',
            'nulos_concejal' => 'nullable|integer|min:0',
            'papeletas_no_utilizadas_concejal' => 'nullable|integer|min:0',
            'blancos_alcalde' => 'nullable|integer|min:0',
            'nulos_alcalde' => 'nullable|integer|min:0',
            'papeletas_no_utilizadas_alcalde' => 'nullable|integer|min:0',

            'foto1' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096',
            'foto2' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096',
            'foto3' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096',
            'foto4' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096',
            'foto5' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096',
            'foto6' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096',
            'foto7' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096',
            'foto8' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096',
            'foto9' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096',
            'foto10' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096',
        ]);

        $votos = $request->input('votos');
        if (is_string($votos)) {
            $votos = json_decode($votos, true);
        }
        if (!is_array($votos)) {
            return response()->json(['message' => 'Formato de votos invalido'], 422);
        }

        $partidosPermitidos = $this->partidosPorMesa($mesa)->pluck('id')->map(fn ($id) => (int) $id)->values();

        $sum = [
            'gobernador' => 0,
            'asambleista_distrito' => 0,
            'asambleista_poblacion' => 0,
            'concejal' => 0,
            'alcalde' => 0,
        ];

        foreach ($votos as $row) {
            $pid = (int) ($row['partido_id'] ?? 0);
            if (!$partidosPermitidos->contains($pid)) {
                return response()->json(['message' => "Partido no habilitado para esta mesa: {$pid}"], 422);
            }
            foreach ([
                'votos_gobernador',
                'votos_asambleista_distrito',
                'votos_asambleista_poblacion',
                'votos_concejal',
                'votos_alcalde'
            ] as $k) {
                if (!isset($row[$k]) || (int) $row[$k] < 0) {
                    return response()->json(['message' => "Voto invalido en {$k}"], 422);
                }
            }

            $sum['gobernador'] += (int) $row['votos_gobernador'];
            $sum['asambleista_distrito'] += (int) $row['votos_asambleista_distrito'];
            $sum['asambleista_poblacion'] += (int) $row['votos_asambleista_poblacion'];
            $sum['concejal'] += (int) $row['votos_concejal'];
            $sum['alcalde'] += (int) $row['votos_alcalde'];
        }

        $blancos = [
            'gobernador' => (int) ($data['blancos_gobernador'] ?? 0),
            'asambleista_distrito' => (int) ($data['blancos_asambleista_distrito'] ?? 0),
            'asambleista_poblacion' => (int) ($data['blancos_asambleista_poblacion'] ?? 0),
            'concejal' => (int) ($data['blancos_concejal'] ?? 0),
            'alcalde' => (int) ($data['blancos_alcalde'] ?? 0),
        ];
        $nulos = [
            'gobernador' => (int) ($data['nulos_gobernador'] ?? 0),
            'asambleista_distrito' => (int) ($data['nulos_asambleista_distrito'] ?? 0),
            'asambleista_poblacion' => (int) ($data['nulos_asambleista_poblacion'] ?? 0),
            'concejal' => (int) ($data['nulos_concejal'] ?? 0),
            'alcalde' => (int) ($data['nulos_alcalde'] ?? 0),
        ];
        $pnu = [
            'gobernador' => (int) ($data['papeletas_no_utilizadas_gobernador'] ?? 0),
            'asambleista_distrito' => (int) ($data['papeletas_no_utilizadas_asambleista_distrito'] ?? 0),
            'asambleista_poblacion' => (int) ($data['papeletas_no_utilizadas_asambleista_poblacion'] ?? 0),
            'concejal' => (int) ($data['papeletas_no_utilizadas_concejal'] ?? 0),
            'alcalde' => (int) ($data['papeletas_no_utilizadas_alcalde'] ?? 0),
        ];

        $finalizar = (bool) $request->boolean('finalizar');
        $hayVotosOCifras = (array_sum($sum) + array_sum($blancos) + array_sum($nulos) + array_sum($pnu)) > 0;

        $finalizadaReal = DB::transaction(function () use ($mesa, $user, $data, $votos, $sum, $blancos, $nulos, $pnu, $finalizar, $hayVotosOCifras, $request) {
            $rm = ResultadoMesa::updateOrCreate(
                ['mesa_id' => $mesa->id],
                [
                    'registrado_por' => $user->id,
                    'observacion' => $data['observacion'] ?? null,
                    'observacion_gobernador' => $data['observacion_gobernador'] ?? null,
                    'observacion_asambleista_distrito' => $data['observacion_asambleista_distrito'] ?? null,
                    'observacion_asambleista_poblacion' => $data['observacion_asambleista_poblacion'] ?? null,
                    'observacion_concejal' => $data['observacion_concejal'] ?? null,
                    'observacion_alcalde' => $data['observacion_alcalde'] ?? null,

                    'etapa_1' => true,
                    'etapa_2' => false,

                    'blancos_gobernador' => $blancos['gobernador'],
                    'nulos_gobernador' => $nulos['gobernador'],
                    'papeletas_no_utilizadas_gobernador' => $pnu['gobernador'],
                    'blancos_asambleista_distrito' => $blancos['asambleista_distrito'],
                    'nulos_asambleista_distrito' => $nulos['asambleista_distrito'],
                    'papeletas_no_utilizadas_asambleista_distrito' => $pnu['asambleista_distrito'],
                    'blancos_asambleista_poblacion' => $blancos['asambleista_poblacion'],
                    'nulos_asambleista_poblacion' => $nulos['asambleista_poblacion'],
                    'papeletas_no_utilizadas_asambleista_poblacion' => $pnu['asambleista_poblacion'],
                    'blancos_concejal' => $blancos['concejal'],
                    'nulos_concejal' => $nulos['concejal'],
                    'papeletas_no_utilizadas_concejal' => $pnu['concejal'],
                    'blancos_alcalde' => $blancos['alcalde'],
                    'nulos_alcalde' => $nulos['alcalde'],
                    'papeletas_no_utilizadas_alcalde' => $pnu['alcalde'],

                    'total_validos' => array_sum($sum),
                    'total_blancos' => array_sum($blancos),
                    'total_nulos' => array_sum($nulos) + array_sum($pnu),
                    'total_votos' => array_sum($sum),
                ]
            );

            $dir = "resultados_mesa/mesa_{$mesa->id}";
            foreach (['foto1', 'foto2', 'foto3', 'foto4', 'foto5', 'foto6', 'foto7', 'foto8', 'foto9', 'foto10'] as $f) {
                if ($request->hasFile($f)) {
                    if (!empty($rm->{$f})) {
                        Storage::disk('public')->delete($rm->{$f});
                    }
                    $rm->{$f} = $request->file($f)->store($dir, 'public');
                }
            }
            $puedeFinalizar = $finalizar && $hayVotosOCifras && $this->tieneFotosMinimasFinalizacion($rm);
            $rm->etapa_2 = $puedeFinalizar;
            $rm->save();

            $partidosEnviados = collect($votos)->pluck('partido_id')->map(fn ($id) => (int) $id)->values();

            ResultadoMesaDetalle::query()
                ->where('resultado_mesa_id', $rm->id)
                ->whereNotIn('partido_id', $partidosEnviados)
                ->delete();

            foreach ($votos as $row) {
                ResultadoMesaDetalle::updateOrCreate(
                    [
                        'resultado_mesa_id' => $rm->id,
                        'partido_id' => (int) $row['partido_id'],
                    ],
                    [
                        'votos_gobernador' => (int) $row['votos_gobernador'],
                        'votos_asambleista_distrito' => (int) $row['votos_asambleista_distrito'],
                        'votos_asambleista_poblacion' => (int) $row['votos_asambleista_poblacion'],
                        'votos_concejal' => (int) $row['votos_concejal'],
                        'votos_alcalde' => (int) $row['votos_alcalde'],
                    ]
                );
            }

            $mesa->estado = $puedeFinalizar ? 'FINALIZADA' : 'EN_PROCESO';
            $mesa->save();
            return $puedeFinalizar;
        });

        SocketEmitter::votacion([
            'title' => 'Nuevo dato registrado',
            'message' => trim(sprintf(
                '%s registró votación en Mesa %s%s',
                $user->name ?? 'Usuario',
                $mesa->numero_mesa,
                $finalizadaReal ? ' · Finalizada' : ''
            )),
            'kind' => 'resultado_mobile',
            'mesa_id' => $mesa->id,
            'mesa_numero' => $mesa->numero_mesa,
            'estado' => $finalizadaReal ? 'FINALIZADA' : 'EN_PROCESO',
            'finalizada' => $finalizadaReal,
            'user_id' => $user->id ?? null,
            'user_name' => $user->name ?? null,
            'username' => $user->username ?? null,
            'total_validos' => array_sum($sum),
            'total_blancos' => array_sum($blancos),
            'total_nulos' => array_sum($nulos) + array_sum($pnu),
        ]);

        return response()->json([
            'ok' => true,
            'mesa_id' => $mesa->id,
            'finalizada' => $finalizadaReal,
        ]);
    }

    private function tieneFotosMinimasFinalizacion(ResultadoMesa $resultado): bool
    {
        foreach (['foto1', 'foto2', 'foto3', 'foto4'] as $slot) {
            if (empty($resultado->{$slot})) {
                return false;
            }
        }
        return true;
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

    public function sync(Request $request)
    {
        $data = $request->validate([
            'mesa_id' => 'required|integer|exists:mesas,id',
            'payload' => 'required|array',
            'payload.total_votos' => 'required|integer|min:0',
            'payload.total_validos' => 'required|integer|min:0',
            'payload.total_blancos' => 'required|integer|min:0',
            'payload.total_nulos' => 'required|integer|min:0',
            'payload.observacion' => 'nullable|string',
            'payload.observacion_gobernador' => 'nullable|string',
            'payload.observacion_asambleista_distrito' => 'nullable|string',
            'payload.observacion_asambleista_poblacion' => 'nullable|string',
            'payload.observacion_concejal' => 'nullable|string',
            'payload.observacion_alcalde' => 'nullable|string',
            'payload.latitud' => 'nullable|numeric',
            'payload.longitud' => 'nullable|numeric',
            'payload.detalles' => 'required|array',
            'payload.detalles.*.partido_id' => 'required|integer|exists:partidos,id',
            'payload.detalles.*.votos_gobernador' => 'required|integer|min:0',
            'payload.detalles.*.votos_asambleista_distrito' => 'required|integer|min:0',
            'payload.detalles.*.votos_asambleista_poblacion' => 'required|integer|min:0',
            'payload.detalles.*.votos_concejal' => 'required|integer|min:0',
            'payload.detalles.*.votos_alcalde' => 'required|integer|min:0',
        ]);

        $user = $request->user();

        DB::transaction(function () use ($data, $user) {
            $rm = ResultadoMesa::updateOrCreate(
                ['mesa_id' => $data['mesa_id']],
                [
                    'registrado_por' => $user->id,
                    'total_votos' => $data['payload']['total_votos'],
                    'total_validos' => $data['payload']['total_validos'],
                    'total_blancos' => $data['payload']['total_blancos'],
                    'total_nulos' => $data['payload']['total_nulos'],
                    'observacion' => $data['payload']['observacion'] ?? null,
                    'observacion_gobernador' => $data['payload']['observacion_gobernador'] ?? null,
                    'observacion_asambleista_distrito' => $data['payload']['observacion_asambleista_distrito'] ?? null,
                    'observacion_asambleista_poblacion' => $data['payload']['observacion_asambleista_poblacion'] ?? null,
                    'observacion_concejal' => $data['payload']['observacion_concejal'] ?? null,
                    'observacion_alcalde' => $data['payload']['observacion_alcalde'] ?? null,
                    'latitud' => $data['payload']['latitud'] ?? null,
                    'longitud' => $data['payload']['longitud'] ?? null,
                ]
            );

            foreach ($data['payload']['detalles'] as $d) {
                ResultadoMesaDetalle::updateOrCreate(
                    [
                        'resultado_mesa_id' => $rm->id,
                        'partido_id' => $d['partido_id'],
                    ],
                    [
                        'votos_gobernador' => $d['votos_gobernador'],
                        'votos_asambleista_distrito' => $d['votos_asambleista_distrito'],
                        'votos_asambleista_poblacion' => $d['votos_asambleista_poblacion'],
                        'votos_concejal' => $d['votos_concejal'],
                        'votos_alcalde' => $d['votos_alcalde'],
                    ]
                );
            }
        });

        SocketEmitter::votacion([
            'title' => 'Nuevo dato sincronizado',
            'message' => trim(sprintf(
                '%s sincronizó datos offline en mesa %s',
                $user->name ?? 'Usuario',
                $data['mesa_id']
            )),
            'kind' => 'sync_offline',
            'mesa_id' => (int) $data['mesa_id'],
            'user_id' => $user->id ?? null,
            'user_name' => $user->name ?? null,
            'username' => $user->username ?? null,
            'total_validos' => (int) ($data['payload']['total_validos'] ?? 0),
            'total_blancos' => (int) ($data['payload']['total_blancos'] ?? 0),
            'total_nulos' => (int) ($data['payload']['total_nulos'] ?? 0),
        ]);

        return response()->json(['ok' => true]);
    }
}
