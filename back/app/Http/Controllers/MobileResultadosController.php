<?php

namespace App\Http\Controllers;

use App\Models\Mesa;
use App\Models\Partido;
use App\Models\ResultadoMesa;
use App\Models\ResultadoMesaDetalle;
use App\Services\SocketEmitter;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class MobileResultadosController extends Controller
{
    private const PARTIDOS_APP = [11, 15];

    private array $asistenciaFields = [
        'aviso_antes',
        'aviso_manana',
        'aviso_mediodia',
        'aviso_tarde',
        'hora_apertura_mesa',
    ];

    private function resolveSocketCategoriasFromTotals(array $totals): array
    {
        $map = [
            'concejal' => 'Concejal',
            'gobernador' => 'Gobernador',
            'alcalde' => 'Alcalde',
            'asambleista_distrito' => 'Asambleista por distrito',
            'asambleista_poblacion' => 'Asambleista por poblacion',
        ];

        $categorias = [];

        foreach ($map as $field => $label) {
            if ((int) ($totals[$field] ?? 0) > 0) {
                $categorias[] = $label;
            }
        }

        return $categorias;
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
            'latitud' => 'nullable|numeric',
            'longitud' => 'nullable|numeric',
            'presente_at' => 'nullable|date',
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

                if (($data['field'] ?? null) === 'aviso_antes' && (bool) ($data['value'] ?? false)) {
                    if (array_key_exists('latitud', $data)) {
                        $mesa->delegado_latitud = isset($data['latitud'])
                            ? (string) $data['latitud']
                            : null;
                    }
                    if (array_key_exists('longitud', $data)) {
                        $mesa->delegado_longitud = isset($data['longitud'])
                            ? (string) $data['longitud']
                            : null;
                    }
                    $mesa->delegado_presente_at = !empty($data['presente_at'])
                        ? Carbon::parse($data['presente_at'])
                        : now();
                }

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
            'aviso_manana' => 'Abrio la mesa',
            'aviso_mediodia' => 'Tengo el acta de la alcaldia en mi poder',
            'aviso_tarde' => 'Tengo el acta de la gobernacion en mi poder',
        ];
        $field = $data['field'];

        $mesa->refresh();

        SocketEmitter::votacion([
            'title' => 'Nuevo dato registrado',
            'message' => trim(sprintf(
                '%s actualizo "%s" en %d mesa(s)%s',
                $user->name ?? 'Usuario',
                $labels[$field] ?? $field,
                $mesas->count(),
                (!empty($data['hora_apertura_mesa']) && $field === 'aviso_manana')
                    ? (' · Hora: ' . $data['hora_apertura_mesa'])
                    : ''
            )),
            'kind' => 'asistencia',
            'field' => $field,
            'field_label' => $labels[$field] ?? $field,
            'value' => (bool) $data['value'],
            'hora_apertura_mesa' => $data['hora_apertura_mesa'] ?? null,
            'latitud' => $data['latitud'] ?? null,
            'longitud' => $data['longitud'] ?? null,
            'presente_at' => $data['presente_at'] ?? null,
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
            'latitud' => $data['latitud'] ?? null,
            'longitud' => $data['longitud'] ?? null,
            'presente_at' => $data['presente_at'] ?? null,
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
                'resultado:id,mesa_id',
            ])
            ->orderBy('numero_mesa')
            ->get();

        $mesas = $mesasCollection
            ->map(fn ($m) => [
                'id' => $m->id,
                'numero_mesa' => $m->numero_mesa,
                'estado' => $m->estado,
                'recinto_nombre' => $m->recinto?->nombre,
                'tiene_resultado' => (bool) $m->resultado,
                'finalizada' => strtoupper((string) ($m->estado ?? '')) === 'FINALIZADA',
            ])
            ->values();

        return response()->json([
            'mesas' => $mesas,
            'partidos' => $this->mobilePartidos($mesasCollection->first()),
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

        return response()->json([
            'mesa_id' => $mesa->id,
            'resultado' => $resultado ? $this->mapResultadoForMobile($resultado) : null,
            'partidos' => $this->mobilePartidos($mesa),
            'bloqueada_mobile' => $this->mesaYaFinalizadaParaMovil($mesa, $resultado),
        ]);
    }

    public function votacionGuardar(Request $request, Mesa $mesa)
    {
        $user = $request->user();
        if ((int) $mesa->delegado_id !== (int) $user->id) {
            return response()->json(['message' => 'Mesa no asignada al usuario'], 403);
        }

        $resultadoExistente = ResultadoMesa::query()
            ->where('mesa_id', $mesa->id)
            ->first();

        if ($this->mesaYaFinalizadaParaMovil($mesa, $resultadoExistente)) {
            return response()->json([
                'message' => 'Esta mesa ya fue finalizada. No se puede volver a modificar desde la app.',
            ], 422);
        }

        $data = $request->validate([
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
        $votosNormalizados = [];
        $totalesCategorias = [
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

            $vvGob = (int) ($row['votos_gobernador'] ?? 0);
            $vvAsd = (int) ($row['votos_asambleista_distrito'] ?? 0);
            $vvAsp = (int) ($row['votos_asambleista_poblacion'] ?? 0);
            $vvCon = (int) ($row['votos_concejal'] ?? 0);
            $vvAlc = (int) ($row['votos_alcalde'] ?? 0);

            if ($vvGob < 0 || $vvAsd < 0 || $vvAsp < 0 || $vvCon < 0 || $vvAlc < 0) {
                return response()->json(['message' => 'Votos invalidos'], 422);
            }

            $votosNormalizados[$pid] = [
                'votos_gobernador' => $vvGob,
                'votos_asambleista_distrito' => $vvAsd,
                'votos_asambleista_poblacion' => $vvAsp,
                'votos_concejal' => $vvCon,
                'votos_alcalde' => $vvAlc,
            ];

            $totalesCategorias['gobernador'] += $vvGob;
            $totalesCategorias['asambleista_distrito'] += $vvAsd;
            $totalesCategorias['asambleista_poblacion'] += $vvAsp;
            $totalesCategorias['concejal'] += $vvCon;
            $totalesCategorias['alcalde'] += $vvAlc;
        }

        foreach ($partidosPermitidos as $partidoId) {
            if (!array_key_exists($partidoId, $votosNormalizados)) {
                $votosNormalizados[$partidoId] = [
                    'votos_gobernador' => 0,
                    'votos_asambleista_distrito' => 0,
                    'votos_asambleista_poblacion' => 0,
                    'votos_concejal' => 0,
                    'votos_alcalde' => 0,
                ];
            }
        }

        $totalBlancos =
            (int) ($data['blancos_gobernador'] ?? 0) +
            (int) ($data['blancos_asambleista_distrito'] ?? 0) +
            (int) ($data['blancos_asambleista_poblacion'] ?? 0) +
            (int) ($data['blancos_concejal'] ?? 0) +
            (int) ($data['blancos_alcalde'] ?? 0);
        $totalNulos =
            (int) ($data['nulos_gobernador'] ?? 0) +
            (int) ($data['nulos_asambleista_distrito'] ?? 0) +
            (int) ($data['nulos_asambleista_poblacion'] ?? 0) +
            (int) ($data['nulos_concejal'] ?? 0) +
            (int) ($data['nulos_alcalde'] ?? 0);
        $totalValidos = array_sum($totalesCategorias);
        $totalVotos = $totalValidos + $totalBlancos + $totalNulos;

        DB::transaction(function () use ($mesa, $user, $data, $request, $votosNormalizados, $totalBlancos, $totalNulos, $totalValidos, $totalVotos) {
            $rm = ResultadoMesa::query()->updateOrCreate(
                ['mesa_id' => $mesa->id],
                [
                    'registrado_por' => $user->id,
                    'origen_registro' => 'app',
                    'observacion' => $data['observacion'] ?? null,
                    'observacion_gobernador' => $data['observacion_gobernador'] ?? null,
                    'observacion_asambleista_distrito' => $data['observacion_asambleista_distrito'] ?? null,
                    'observacion_asambleista_poblacion' => $data['observacion_asambleista_poblacion'] ?? null,
                    'observacion_concejal' => $data['observacion_concejal'] ?? null,
                    'observacion_alcalde' => $data['observacion_alcalde'] ?? null,
                    'blancos_gobernador' => (int) ($data['blancos_gobernador'] ?? 0),
                    'nulos_gobernador' => (int) ($data['nulos_gobernador'] ?? 0),
                    'papeletas_no_utilizadas_gobernador' => (int) ($data['papeletas_no_utilizadas_gobernador'] ?? 0),
                    'blancos_asambleista_distrito' => (int) ($data['blancos_asambleista_distrito'] ?? 0),
                    'nulos_asambleista_distrito' => (int) ($data['nulos_asambleista_distrito'] ?? 0),
                    'papeletas_no_utilizadas_asambleista_distrito' => (int) ($data['papeletas_no_utilizadas_asambleista_distrito'] ?? 0),
                    'blancos_asambleista_poblacion' => (int) ($data['blancos_asambleista_poblacion'] ?? 0),
                    'nulos_asambleista_poblacion' => (int) ($data['nulos_asambleista_poblacion'] ?? 0),
                    'papeletas_no_utilizadas_asambleista_poblacion' => (int) ($data['papeletas_no_utilizadas_asambleista_poblacion'] ?? 0),
                    'blancos_concejal' => (int) ($data['blancos_concejal'] ?? 0),
                    'nulos_concejal' => (int) ($data['nulos_concejal'] ?? 0),
                    'papeletas_no_utilizadas_concejal' => (int) ($data['papeletas_no_utilizadas_concejal'] ?? 0),
                    'blancos_alcalde' => (int) ($data['blancos_alcalde'] ?? 0),
                    'nulos_alcalde' => (int) ($data['nulos_alcalde'] ?? 0),
                    'papeletas_no_utilizadas_alcalde' => (int) ($data['papeletas_no_utilizadas_alcalde'] ?? 0),
                    'total_validos' => $totalValidos,
                    'total_blancos' => $totalBlancos,
                    'total_nulos' => $totalNulos,
                    'total_votos' => $totalVotos,
                ]
            );

            $dir = "resultados_mesa/mesa_{$mesa->id}";
            foreach (['foto1', 'foto2', 'foto3', 'foto4', 'foto5', 'foto6', 'foto7', 'foto8', 'foto9', 'foto10'] as $field) {
                if (!$request->hasFile($field)) {
                    continue;
                }
                if (!empty($rm->{$field})) {
                    Storage::disk('public')->delete($rm->{$field});
                }
                $rm->{$field} = $request->file($field)->store($dir, 'public');
            }
            $rm->save();

            ResultadoMesaDetalle::query()
                ->where('resultado_mesa_id', $rm->id)
                ->whereNotIn('partido_id', array_keys($votosNormalizados))
                ->delete();

            foreach ($votosNormalizados as $partidoId => $row) {
                ResultadoMesaDetalle::updateOrCreate(
                    [
                        'resultado_mesa_id' => $rm->id,
                        'partido_id' => (int) $partidoId,
                    ],
                    [
                        'votos_gobernador' => (int) ($row['votos_gobernador'] ?? 0),
                        'votos_asambleista_distrito' => (int) ($row['votos_asambleista_distrito'] ?? 0),
                        'votos_asambleista_poblacion' => (int) ($row['votos_asambleista_poblacion'] ?? 0),
                        'votos_concejal' => (int) ($row['votos_concejal'] ?? 0),
                        'votos_alcalde' => (int) ($row['votos_alcalde'] ?? 0),
                    ]
                );
            }

            $mesa->estado = (!empty($rm->foto5) && !empty($rm->foto6))
                ? 'FINALIZADA'
                : 'EN_PROCESO';
            $mesa->save();
        });

        SocketEmitter::votacion([
            'title' => 'Nuevo dato registrado',
            'message' => trim(sprintf(
                '%s registro resultado en Mesa %s',
                $user->name ?? 'Usuario',
                $mesa->numero_mesa
            )),
            'kind' => 'resultado_mobile',
            'mesa_id' => $mesa->id,
            'mesa_numero' => $mesa->numero_mesa,
            'estado' => $mesa->estado,
            'finalizada' => strtoupper((string) ($mesa->estado ?? '')) === 'FINALIZADA',
            'user_id' => $user->id ?? null,
            'user_name' => $user->name ?? null,
            'username' => $user->username ?? null,
            'total_validos' => $totalValidos,
            'total_blancos' => $totalBlancos,
            'total_nulos' => $totalNulos,
            'categorias' => $this->resolveSocketCategoriasFromTotals($totalesCategorias),
        ]);

        return response()->json([
            'ok' => true,
            'mesa_id' => $mesa->id,
            'finalizada' => strtoupper((string) ($mesa->estado ?? '')) === 'FINALIZADA',
        ]);
    }

    private function mapResultadoForMobile(ResultadoMesa $resultado): array
    {
        $data = [
            'etapa_1' => (bool) $resultado->etapa_1,
            'etapa_2' => (bool) $resultado->etapa_2,
            'observacion' => $resultado->observacion,
            'observacion_gobernador' => $resultado->observacion_gobernador,
            'observacion_asambleista_distrito' => $resultado->observacion_asambleista_distrito,
            'observacion_asambleista_poblacion' => $resultado->observacion_asambleista_poblacion,
            'observacion_concejal' => $resultado->observacion_concejal,
            'observacion_alcalde' => $resultado->observacion_alcalde,
            'blancos_gobernador' => (int) ($resultado->blancos_gobernador ?? 0),
            'nulos_gobernador' => (int) ($resultado->nulos_gobernador ?? 0),
            'papeletas_no_utilizadas_gobernador' => (int) ($resultado->papeletas_no_utilizadas_gobernador ?? 0),
            'blancos_asambleista_distrito' => (int) ($resultado->blancos_asambleista_distrito ?? 0),
            'nulos_asambleista_distrito' => (int) ($resultado->nulos_asambleista_distrito ?? 0),
            'papeletas_no_utilizadas_asambleista_distrito' => (int) ($resultado->papeletas_no_utilizadas_asambleista_distrito ?? 0),
            'blancos_asambleista_poblacion' => (int) ($resultado->blancos_asambleista_poblacion ?? 0),
            'nulos_asambleista_poblacion' => (int) ($resultado->nulos_asambleista_poblacion ?? 0),
            'papeletas_no_utilizadas_asambleista_poblacion' => (int) ($resultado->papeletas_no_utilizadas_asambleista_poblacion ?? 0),
            'blancos_concejal' => (int) ($resultado->blancos_concejal ?? 0),
            'nulos_concejal' => (int) ($resultado->nulos_concejal ?? 0),
            'papeletas_no_utilizadas_concejal' => (int) ($resultado->papeletas_no_utilizadas_concejal ?? 0),
            'blancos_alcalde' => (int) ($resultado->blancos_alcalde ?? 0),
            'nulos_alcalde' => (int) ($resultado->nulos_alcalde ?? 0),
            'papeletas_no_utilizadas_alcalde' => (int) ($resultado->papeletas_no_utilizadas_alcalde ?? 0),
            'detalles' => collect($resultado->detalles)
                ->map(fn ($d) => [
                    'partido_id' => (int) $d->partido_id,
                    'votos_gobernador' => (int) ($d->votos_gobernador ?? 0),
                    'votos_asambleista_distrito' => (int) ($d->votos_asambleista_distrito ?? 0),
                    'votos_asambleista_poblacion' => (int) ($d->votos_asambleista_poblacion ?? 0),
                    'votos_concejal' => (int) ($d->votos_concejal ?? 0),
                    'votos_alcalde' => (int) ($d->votos_alcalde ?? 0),
                ])
                ->values()
                ->all(),
        ];

        foreach (['foto1', 'foto2', 'foto3', 'foto4', 'foto5', 'foto6', 'foto7', 'foto8', 'foto9', 'foto10'] as $slot) {
            $data[$slot . '_url'] = $resultado->{$slot} ? Storage::url($resultado->{$slot}) : null;
        }

        return $data;
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

    private function mobilePartidos(?Mesa $mesa)
    {
        return $this->partidosPorMesa($mesa)
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
    }

    private function mesaYaFinalizadaParaMovil(Mesa $mesa, ?ResultadoMesa $resultado): bool
    {
        if (!$resultado) {
            return false;
        }

        return strtoupper((string) ($mesa->estado ?? '')) === 'FINALIZADA';
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
                    'origen_registro' => 'app',
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

        $socketCategorias = [
            'gobernador' => collect($data['payload']['detalles'])->sum(fn ($row) => (int) ($row['votos_gobernador'] ?? 0)),
            'asambleista_distrito' => collect($data['payload']['detalles'])->sum(fn ($row) => (int) ($row['votos_asambleista_distrito'] ?? 0)),
            'asambleista_poblacion' => collect($data['payload']['detalles'])->sum(fn ($row) => (int) ($row['votos_asambleista_poblacion'] ?? 0)),
            'concejal' => collect($data['payload']['detalles'])->sum(fn ($row) => (int) ($row['votos_concejal'] ?? 0)),
            'alcalde' => collect($data['payload']['detalles'])->sum(fn ($row) => (int) ($row['votos_alcalde'] ?? 0)),
        ];

        SocketEmitter::votacion([
            'title' => 'Nuevo dato sincronizado',
            'message' => trim(sprintf(
                '%s sincronizo datos offline en mesa %s',
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
            'categorias' => $this->resolveSocketCategoriasFromTotals($socketCategorias),
        ]);

        return response()->json(['ok' => true]);
    }
}
