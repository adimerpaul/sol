<?php

namespace App\Http\Controllers;

use App\Models\Mesa;
use App\Models\Partido;
use App\Models\ResultadoMesaSegundaVuelta;
use App\Models\ResultadoMesaSegundaVueltaDetalle;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class SuperAdminMesasSegundaVueltaController extends Controller
{
    private const DEPARTAMENTO_ORURO_ID = 5;
    private const PARTIDOS_SEGUNDA_VUELTA = [11, 15];

    public function bootstrap()
    {
        $mesas = $this->baseMesasQuery()
            ->orderBy('mesas.numero_mesa')
            ->get()
            ->map(fn (Mesa $mesa) => $this->mapMesaRow($mesa))
            ->values();

        return response()->json([
            'summary' => [
                'total' => $mesas->count(),
                'asignadas' => $mesas->whereNotNull('delegado_segunda_vuelta_id')->count(),
                'sin_delegado' => $mesas->whereNull('delegado_segunda_vuelta_id')->count(),
                'con_resultado' => $mesas->where('tiene_resultado', true)->count(),
            ],
            'mesas' => $mesas,
            'geo' => [
                'provincias' => DB::table('provincias')
                    ->select('id', 'nombre', 'departamento_id')
                    ->whereNull('deleted_at')
                    ->where('departamento_id', self::DEPARTAMENTO_ORURO_ID)
                    ->orderBy('nombre')
                    ->get(),
                'municipios' => DB::table('municipios')
                    ->join('provincias', 'provincias.id', '=', 'municipios.provincia_id')
                    ->select('municipios.id', 'municipios.nombre', 'municipios.provincia_id')
                    ->whereNull('municipios.deleted_at')
                    ->where('provincias.departamento_id', self::DEPARTAMENTO_ORURO_ID)
                    ->orderBy('municipios.nombre')
                    ->get(),
                'localidades' => DB::table('localidades')
                    ->join('municipios', 'municipios.id', '=', 'localidades.municipio_id')
                    ->join('provincias', 'provincias.id', '=', 'municipios.provincia_id')
                    ->select('localidades.id', 'localidades.nombre', 'localidades.municipio_id')
                    ->whereNull('localidades.deleted_at')
                    ->where('provincias.departamento_id', self::DEPARTAMENTO_ORURO_ID)
                    ->orderBy('localidades.nombre')
                    ->get(),
            ],
            'delegados' => User::query()
                ->select('id', 'name', 'username', 'celular', 'ci', 'fecha_nacimiento', 'role')
                ->whereIn('role', ['Administrador', 'Delegado de Mesa'])
                ->orderBy('name')
                ->get(),
            'partidos' => $this->partidosSegundaVuelta(),
        ]);
    }

    public function asignarDelegado(Request $request, Mesa $mesa)
    {
        $this->assertMesaOruro($mesa);

        $data = $request->validate([
            'delegado_id' => 'nullable|exists:users,id',
            'estado' => 'nullable|string|max:30',
        ]);

        if (!empty($data['delegado_id'])) {
            $delegado = User::findOrFail($data['delegado_id']);
            if (!in_array($delegado->role, ['Administrador', 'Delegado de Mesa'], true)) {
                return response()->json(['message' => 'El usuario no es Administrador ni Delegado de Mesa'], 422);
            }

            $mesa->delegado_segunda_vuelta_id = (int) $data['delegado_id'];
            $mesa->estado_segunda_vuelta = $data['estado'] ?? 'ASIGNADA';
            $mesa->save();

            return response()->json([
                'message' => 'Delegado de segunda vuelta asignado',
                'row' => $this->freshMesaRow($mesa),
            ]);
        }

        $mesa->delegado_segunda_vuelta_id = null;
        $mesa->estado_segunda_vuelta = 'PENDIENTE';
        $mesa->save();

        return response()->json([
            'message' => 'Mesa liberada para segunda vuelta',
            'row' => $this->freshMesaRow($mesa),
        ]);
    }

    public function asignacionMasiva(Request $request)
    {
        $data = $request->validate([
            'mesa_ids' => 'required|array|min:1',
            'mesa_ids.*' => 'integer|exists:mesas,id',
            'delegado_id' => 'nullable|exists:users,id',
            'estado' => 'nullable|string|max:30',
        ]);

        if (!empty($data['delegado_id'])) {
            $delegado = User::findOrFail($data['delegado_id']);
            if (!in_array($delegado->role, ['Administrador', 'Delegado de Mesa'], true)) {
                return response()->json(['message' => 'El usuario no es Administrador ni Delegado de Mesa'], 422);
            }
        }

        $mesaIds = collect($data['mesa_ids'])
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $count = Mesa::query()
            ->where('departamento_id', self::DEPARTAMENTO_ORURO_ID)
            ->whereIn('id', $mesaIds)
            ->update([
                'delegado_segunda_vuelta_id' => $data['delegado_id'] ?? null,
                'estado_segunda_vuelta' => !empty($data['delegado_id'])
                    ? ($data['estado'] ?? 'ASIGNADA')
                    : 'PENDIENTE',
                'updated_at' => now(),
            ]);

        return response()->json([
            'message' => !empty($data['delegado_id'])
                ? 'Mesas asignadas para segunda vuelta'
                : 'Mesas liberadas para segunda vuelta',
            'updated_count' => $count,
            'rows' => $this->baseMesasQuery()
                ->whereIn('mesas.id', $mesaIds)
                ->orderBy('mesas.numero_mesa')
                ->get()
                ->map(fn (Mesa $mesa) => $this->mapMesaRow($mesa))
                ->values(),
        ]);
    }

    public function resultado(Mesa $mesa)
    {
        $this->assertMesaOruro($mesa);

        $mesa->load([
            'recinto:id,nombre',
            'provincia:id,nombre',
            'municipio:id,nombre',
            'localidad:id,nombre',
            'delegadoSegundaVuelta:id,name,username,celular,ci,fecha_nacimiento',
            'resultadoSegundaVuelta.detalles.partido:id,nombre,sigla,icono',
        ]);

        $resultado = $mesa->resultadoSegundaVuelta;
        if ($resultado) {
            $resultado->foto_pizarra_url = $resultado->foto_pizarra ? Storage::url($resultado->foto_pizarra) : null;
            $resultado->foto_acta_url = $resultado->foto_acta ? Storage::url($resultado->foto_acta) : null;
        }

        return response()->json([
            'mesa' => $this->mapMesaRow($mesa),
            'resultado' => $resultado,
            'partidos' => $this->partidosSegundaVuelta(),
        ]);
    }

    public function guardarResultado(Request $request, Mesa $mesa)
    {
        $this->assertMesaOruro($mesa);

        if (!$mesa->delegado_segunda_vuelta_id) {
            return response()->json(['message' => 'Esta mesa no tiene delegado de segunda vuelta asignado'], 422);
        }

        $data = $request->validate([
            'blancos' => 'nullable|integer|min:0',
            'nulos' => 'nullable|integer|min:0',
            'papeletas_no_utilizadas' => 'nullable|integer|min:0',
            'observacion' => 'nullable|string',
            'votos' => 'required',
            'foto_pizarra' => 'nullable|image|max:4096',
            'foto_acta' => 'nullable|image|max:4096',
            'clear_foto_pizarra' => 'nullable|boolean',
            'clear_foto_acta' => 'nullable|boolean',
        ]);

        $votos = $request->input('votos');
        if (is_string($votos)) {
            $votos = json_decode($votos, true);
        }

        if (!is_array($votos)) {
            return response()->json(['message' => 'Formato invalido de votos'], 422);
        }

        $permitidos = $this->partidosSegundaVuelta()->pluck('id')->map(fn ($id) => (int) $id)->values();
        $votosNormalizados = [];

        foreach ($votos as $row) {
            $partidoId = (int) ($row['partido_id'] ?? 0);
            $votosGobernador = (int) ($row['votos_gobernador'] ?? 0);

            if (!$permitidos->contains($partidoId)) {
                return response()->json(['message' => 'Partido no permitido para segunda vuelta'], 422);
            }

            if ($votosGobernador < 0) {
                return response()->json(['message' => 'Los votos no pueden ser negativos'], 422);
            }

            $votosNormalizados[$partidoId] = $votosGobernador;
        }

        foreach ($permitidos as $partidoId) {
            if (!array_key_exists((int) $partidoId, $votosNormalizados)) {
                $votosNormalizados[(int) $partidoId] = 0;
            }
        }

        $blancos = (int) ($data['blancos'] ?? 0);
        $nulos = (int) ($data['nulos'] ?? 0);
        $papeletasNoUtilizadas = (int) ($data['papeletas_no_utilizadas'] ?? 0);
        $totalValidos = collect($votosNormalizados)->sum();
        $totalVotos = $totalValidos + $blancos + $nulos;

        DB::transaction(function () use ($request, $mesa, $votosNormalizados, $blancos, $nulos, $papeletasNoUtilizadas, $totalValidos, $totalVotos, $data) {
            $resultado = ResultadoMesaSegundaVuelta::query()->firstOrCreate(
                ['mesa_id' => $mesa->id],
                [
                    'registrado_por' => $request->user()->id,
                    'origen_registro' => 'sistema',
                ]
            );

            $resultado->registrado_por = $request->user()->id;
            $resultado->origen_registro = 'sistema';
            $resultado->blancos = $blancos;
            $resultado->nulos = $nulos;
            $resultado->papeletas_no_utilizadas = $papeletasNoUtilizadas;
            $resultado->total_blancos = $blancos;
            $resultado->total_nulos = $nulos;
            $resultado->total_validos = $totalValidos;
            $resultado->total_votos = $totalVotos;
            $resultado->observacion = $data['observacion'] ?? null;

            $dir = "resultados_segunda_vuelta/mesa_{$mesa->id}";

            foreach (['foto_pizarra', 'foto_acta'] as $field) {
                $clearField = 'clear_' . $field;
                if ($request->boolean($clearField)) {
                    if (!empty($resultado->{$field})) {
                        Storage::disk('public')->delete($resultado->{$field});
                    }
                    $resultado->{$field} = null;
                }

                if ($request->hasFile($field)) {
                    if (!empty($resultado->{$field})) {
                        Storage::disk('public')->delete($resultado->{$field});
                    }
                    $resultado->{$field} = $request->file($field)->store($dir, 'public');
                }
            }

            $resultado->save();

            ResultadoMesaSegundaVueltaDetalle::query()
                ->where('resultado_mesa_segunda_vuelta_id', $resultado->id)
                ->whereNotIn('partido_id', array_keys($votosNormalizados))
                ->delete();

            foreach ($votosNormalizados as $partidoId => $votosGobernador) {
                ResultadoMesaSegundaVueltaDetalle::updateOrCreate(
                    [
                        'resultado_mesa_segunda_vuelta_id' => $resultado->id,
                        'partido_id' => (int) $partidoId,
                    ],
                    [
                        'votos_gobernador' => (int) $votosGobernador,
                    ]
                );
            }

            $mesa->estado_segunda_vuelta = 'RESULTADO_REGISTRADO';
            $mesa->save();
        });

        return response()->json([
            'message' => 'Resultado de segunda vuelta guardado',
            'row' => $this->freshMesaRow($mesa),
        ]);
    }

    private function baseMesasQuery()
    {
        return Mesa::query()
            ->select([
                'mesas.id',
                'mesas.departamento_id',
                'mesas.provincia_id',
                'mesas.municipio_id',
                'mesas.localidad_id',
                'mesas.recinto_id',
                'mesas.numero_mesa',
                'mesas.habilitados',
                'mesas.delegado_segunda_vuelta_id',
                'mesas.estado_segunda_vuelta',
            ])
            ->with([
                'departamento:id,nombre',
                'provincia:id,nombre',
                'municipio:id,nombre',
                'localidad:id,nombre',
                'recinto:id,nombre,latitud,longitud',
                'delegadoSegundaVuelta:id,name,username,celular,ci,fecha_nacimiento',
                'resultadoSegundaVuelta:id,mesa_id,total_votos,total_validos,total_blancos,total_nulos,blancos,nulos,papeletas_no_utilizadas,foto_pizarra,foto_acta,updated_at',
                'resultadoSegundaVuelta.detalles:id,resultado_mesa_segunda_vuelta_id,partido_id,votos_gobernador',
                'resultadoSegundaVuelta.detalles.partido:id,nombre,sigla,icono',
            ])
            ->where('mesas.departamento_id', self::DEPARTAMENTO_ORURO_ID)
            ->whereNull('mesas.deleted_at');
    }

    private function mapMesaRow(Mesa $mesa): array
    {
        $resultado = $mesa->resultadoSegundaVuelta;
        $detalles = collect($resultado?->detalles ?? []);

        return [
            'id' => $mesa->id,
            'departamento_id' => $mesa->departamento_id,
            'departamento_nombre' => $mesa->departamento?->nombre,
            'provincia_id' => $mesa->provincia_id,
            'provincia_nombre' => $mesa->provincia?->nombre,
            'municipio_id' => $mesa->municipio_id,
            'municipio_nombre' => $mesa->municipio?->nombre,
            'localidad_id' => $mesa->localidad_id,
            'localidad_nombre' => $mesa->localidad?->nombre,
            'recinto_id' => $mesa->recinto_id,
            'recinto_nombre' => $mesa->recinto?->nombre,
            'numero_mesa' => $mesa->numero_mesa,
            'habilitados' => (int) ($mesa->habilitados ?? 0),
            'delegado_segunda_vuelta_id' => $mesa->delegado_segunda_vuelta_id,
            'delegado_segunda_vuelta' => $mesa->delegadoSegundaVuelta ? [
                'id' => $mesa->delegadoSegundaVuelta->id,
                'name' => $mesa->delegadoSegundaVuelta->name,
                'username' => $mesa->delegadoSegundaVuelta->username,
                'celular' => $mesa->delegadoSegundaVuelta->celular,
                'ci' => $mesa->delegadoSegundaVuelta->ci,
                'fecha_nacimiento' => $mesa->delegadoSegundaVuelta->fecha_nacimiento,
            ] : null,
            'estado_segunda_vuelta' => $mesa->estado_segunda_vuelta,
            'tiene_resultado' => $resultado !== null,
            'total_votos' => (int) ($resultado?->total_votos ?? 0),
            'total_validos' => (int) ($resultado?->total_validos ?? 0),
            'total_blancos' => (int) ($resultado?->total_blancos ?? 0),
            'total_nulos' => (int) ($resultado?->total_nulos ?? 0),
            'blancos' => (int) ($resultado?->blancos ?? 0),
            'nulos' => (int) ($resultado?->nulos ?? 0),
            'papeletas_no_utilizadas' => (int) ($resultado?->papeletas_no_utilizadas ?? 0),
            'foto_pizarra_url' => $resultado?->foto_pizarra ? Storage::url($resultado->foto_pizarra) : null,
            'foto_acta_url' => $resultado?->foto_acta ? Storage::url($resultado->foto_acta) : null,
            'resultado_actualizado_at' => $resultado?->updated_at?->toIso8601String(),
            'votos_partidos' => $detalles
                ->mapWithKeys(fn ($detalle) => [
                    (int) $detalle->partido_id => [
                        'partido_id' => (int) $detalle->partido_id,
                        'partido_nombre' => $detalle->partido?->nombre,
                        'partido_sigla' => $detalle->partido?->sigla,
                        'votos_gobernador' => (int) ($detalle->votos_gobernador ?? 0),
                    ],
                ])
                ->all(),
        ];
    }

    private function partidosSegundaVuelta()
    {
        return Partido::query()
            ->select('id', 'sigla', 'nombre', 'color', 'icono')
            ->whereIn('id', self::PARTIDOS_SEGUNDA_VUELTA)
            ->orderByRaw('CASE id WHEN 11 THEN 1 WHEN 15 THEN 2 ELSE 99 END')
            ->get()
            ->values();
    }

    private function freshMesaRow(Mesa $mesa): array
    {
        $mesa = $this->baseMesasQuery()->where('mesas.id', $mesa->id)->firstOrFail();

        return $this->mapMesaRow($mesa);
    }

    private function assertMesaOruro(Mesa $mesa): void
    {
        abort_if((int) $mesa->departamento_id !== self::DEPARTAMENTO_ORURO_ID, 404);
    }
}
