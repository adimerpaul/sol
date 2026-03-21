<?php

namespace App\Http\Controllers;

use App\Models\Localidad;
use App\Models\Mesa;
use App\Models\MesaAiControl;
use App\Models\MesaAiControlDetalle;
use App\Models\Partido;
use App\Models\Provincia;
use App\Models\Recinto;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MesaAiControlController extends Controller
{
    private const DEFAULT_DEPARTAMENTO = 'Oruro';
    private const DEFAULT_PROVINCIA = 'Cercado';
    private const DEFAULT_MUNICIPIO_ID = 191;
    private const DEFAULT_LOCALIDAD = 'Oruro';

    private const CATEGORY_META = [
        'concejal' => [
            'label' => 'Concejal',
            'vote_field' => 'votos_concejal',
            'blank_field' => 'blancos_concejal',
            'null_field' => 'nulos_concejal',
            'unused_field' => 'papeletas_no_utilizadas_concejal',
        ],
        'alcalde' => [
            'label' => 'Alcalde',
            'vote_field' => 'votos_alcalde',
            'blank_field' => 'blancos_alcalde',
            'null_field' => 'nulos_alcalde',
            'unused_field' => 'papeletas_no_utilizadas_alcalde',
        ],
    ];

    public function bootstrap(Request $request)
    {
        $this->authorizeAccess($request);

        $filters = $this->resolvedFilters($request);
        $mesaQuery = $this->mesaScope($filters);
        $mesaIds = (clone $mesaQuery)->pluck('mesas.id');
        $recintos = $this->recintoOptions($filters);
        $latestControls = $this->latestControlsByMesa($mesaIds);
        $selectedRecintoId = (int) ($request->integer('recinto_id') ?: ($recintos->first()->id ?? 0));
        $selectedMesa = $request->filled('mesa_id')
            ? $this->selectedMesaPayload((int) $request->integer('mesa_id'))
            : null;

        return response()->json([
            'filters' => $filters,
            'summary' => [
                'recintos' => (int) $recintos->count(),
                'mesas' => (int) $mesaIds->count(),
                'procesadas' => (int) $latestControls->where('estado', 'procesado')->count(),
                'confirmadas' => (int) $latestControls->where('estado', 'confirmado')->count(),
            ],
            'recintos' => $recintos->values(),
            'mesa_board' => $this->mesaBoard($mesaIds, 0, $latestControls),
            'recinto_status' => $this->recintoStatus($mesaIds, $recintos, $latestControls),
            'chart_data' => $this->chartData($latestControls),
            'recent_controls' => $this->recentControls($mesaIds),
            'selected_mesa' => $selectedMesa,
        ]);
    }

    public function mesasOptions(Request $request)
    {
        $this->authorizeAccess($request);

        $filters = $this->resolvedFilters($request);
        $search = trim((string) $request->get('q', ''));

        $rows = $this->mesaScope($filters)
            ->select([
                'mesas.id',
                'mesas.numero_mesa',
                'mesas.habilitados',
                'mesas.recinto_id',
                'recintos.nombre as recinto_nombre',
                'localidades.nombre as localidad_nombre',
                'municipios.nombre as municipio_nombre',
                'provincias.nombre as provincia_nombre',
                'departamentos.nombre as departamento_nombre',
            ])
            ->join('recintos', 'recintos.id', '=', 'mesas.recinto_id')
            ->leftJoin('localidades', 'localidades.id', '=', 'mesas.localidad_id')
            ->leftJoin('municipios', 'municipios.id', '=', 'mesas.municipio_id')
            ->leftJoin('provincias', 'provincias.id', '=', 'mesas.provincia_id')
            ->leftJoin('departamentos', 'departamentos.id', '=', 'mesas.departamento_id')
            ->when($request->filled('recinto_id'), fn ($q) => $q->where('mesas.recinto_id', $request->integer('recinto_id')))
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($qq) use ($search) {
                    $qq->where('mesas.numero_mesa', 'like', '%' . $search . '%')
                        ->orWhere('recintos.nombre', 'like', '%' . $search . '%');
                });
            })
            ->orderBy('recintos.nombre')
            ->orderBy('mesas.numero_mesa')
            ->limit(80)
            ->get()
            ->map(function ($row) {
                return [
                    'id' => (int) $row->id,
                    'recinto_id' => (int) $row->recinto_id,
                    'numero_mesa' => (string) $row->numero_mesa,
                    'habilitados' => (int) ($row->habilitados ?? 0),
                    'label' => trim(sprintf(
                        'Mesa %s · %s · %s / %s / %s',
                        $row->numero_mesa,
                        $row->recinto_nombre,
                        $row->localidad_nombre,
                        $row->municipio_nombre,
                        $row->provincia_nombre
                    )),
                ];
            });

        return response()->json($rows);
    }

    public function process(Request $request)
    {
        $this->authorizeAccess($request);

        $data = $request->validate([
            'mesa_id' => 'required|integer|exists:mesas,id',
            'fuente_tipo' => 'required|string|in:upload,resultado_slot',
            'fuente_slot_departamental' => 'nullable|string|max:20',
            'fuente_slot_municipal' => 'nullable|string|max:20',
            'foto_departamental' => 'nullable|image|max:8192',
            'foto_municipal' => 'nullable|image|max:8192',
            'observaciones' => 'nullable|string',
        ]);

        $mesa = Mesa::with(['recinto', 'localidad', 'municipio', 'provincia', 'departamento', 'resultado'])
            ->findOrFail($data['mesa_id']);
        $partidos = $this->partidosPorMesa($mesa);

        if ($partidos->isEmpty()) {
            return response()->json(['message' => 'No hay partidos habilitados para esta mesa.'], 422);
        }

        if (
            $data['fuente_tipo'] === 'upload' &&
            !$request->hasFile('foto_departamental') &&
            !$request->hasFile('foto_municipal')
        ) {
            return response()->json(['message' => 'Debe adjuntar al menos una papeleta para procesar.'], 422);
        }

        if (
            $data['fuente_tipo'] === 'resultado_slot' &&
            empty($data['fuente_slot_departamental']) &&
            empty($data['fuente_slot_municipal'])
        ) {
            return response()->json(['message' => 'Debe seleccionar al menos una foto oficial de la mesa.'], 422);
        }

        $stored = $this->storeSourceImages($request, $mesa, $data);
        $gemini = $this->callGemini($stored['images'], $mesa, $partidos);
        $normalized = $this->normalizeGeminiPayload($gemini['parsed'], $partidos);

        $control = DB::transaction(function () use ($request, $mesa, $data, $stored, $gemini, $normalized) {
            $control = MesaAiControl::create(array_merge(
                [
                    'mesa_id' => $mesa->id,
                    'resultado_mesa_id' => $mesa->resultado?->id,
                    'registrado_por' => $request->user()?->id,
                    'fuente_tipo' => $data['fuente_tipo'],
                    'fuente_slot' => $data['fuente_slot_departamental'] ?? null,
                    'fuente_slot_secundaria' => $data['fuente_slot_municipal'] ?? null,
                    'imagen_path' => $stored['primary_relative_path'],
                    'imagen_path_secundaria' => $stored['secondary_relative_path'],
                    'modelo' => $gemini['model'],
                    'estado' => 'procesado',
                    'total_detectado' => $this->detectedTotal($normalized['partidos'], $normalized['categorias']),
                    'resumen_json' => $normalized['resumen_json'],
                    'respuesta_json' => $gemini['response_json'],
                    'respuesta_raw' => $gemini['response_raw'],
                    'observaciones' => $data['observaciones'] ?? $normalized['observaciones'],
                ],
                $this->categoryPayload($normalized['categorias'])
            ));

            foreach ($normalized['partidos'] as $row) {
                MesaAiControlDetalle::create([
                    'mesa_ai_control_id' => $control->id,
                    'partido_id' => $row['partido_id'],
                    'votos_gobernador' => $row['votos_gobernador'],
                    'votos_asambleista_distrito' => $row['votos_asambleista_distrito'],
                    'votos_asambleista_poblacion' => $row['votos_asambleista_poblacion'],
                    'votos_concejal' => $row['votos_concejal'],
                    'votos_alcalde' => $row['votos_alcalde'],
                    'confianza' => $row['confianza'],
                    'fuente_json' => $row['fuente_json'],
                ]);
            }

            return $control;
        });

        return response()->json([
            'message' => 'Imagen procesada con IA.',
            'control' => $this->controlPayload($control->id),
        ]);
    }

    public function confirm(Request $request, MesaAiControl $control)
    {
        $this->authorizeAccess($request);

        $data = $request->validate([
            'observaciones' => 'nullable|string',
            'categorias' => 'required|array',
            'votos' => 'required|array|min:1',
            'votos.*.partido_id' => 'required|integer|exists:partidos,id',
            'votos.*.votos_gobernador' => 'nullable|integer|min:0',
            'votos.*.votos_asambleista_distrito' => 'nullable|integer|min:0',
            'votos.*.votos_asambleista_poblacion' => 'nullable|integer|min:0',
            'votos.*.votos_concejal' => 'nullable|integer|min:0',
            'votos.*.votos_alcalde' => 'nullable|integer|min:0',
            'votos.*.confianza' => 'nullable|numeric|min:0|max:100',
        ]);

        $partidosPermitidos = $this->partidosPorMesa($control->mesa)->pluck('id')->map(fn ($id) => (int) $id);
        foreach ($data['votos'] as $row) {
            if (!$partidosPermitidos->contains((int) $row['partido_id'])) {
                return response()->json(['message' => 'Hay partidos no habilitados para esta mesa.'], 422);
            }
        }

        $normalizedCategorias = $this->normalizeCategoryInput($data['categorias']);
        $normalizedRows = $this->normalizeManualVotes($data['votos'], $partidosPermitidos);

        DB::transaction(function () use ($control, $data, $normalizedCategorias, $normalizedRows) {
            $control->update(array_merge(
                [
                    'estado' => 'confirmado',
                    'observaciones' => $data['observaciones'] ?? $control->observaciones,
                    'confirmado_at' => now(),
                    'total_detectado' => $this->detectedTotal($normalizedRows, $normalizedCategorias),
                    'resumen_json' => [
                        'categorias' => $normalizedCategorias,
                        'totales_partido' => $this->aggregatePartyTotals($normalizedRows),
                    ],
                ],
                $this->categoryPayload($normalizedCategorias)
            ));

            $control->detalles()->delete();

            foreach ($normalizedRows as $row) {
                MesaAiControlDetalle::create([
                    'mesa_ai_control_id' => $control->id,
                    'partido_id' => $row['partido_id'],
                    'votos_gobernador' => $row['votos_gobernador'],
                    'votos_asambleista_distrito' => $row['votos_asambleista_distrito'],
                    'votos_asambleista_poblacion' => $row['votos_asambleista_poblacion'],
                    'votos_concejal' => $row['votos_concejal'],
                    'votos_alcalde' => $row['votos_alcalde'],
                    'confianza' => $row['confianza'],
                    'fuente_json' => $row['fuente_json'],
                ]);
            }
        });

        return response()->json([
            'message' => 'Control IA confirmado correctamente.',
            'control' => $this->controlPayload($control->id),
        ]);
    }

    private function authorizeAccess(Request $request): void
    {
        $user = $request->user();
        $allowedRole = in_array($user?->role, ['Administrador', 'Supervisor'], true);
        $allowedPermission = $user?->can('Control IA Mesas') ?? false;

        abort_unless($allowedRole || $allowedPermission, 403, 'No autorizado para Control IA Mesas.');
    }

    private function resolvedFilters(Request $request): array
    {
        $departamentoId = $request->integer('departamento_id');
        if (!$departamentoId) {
            $departamentoId = DB::table('departamentos')->where('nombre', self::DEFAULT_DEPARTAMENTO)->value('id');
        }

        $provinciaId = $request->integer('provincia_id');
        if (!$provinciaId) {
            $provinciaId = Provincia::query()
                ->where('departamento_id', $departamentoId)
                ->where('nombre', self::DEFAULT_PROVINCIA)
                ->value('id');
        }

        $municipioId = $request->integer('municipio_id') ?: self::DEFAULT_MUNICIPIO_ID;
        $localidadId = $request->integer('localidad_id');
        if (!$localidadId) {
            $localidadId = Localidad::query()
                ->where('municipio_id', $municipioId)
                ->where('nombre', self::DEFAULT_LOCALIDAD)
                ->value('id');
        }

        return [
            'departamento_id' => (int) $departamentoId,
            'provincia_id' => (int) $provinciaId,
            'municipio_id' => (int) $municipioId,
            'localidad_id' => (int) $localidadId,
            'departamento_nombre' => DB::table('departamentos')->where('id', $departamentoId)->value('nombre'),
            'provincia_nombre' => DB::table('provincias')->where('id', $provinciaId)->value('nombre'),
            'municipio_nombre' => DB::table('municipios')->where('id', $municipioId)->value('nombre'),
            'localidad_nombre' => DB::table('localidades')->where('id', $localidadId)->value('nombre'),
        ];
    }

    private function mesaScope(array $filters)
    {
        return Mesa::query()
            ->whereNull('mesas.deleted_at')
            ->where('mesas.departamento_id', $filters['departamento_id'])
            ->where('mesas.provincia_id', $filters['provincia_id'])
            ->where('mesas.municipio_id', $filters['municipio_id'])
            ->when(!empty($filters['localidad_id']), fn ($q) => $q->where('mesas.localidad_id', $filters['localidad_id']));
    }

    private function recintoOptions(array $filters): Collection
    {
        return Recinto::query()
            ->select(['recintos.id', 'recintos.nombre', 'recintos.latitud', 'recintos.longitud'])
            ->whereNull('recintos.deleted_at')
            ->where('recintos.departamento_id', $filters['departamento_id'])
            ->where('recintos.provincia_id', $filters['provincia_id'])
            ->where('recintos.municipio_id', $filters['municipio_id'])
            ->when(!empty($filters['localidad_id']), fn ($q) => $q->where('recintos.localidad_id', $filters['localidad_id']))
            ->orderBy('recintos.nombre')
            ->get();
    }

    private function selectedMesaPayload(int $mesaId): ?array
    {
        $mesa = Mesa::with([
            'recinto:id,nombre',
            'localidad:id,nombre',
            'municipio:id,nombre',
            'provincia:id,nombre',
            'departamento:id,nombre',
            'resultado',
        ])->find($mesaId);

        if (!$mesa) {
            return null;
        }

        $fuentes = [];
        if ($mesa->resultado) {
            foreach (range(1, 10) as $slot) {
                $field = 'foto' . $slot;
                if (!empty($mesa->resultado->{$field})) {
                    $fuentes[] = [
                        'slot' => $field,
                        'label' => 'Foto oficial ' . $slot,
                        'url' => $this->publicUrl($mesa->resultado->{$field}),
                    ];
                }
            }
        }

        $latest = MesaAiControl::query()
            ->where('mesa_id', $mesa->id)
            ->latest('id')
            ->value('id');

        return [
            'id' => (int) $mesa->id,
            'numero_mesa' => (string) $mesa->numero_mesa,
            'habilitados' => (int) ($mesa->habilitados ?? 0),
            'recinto' => $mesa->recinto?->nombre,
            'localidad' => $mesa->localidad?->nombre,
            'municipio' => $mesa->municipio?->nombre,
            'provincia' => $mesa->provincia?->nombre,
            'departamento' => $mesa->departamento?->nombre,
            'fuentes_oficiales' => $fuentes,
            'latest_control' => $latest ? $this->controlPayload($latest) : null,
        ];
    }

    private function latestControlsByMesa(Collection $mesaIds): Collection
    {
        if ($mesaIds->isEmpty()) {
            return collect();
        }

        return MesaAiControl::query()
            ->with(['mesa.recinto:id,nombre'])
            ->whereIn('mesa_id', $mesaIds->all())
            ->latest('id')
            ->get()
            ->unique('mesa_id')
            ->values();
    }

    private function recentControls(Collection $mesaIds): array
    {
        if ($mesaIds->isEmpty()) {
            return [];
        }

        return MesaAiControl::query()
            ->with([
                'mesa.recinto:id,nombre',
                'registradoPor:id,name,username',
            ])
            ->whereIn('mesa_id', $mesaIds->all())
            ->latest('id')
            ->limit(12)
            ->get()
            ->map(function ($control) {
                return [
                    'id' => (int) $control->id,
                    'estado' => $control->estado,
                    'mesa_numero' => $control->mesa?->numero_mesa,
                    'recinto' => $control->mesa?->recinto?->nombre,
                    'usuario' => $control->registradoPor?->name ?: $control->registradoPor?->username,
                    'total_detectado' => (int) $control->total_detectado,
                    'updated_at' => optional($control->updated_at)->toDateTimeString(),
                ];
            })
            ->values()
            ->all();
    }

    private function recintoStatus(Collection $mesaIds, Collection $recintos, Collection $latestControls): array
    {
        if ($mesaIds->isEmpty()) {
            return [];
        }

        $latest = $latestControls->keyBy('mesa_id');
        $mesas = Mesa::query()
            ->select(['id', 'recinto_id'])
            ->whereIn('id', $mesaIds->all())
            ->get()
            ->groupBy('recinto_id');

        return $recintos->map(function ($recinto) use ($mesas, $latest) {
            $rows = $mesas->get($recinto->id, collect());
            $total = $rows->count();
            $procesadas = 0;
            $confirmadas = 0;

            foreach ($rows as $mesa) {
                $control = $latest->get($mesa->id);
                if ($control) {
                    $procesadas++;
                    if ($control->estado === 'confirmado') {
                        $confirmadas++;
                    }
                }
            }

            $status = 'pendiente';
            $color = 'negative';
            if ($confirmadas === $total && $total > 0) {
                $status = 'completo';
                $color = 'positive';
            } elseif ($procesadas > 0) {
                $status = 'parcial';
                $color = 'warning';
            }

            return [
                'id' => (int) $recinto->id,
                'nombre' => $recinto->nombre,
                'latitud' => $recinto->latitud,
                'longitud' => $recinto->longitud,
                'total_mesas' => $total,
                'procesadas' => $procesadas,
                'confirmadas' => $confirmadas,
                'faltantes' => max($total - $procesadas, 0),
                'status' => $status,
                'color' => $color,
            ];
        })->values()->all();
    }

    private function mesaBoard(Collection $mesaIds, int $selectedRecintoId, Collection $latestControls): array
    {
        if ($mesaIds->isEmpty()) {
            return [];
        }

        $latest = $latestControls->keyBy('mesa_id');

        return Mesa::query()
            ->with('recinto:id,nombre')
            ->whereIn('id', $mesaIds->all())
            ->when($selectedRecintoId > 0, fn ($q) => $q->where('recinto_id', $selectedRecintoId))
            ->orderBy('numero_mesa')
            ->get()
            ->map(function ($mesa) use ($latest) {
                $control = $latest->get($mesa->id);
                $status = 'pendiente';
                $color = 'negative';

                if ($control?->estado === 'confirmado') {
                    $status = 'confirmado';
                    $color = 'positive';
                } elseif ($control) {
                    $status = 'procesado';
                    $color = 'warning';
                }

                return [
                    'id' => (int) $mesa->id,
                    'numero_mesa' => (string) $mesa->numero_mesa,
                    'recinto' => $mesa->recinto?->nombre,
                    'status' => $status,
                    'color' => $color,
                    'control_id' => $control?->id,
                ];
            })
            ->values()
            ->all();
    }

    private function chartData(Collection $latestControls): array
    {
        if ($latestControls->isEmpty()) {
            return [];
        }

        $latestIds = $latestControls->pluck('id');
        $rows = MesaAiControlDetalle::query()
            ->with('partido:id,sigla,nombre,color')
            ->whereIn('mesa_ai_control_id', $latestIds->all())
            ->get();

        $charts = [];
        foreach (self::CATEGORY_META as $key => $meta) {
            $rank = $rows
                ->groupBy('partido_id')
                ->map(function ($items) use ($meta) {
                    $partido = $items->first()->partido;
                    $total = $items->sum($meta['vote_field']);

                    return [
                        'partido_id' => (int) $partido->id,
                        'sigla' => $partido->sigla,
                        'nombre' => $partido->nombre,
                        'color' => $partido->color ?: '#c62828',
                        'total' => (int) $total,
                    ];
                })
                ->sortByDesc('total')
                ->take(10)
                ->values();

            $charts[] = [
                'key' => $key,
                'label' => $meta['label'],
                'series' => $rank->map(fn ($row) => (int) $row['total'])->values()->all(),
                'labels' => $rank->map(fn ($row) => $row['sigla'] ?: $row['nombre'])->values()->all(),
                'colors' => $rank->map(fn ($row) => $row['color'])->values()->all(),
                'total' => (int) $rank->sum('total'),
                'ranking' => $rank->all(),
            ];
        }

        return $charts;
    }

    private function storeSourceImages(Request $request, Mesa $mesa, array $data): array
    {
        $dir = 'ia_controles/mesa_' . $mesa->id;
        $images = [];

        if ($data['fuente_tipo'] === 'upload') {
            if ($request->hasFile('foto_departamental')) {
                $path = $request->file('foto_departamental')->store($dir, 'public');
                $images['departamental'] = [
                    'relative_path' => $path,
                    'absolute_path' => Storage::disk('public')->path($path),
                ];
            }

            if ($request->hasFile('foto_municipal')) {
                $path = $request->file('foto_municipal')->store($dir, 'public');
                $images['municipal'] = [
                    'relative_path' => $path,
                    'absolute_path' => Storage::disk('public')->path($path),
                ];
            }
        } else {
            $resultado = $mesa->resultado;
            foreach ([
                'departamental' => $data['fuente_slot_departamental'] ?? null,
                'municipal' => $data['fuente_slot_municipal'] ?? null,
            ] as $kind => $slot) {
                if (!$slot) {
                    continue;
                }

                if (!$resultado || !in_array($slot, array_map(fn ($n) => 'foto' . $n, range(1, 10)), true)) {
                    abort(422, 'La foto oficial seleccionada no es valida.');
                }

                $sourcePath = $resultado->{$slot};
                if (!$sourcePath || !Storage::disk('public')->exists($sourcePath)) {
                    abort(422, 'La foto oficial no existe en almacenamiento.');
                }

                $extension = pathinfo($sourcePath, PATHINFO_EXTENSION) ?: 'jpg';
                $destPath = $dir . '/' . $kind . '_' . $slot . '_' . Str::uuid() . '.' . $extension;
                Storage::disk('public')->copy($sourcePath, $destPath);

                $images[$kind] = [
                    'relative_path' => $destPath,
                    'absolute_path' => Storage::disk('public')->path($destPath),
                ];
            }
        }

        if (empty($images)) {
            abort(422, 'No se encontro ninguna imagen para procesar.');
        }

        return [
            'images' => $images,
            'primary_relative_path' => $images['departamental']['relative_path'] ?? $images['municipal']['relative_path'],
            'secondary_relative_path' => $images['municipal']['relative_path'] ?? null,
        ];
    }

    private function callGemini(array $images, Mesa $mesa, Collection $partidos): array
    {
        $url = rtrim((string) config('services.gemini_ai_control.url'), '?');
        $key = (string) config('services.gemini_ai_control.key');

        if ($url === '' || $key === '') {
            abort(500, 'Falta configurar GEMINI_AI_URL o GEMINI_AI_KEY en el backend.');
        }

        $prompt = $this->buildGeminiPrompt($mesa, $partidos);
        $parts = [['text' => $prompt]];

        foreach ($images as $kind => $image) {
            $parts[] = ['text' => 'Imagen ' . $kind . ':'];
            $parts[] = [
                'inline_data' => [
                    'mime_type' => mime_content_type($image['absolute_path']) ?: 'image/jpeg',
                    'data' => base64_encode(file_get_contents($image['absolute_path'])),
                ],
            ];
        }

        $response = Http::timeout(90)
            ->acceptJson()
            ->post($url . '?key=' . urlencode($key), [
                'contents' => [[
                    'role' => 'user',
                    'parts' => $parts,
                ]],
            ]);

        if (!$response->successful()) {
            abort(502, 'Gemini no respondio correctamente: ' . $response->status());
        }

        $json = $response->json();
        $text = collect($json['candidates'][0]['content']['parts'] ?? [])
            ->pluck('text')
            ->filter()
            ->implode("\n");

        return [
            'model' => basename(parse_url($url, PHP_URL_PATH) ?: 'gemini'),
            'response_json' => $json,
            'response_raw' => $text,
            'parsed' => $this->extractJsonFromGemini($text),
        ];
    }

    private function buildGeminiPrompt(Mesa $mesa, Collection $partidos): string
    {
        $partyList = $partidos->map(function ($partido) {
            return sprintf(
                '- partido_id: %s, sigla: %s, nombre: %s',
                $partido->id,
                $partido->sigla,
                $partido->nombre
            );
        })->implode("\n");

        return implode("\n", [
            'Analiza la fotografia de una hoja de conteo electoral.',
            'Puede venir una o dos fotografias.',
            'Solo interesa la papeleta municipal con alcalde y concejal.',
            'Si llega una foto departamental, ignorala.',
            'Debes extraer solo numeros visibles. Si no es legible, usa 0 y explica la duda en observaciones.',
            'Mesa: ' . $mesa->numero_mesa,
            'Recinto: ' . ($mesa->recinto?->nombre ?? '-'),
            'Responde solo JSON valido, sin markdown.',
            'Estructura obligatoria:',
            '{',
            '  "observaciones": "texto corto",',
            '  "categorias": {',
            '    "concejal": {"blancos":0,"nulos":0,"papeletas_no_utilizadas":0},',
            '    "alcalde": {"blancos":0,"nulos":0,"papeletas_no_utilizadas":0}',
            '  },',
            '  "partidos": [',
            '    {',
            '      "partido_id": 0,',
            '      "sigla": "",',
            '      "votos_concejal": 0,',
            '      "votos_alcalde": 0,',
            '      "confianza": 0',
            '    }',
            '  ]',
            '}',
            'Usa solamente estos partidos habilitados para la mesa:',
            $partyList,
        ]);
    }

    private function extractJsonFromGemini(string $text): array
    {
        $decoded = json_decode($text, true);
        if (is_array($decoded)) {
            return $decoded;
        }

        if (preg_match('/\{.*\}/s', $text, $matches)) {
            $decoded = json_decode($matches[0], true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        abort(422, 'No se pudo interpretar la respuesta JSON de Gemini.');
    }

    private function normalizeGeminiPayload(array $payload, Collection $partidos): array
    {
        $partiesById = $partidos->keyBy(fn ($p) => (int) $p->id);
        $partiesBySigla = $partidos->keyBy(fn ($p) => Str::upper(trim((string) $p->sigla)));
        $incoming = collect($payload['partidos'] ?? []);

        $rows = $partidos->map(function ($partido) use ($incoming, $partiesById, $partiesBySigla) {
            $match = $incoming->first(function ($row) use ($partido, $partiesById, $partiesBySigla) {
                $id = (int) ($row['partido_id'] ?? 0);
                $sigla = Str::upper(trim((string) ($row['sigla'] ?? '')));

                if ($id > 0 && $partiesById->has($id)) {
                    return $id === (int) $partido->id;
                }

                return $sigla !== '' && $partiesBySigla->has($sigla) && (int) $partiesBySigla->get($sigla)->id === (int) $partido->id;
            }) ?? [];

            return [
                'partido_id' => (int) $partido->id,
                'votos_gobernador' => max((int) ($match['votos_gobernador'] ?? 0), 0),
                'votos_asambleista_distrito' => max((int) ($match['votos_asambleista_distrito'] ?? 0), 0),
                'votos_asambleista_poblacion' => max((int) ($match['votos_asambleista_poblacion'] ?? 0), 0),
                'votos_concejal' => max((int) ($match['votos_concejal'] ?? 0), 0),
                'votos_alcalde' => max((int) ($match['votos_alcalde'] ?? 0), 0),
                'confianza' => isset($match['confianza']) ? min(max((float) $match['confianza'], 0), 100) : null,
                'fuente_json' => $match ?: null,
            ];
        })->values()->all();

        $categorias = $this->normalizeCategoryInput($payload['categorias'] ?? []);

        return [
            'observaciones' => trim((string) ($payload['observaciones'] ?? '')),
            'categorias' => $categorias,
            'partidos' => $rows,
            'resumen_json' => [
                'categorias' => $categorias,
                'totales_partido' => $this->aggregatePartyTotals($rows),
            ],
        ];
    }

    private function normalizeCategoryInput(array $categorias): array
    {
        $normalized = [];

        foreach (self::CATEGORY_META as $key => $meta) {
            $source = is_array($categorias[$key] ?? null) ? $categorias[$key] : [];
            $normalized[$key] = [
                'blancos' => max((int) ($source['blancos'] ?? 0), 0),
                'nulos' => max((int) ($source['nulos'] ?? 0), 0),
                'papeletas_no_utilizadas' => max((int) ($source['papeletas_no_utilizadas'] ?? 0), 0),
            ];
        }

        return $normalized;
    }

    private function normalizeManualVotes(array $rows, Collection $partidosPermitidos): array
    {
        $allowed = $partidosPermitidos->map(fn ($id) => (int) $id)->values();

        return collect($rows)
            ->filter(fn ($row) => $allowed->contains((int) ($row['partido_id'] ?? 0)))
            ->map(function ($row) {
                return [
                    'partido_id' => (int) $row['partido_id'],
                    'votos_gobernador' => max((int) ($row['votos_gobernador'] ?? 0), 0),
                    'votos_asambleista_distrito' => max((int) ($row['votos_asambleista_distrito'] ?? 0), 0),
                    'votos_asambleista_poblacion' => max((int) ($row['votos_asambleista_poblacion'] ?? 0), 0),
                    'votos_concejal' => max((int) ($row['votos_concejal'] ?? 0), 0),
                    'votos_alcalde' => max((int) ($row['votos_alcalde'] ?? 0), 0),
                    'confianza' => isset($row['confianza']) ? min(max((float) $row['confianza'], 0), 100) : null,
                    'fuente_json' => ['manual' => true],
                ];
            })
            ->values()
            ->all();
    }

    private function categoryPayload(array $categorias): array
    {
        $payload = [];

        foreach (self::CATEGORY_META as $key => $meta) {
            $payload[$meta['blank_field']] = (int) ($categorias[$key]['blancos'] ?? 0);
            $payload[$meta['null_field']] = (int) ($categorias[$key]['nulos'] ?? 0);
            $payload[$meta['unused_field']] = (int) ($categorias[$key]['papeletas_no_utilizadas'] ?? 0);
        }

        return $payload;
    }

    private function aggregatePartyTotals(array $rows): array
    {
        return collect($rows)->map(function ($row) {
            return [
                'partido_id' => (int) $row['partido_id'],
                'total' => (int) (
                    ($row['votos_gobernador'] ?? 0) +
                    ($row['votos_asambleista_distrito'] ?? 0) +
                    ($row['votos_asambleista_poblacion'] ?? 0) +
                    ($row['votos_concejal'] ?? 0) +
                    ($row['votos_alcalde'] ?? 0)
                ),
            ];
        })->all();
    }

    private function detectedTotal(array $rows, array $categorias): int
    {
        $partyTotal = (int) collect($rows)->sum(function ($row) {
            return (int) (
                ($row['votos_gobernador'] ?? 0) +
                ($row['votos_asambleista_distrito'] ?? 0) +
                ($row['votos_asambleista_poblacion'] ?? 0) +
                ($row['votos_concejal'] ?? 0) +
                ($row['votos_alcalde'] ?? 0)
            );
        });

        $categoryTotal = (int) collect($categorias)->sum(function ($row) {
            return (int) (
                ($row['blancos'] ?? 0) +
                ($row['nulos'] ?? 0) +
                ($row['papeletas_no_utilizadas'] ?? 0)
            );
        });

        return $partyTotal + $categoryTotal;
    }

    private function controlPayload(int $controlId): array
    {
        $control = MesaAiControl::query()
            ->with([
                'mesa.recinto:id,nombre',
                'mesa.localidad:id,nombre',
                'mesa.municipio:id,nombre',
                'mesa.provincia:id,nombre',
                'mesa.departamento:id,nombre',
                'detalles.partido:id,sigla,nombre,color',
                'registradoPor:id,name,username',
            ])
            ->findOrFail($controlId);

        return [
            'id' => (int) $control->id,
            'mesa_id' => (int) $control->mesa_id,
            'mesa_numero' => $control->mesa?->numero_mesa,
            'mesa_habilitados' => (int) ($control->mesa?->habilitados ?? 0),
            'estado' => $control->estado,
            'modelo' => $control->modelo,
            'imagen_url' => $this->publicUrl($control->imagen_path),
            'imagen_url_secundaria' => $control->imagen_path_secundaria ? $this->publicUrl($control->imagen_path_secundaria) : null,
            'observaciones' => $control->observaciones,
            'total_detectado' => (int) $control->total_detectado,
            'confirmado_at' => optional($control->confirmado_at)->toDateTimeString(),
            'registrado_por' => $control->registradoPor?->name ?: $control->registradoPor?->username,
            'mesa_contexto' => [
                'recinto' => $control->mesa?->recinto?->nombre,
                'localidad' => $control->mesa?->localidad?->nombre,
                'municipio' => $control->mesa?->municipio?->nombre,
                'provincia' => $control->mesa?->provincia?->nombre,
                'departamento' => $control->mesa?->departamento?->nombre,
            ],
            'categorias' => collect(self::CATEGORY_META)->mapWithKeys(function ($meta, $key) use ($control) {
                return [$key => [
                    'blancos' => (int) ($control->{$meta['blank_field']} ?? 0),
                    'nulos' => (int) ($control->{$meta['null_field']} ?? 0),
                    'papeletas_no_utilizadas' => (int) ($control->{$meta['unused_field']} ?? 0),
                ]];
            })->all(),
            'votos' => $control->detalles->map(function ($detalle) {
                return [
                    'partido_id' => (int) $detalle->partido_id,
                    'sigla' => $detalle->partido?->sigla,
                    'nombre' => $detalle->partido?->nombre,
                    'color' => $detalle->partido?->color ?: '#c62828',
                    'votos_gobernador' => (int) $detalle->votos_gobernador,
                    'votos_asambleista_distrito' => (int) $detalle->votos_asambleista_distrito,
                    'votos_asambleista_poblacion' => (int) $detalle->votos_asambleista_poblacion,
                    'votos_concejal' => (int) $detalle->votos_concejal,
                    'votos_alcalde' => (int) $detalle->votos_alcalde,
                    'confianza' => $detalle->confianza !== null ? (float) $detalle->confianza : null,
                ];
            })->values()->all(),
        ];
    }

    private function partidosPorMesa(Mesa $mesa): Collection
    {
        $municipioId = $mesa->municipio_id ?: $mesa->recinto?->municipio_id;

        $base = Partido::query()
            ->select([
                'partidos.id',
                'partidos.sigla',
                'partidos.nombre',
                'partidos.color',
                'partidos.orden_municipal',
                'partidos.orden_departamental',
            ]);

        $tieneConfig = DB::table('municipio_partido')
            ->where('municipio_id', $municipioId)
            ->exists();

        if (!$municipioId || !$tieneConfig) {
            return $base
                ->orderByRaw('CASE WHEN partidos.orden_municipal IS NULL OR partidos.orden_municipal = 0 THEN 1 ELSE 0 END')
                ->orderBy('partidos.orden_municipal')
                ->orderBy('partidos.sigla')
                ->get();
        }

        return $base
            ->join('municipio_partido as mp', function ($join) use ($municipioId) {
                $join->on('mp.partido_id', '=', 'partidos.id')
                    ->where('mp.municipio_id', '=', $municipioId);
            })
            ->where(function ($q) {
                $q->where('mp.habilitado_gobernador', true)
                    ->orWhere('mp.habilitado_asambleista_poblacion', true)
                    ->orWhere('mp.habilitado_asambleista_distrito', true)
                    ->orWhere('mp.habilitado_alcalde', true)
                    ->orWhere('mp.habilitado_concejal', true);
            })
            ->orderByRaw('CASE WHEN partidos.orden_municipal IS NULL OR partidos.orden_municipal = 0 THEN 1 ELSE 0 END')
            ->orderBy('partidos.orden_municipal')
            ->orderBy('partidos.sigla')
            ->get();
    }

    private function publicUrl(?string $path): ?string
    {
        if (!$path) {
            return null;
        }

        $base = rtrim((string) config('app.url'), '/');
        return $base . Storage::url($path);
    }
}
