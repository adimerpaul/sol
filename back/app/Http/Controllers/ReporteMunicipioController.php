<?php

namespace App\Http\Controllers;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReporteMunicipioController extends Controller
{
    private const PERMISSION_NAME = 'Reportes por Municipio';
    private const DEFAULT_DEPARTAMENTO_ID = 5;

    public function bootstrap(Request $request)
    {
        $this->authorizeAccess($request);

        $departamentos = $this->departamentoOptions();
        $defaultDepartamentoId = $request->filled('departamento_id')
            ? (int) $request->input('departamento_id')
            : $this->resolveDefaultDepartamentoId($departamentos);

        return response()->json([
            'departamentos' => $departamentos,
            'default_departamento_id' => $defaultDepartamentoId,
            'municipios' => $defaultDepartamentoId
                ? $this->municipioOptions($defaultDepartamentoId)
                : [],
        ]);
    }

    public function municipios(Request $request)
    {
        $this->authorizeAccess($request);

        $data = $request->validate([
            'departamento_id' => ['required', 'integer', 'exists:departamentos,id'],
        ]);

        return response()->json(
            $this->municipioOptions((int) $data['departamento_id'])
        );
    }

    public function detalle(Request $request)
    {
        $this->authorizeAccess($request);

        return response()->json($this->buildReportPayload($request));
    }

    public function pdf(Request $request)
    {
        $this->authorizeAccess($request);

        $payload = $this->buildReportPayload($request);
        $actor = $request->user();
        $filename = 'reporte_municipio_' . $payload['municipio']['id'] . '.pdf';

        $pdf = Pdf::loadView('pdf.reportes_municipio', [
            ...$payload,
            'generatedAt' => now()->format('d/m/Y H:i'),
            'generatedBy' => $actor?->name ?: $actor?->username ?: 'Sistema',
        ])->setPaper('a4', 'landscape');

        return $pdf->stream($filename);
    }

    private function buildReportPayload(Request $request): array
    {
        $data = $request->validate([
            'departamento_id' => ['required', 'integer', 'exists:departamentos,id'],
            'municipio_id' => ['required', 'integer', 'exists:municipios,id'],
        ]);

        $municipio = DB::table('municipios as m')
            ->join('provincias as p', 'p.id', '=', 'm.provincia_id')
            ->join('departamentos as d', 'd.id', '=', 'p.departamento_id')
            ->select(
                'm.id',
                'm.nombre as municipio_nombre',
                'p.id as provincia_id',
                'p.nombre as provincia_nombre',
                'd.id as departamento_id',
                'd.nombre as departamento_nombre'
            )
            ->where('m.id', (int) $data['municipio_id'])
            ->where('d.id', (int) $data['departamento_id'])
            ->whereNull('m.deleted_at')
            ->whereNull('p.deleted_at')
            ->whereNull('d.deleted_at')
            ->first();

        if (!$municipio) {
            throw new HttpResponseException(response()->json([
                'message' => 'El municipio no pertenece al departamento seleccionado.',
            ], 422));
        }

        $rows = DB::table('recintos as r')
            ->join('localidades as l', 'l.id', '=', 'r.localidad_id')
            ->leftJoin('mesas as me', function ($join) {
                $join->on('me.recinto_id', '=', 'r.id')
                    ->whereNull('me.deleted_at');
            })
            ->select(
                'l.nombre as localidad',
                'r.nombre as recinto_nombre',
                DB::raw('COUNT(me.id) as total_mesas'),
                DB::raw('COALESCE(SUM(me.habilitados), 0) as total_habilitados')
            )
            ->where('r.municipio_id', (int) $data['municipio_id'])
            ->whereNotNull('r.id_original')
            ->whereNull('r.deleted_at')
            ->whereNull('l.deleted_at')
            ->groupBy('l.nombre', 'r.nombre')
            ->orderByDesc(DB::raw('COUNT(me.id)'))
            ->orderBy('l.nombre')
            ->orderBy('r.nombre')
            ->get()
            ->values()
            ->map(function ($row, $index) use ($municipio) {
                return [
                    'nro' => $index + 1,
                    'municipio' => $municipio->municipio_nombre,
                    'localidad' => $row->localidad,
                    'recinto_nombre' => $row->recinto_nombre,
                    'total_mesas' => (int) $row->total_mesas,
                    'total_habilitados' => (int) $row->total_habilitados,
                ];
            })
            ->all();

        return [
            'departamento' => [
                'id' => (int) $municipio->departamento_id,
                'nombre' => $municipio->departamento_nombre,
            ],
            'provincia' => [
                'id' => (int) $municipio->provincia_id,
                'nombre' => $municipio->provincia_nombre,
            ],
            'municipio' => [
                'id' => (int) $municipio->id,
                'nombre' => $municipio->municipio_nombre,
            ],
            'rows' => $rows,
            'totals' => [
                'recintos' => count($rows),
                'mesas' => collect($rows)->sum('total_mesas'),
                'habilitados' => collect($rows)->sum('total_habilitados'),
            ],
        ];
    }

    private function authorizeAccess(Request $request): void
    {
        $user = $request->user();

        if (!$user || !$user->hasPermissionTo(self::PERMISSION_NAME)) {
            throw new HttpResponseException(response()->json([
                'message' => 'No autorizado para acceder a este reporte.',
            ], 403));
        }
    }

    private function departamentoOptions(): array
    {
        return DB::table('departamentos')
            ->select('id', 'nombre')
            ->whereNull('deleted_at')
            ->orderByRaw('CASE WHEN id = ' . self::DEFAULT_DEPARTAMENTO_ID . ' THEN 0 ELSE 1 END')
            ->orderBy('nombre')
            ->get()
            ->map(fn ($row) => [
                'id' => (int) $row->id,
                'label' => $row->nombre,
            ])
            ->all();
    }

    private function municipioOptions(int $departamentoId): array
    {
        return DB::table('municipios as m')
            ->join('provincias as p', 'p.id', '=', 'm.provincia_id')
            ->select(
                'm.id',
                'm.nombre as municipio_nombre',
                'p.nombre as provincia_nombre'
            )
            ->where('p.departamento_id', $departamentoId)
            ->whereNull('m.deleted_at')
            ->whereNull('p.deleted_at')
            ->orderBy('m.nombre')
            ->get()
            ->map(fn ($row) => [
                'id' => (int) $row->id,
                'label' => $row->municipio_nombre . ' - ' . $row->provincia_nombre,
            ])
            ->all();
    }

    private function resolveDefaultDepartamentoId(array $departamentos): ?int
    {
        $ids = collect($departamentos)->pluck('id');

        if ($ids->contains(self::DEFAULT_DEPARTAMENTO_ID)) {
            return self::DEFAULT_DEPARTAMENTO_ID;
        }

        return $ids->first();
    }
}
