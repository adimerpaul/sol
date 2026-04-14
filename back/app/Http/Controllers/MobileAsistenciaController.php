<?php

namespace App\Http\Controllers;

use App\Models\Asistencia;
use App\Models\Mesa;
use App\Models\Recinto;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class MobileAsistenciaController extends Controller
{
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

        $rows = Asistencia::query()
            ->whereIn('mesa_id', $mesas)
            ->get($this->asistenciaFields);

        $state = [];
        foreach ($this->asistenciaFields as $field) {
            if ($rows->isEmpty()) {
                $state[$field] = $field === 'hora_apertura_mesa' ? null : false;
                continue;
            }

            $state[$field] = $field === 'hora_apertura_mesa'
                ? $rows->pluck('hora_apertura_mesa')->filter()->first()
                : $rows->every(fn ($row) => (bool) ($row->{$field} ?? false));
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
            $alreadyTrue = Asistencia::query()
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
                $asistencia = Asistencia::query()->firstOrCreate(
                    ['mesa_id' => $mesa->id],
                    ['delegado_id' => $mesa->delegado_id]
                );

                $asistencia->delegado_id = $mesa->delegado_id;
                $this->applyField($asistencia, $data, $user->id);
                $asistencia->save();

                if (
                    (bool) $asistencia->aviso_antes ||
                    (bool) $asistencia->aviso_manana ||
                    (bool) $asistencia->aviso_mediodia ||
                    (bool) $asistencia->aviso_tarde
                ) {
                    $mesa->estado = 'EN_PROCESO';
                } else {
                    $mesa->estado = $mesa->delegado_id ? 'ASIGNADA' : 'PENDIENTE';
                }

                if (($data['field'] ?? null) === 'aviso_antes' && (bool) ($data['value'] ?? false)) {
                    if (array_key_exists('latitud', $data)) {
                        $mesa->delegado_latitud = isset($data['latitud']) ? (string) $data['latitud'] : null;
                    }
                    if (array_key_exists('longitud', $data)) {
                        $mesa->delegado_longitud = isset($data['longitud']) ? (string) $data['longitud'] : null;
                    }
                    $mesa->delegado_presente_at = !empty($data['presente_at'])
                        ? Carbon::parse($data['presente_at'])
                        : now();
                }

                $mesa->save();
            }
        });

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

    public function delegadoUpdate(Request $request)
    {
        $data = $request->validate([
            'mesa_id' => 'required|integer|exists:mesas,id',
            'field' => 'required|string|in:aviso_manana,aviso_mediodia,aviso_tarde',
            'value' => 'required|boolean',
        ]);

        $user = $request->user();
        $mesa = Mesa::query()
            ->with(['delegado:id,name', 'recinto:id,nombre'])
            ->findOrFail((int) $data['mesa_id']);

        if (!$this->canManageMesaAsistencia($user, $mesa)) {
            return response()->json(['message' => 'No autorizado para registrar asistencia de esta mesa'], 403);
        }

        if (!$mesa->delegado_id) {
            return response()->json(['message' => 'La mesa no tiene delegado asignado'], 422);
        }

        if ((bool) $data['value'] === false) {
            $alreadyTrue = Asistencia::query()
                ->where('mesa_id', $mesa->id)
                ->where($data['field'], true)
                ->exists();

            if ($alreadyTrue) {
                return response()->json([
                    'message' => 'Este campo ya fue confirmado y no puede revertirse',
                ], 422);
            }
        }

        $asistencia = null;
        DB::transaction(function () use ($mesa, $user, $data, &$asistencia) {
            $asistencia = Asistencia::query()->firstOrCreate(
                ['mesa_id' => $mesa->id],
                ['delegado_id' => $mesa->delegado_id]
            );

            $asistencia->delegado_id = $mesa->delegado_id;
            $this->applyField($asistencia, $data, $user->id);
            $asistencia->save();
        });

        return response()->json([
            'ok' => true,
            'mesa_id' => $mesa->id,
            'delegado_id' => $mesa->delegado_id,
            'delegado_nombre' => $mesa->delegado?->name,
            'recinto_nombre' => $mesa->recinto?->nombre,
            'field' => $data['field'],
            'value' => (bool) $data['value'],
            'registrado_por' => $user->id,
            'registrado_at' => $this->resolveFieldTimestamp($asistencia, (string) $data['field'])?->toIso8601String(),
        ]);
    }

    private function canManageMesaAsistencia(User $user, Mesa $mesa): bool
    {
        if (!$mesa->recinto_id) {
            return false;
        }

        $jefeRecintoIds = $user->recintosComoJefe()
            ->pluck('recintos.id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        if ($jefeRecintoIds->isNotEmpty()) {
            return $jefeRecintoIds->contains((int) $mesa->recinto_id);
        }

        if ($user->role === 'Supervisor') {
            return Recinto::query()
                ->where('id', $mesa->recinto_id)
                ->whereHas('users', fn ($q) => $q->where('users.id', $user->id))
                ->exists();
        }

        if ($user->role === 'Administrador') {
            return Recinto::query()
                ->where('id', $mesa->recinto_id)
                ->where('pais_id', 1)
                ->where('departamento_id', 5)
                ->exists();
        }

        return false;
    }

    private function resolveFieldTimestamp(Asistencia $asistencia, string $field): ?Carbon
    {
        return match ($field) {
            'aviso_manana' => $asistencia->aviso_manana_at,
            'aviso_mediodia' => $asistencia->aviso_mediodia_at,
            'aviso_tarde' => $asistencia->aviso_tarde_at,
            'aviso_antes' => $asistencia->aviso_antes_at,
            default => null,
        };
    }

    private function applyField(Asistencia $asistencia, array $data, int $userId): void
    {
        $field = (string) $data['field'];
        $value = (bool) $data['value'];
        $now = now();

        $asistencia->{$field} = $value;

        if ($field === 'aviso_antes' && $value) {
            $asistencia->aviso_antes_at = $now;
            $asistencia->aviso_antes_by = $userId;
            $asistencia->presente_latitud = $data['latitud'] ?? $asistencia->presente_latitud;
            $asistencia->presente_longitud = $data['longitud'] ?? $asistencia->presente_longitud;
            $asistencia->presente_at = !empty($data['presente_at']) ? Carbon::parse($data['presente_at']) : $now;
        }

        if ($field === 'aviso_manana' && $value) {
            $asistencia->aviso_manana_at = $now;
            $asistencia->aviso_manana_by = $userId;
            $asistencia->hora_apertura_mesa = $data['hora_apertura_mesa'] ?? $asistencia->hora_apertura_mesa;
        }

        if ($field === 'aviso_mediodia' && $value) {
            $asistencia->aviso_mediodia_at = $now;
            $asistencia->aviso_mediodia_by = $userId;
        }

        if ($field === 'aviso_tarde' && $value) {
            $asistencia->aviso_tarde_at = $now;
            $asistencia->aviso_tarde_by = $userId;
        }
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
}
