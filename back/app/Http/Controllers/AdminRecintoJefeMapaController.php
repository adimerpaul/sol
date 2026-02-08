<?php
namespace App\Http\Controllers;

use App\Models\Recinto;
use App\Models\User;
use Illuminate\Http\Request;

class AdminRecintoJefeMapaController extends Controller
{
    /**
     * Recintos visibles para el usuario logueado
     */
    public function recintos(Request $request)
    {
        $user = $request->user();

        // Admin ve TODOS sus recintos asignados
        if ($user->role === 'Administrador') {
            return Recinto::with(['jefe:id,name,username'])
                ->withCount([
                    'mesas',
                    'mesas as mesas_asignadas_count' => function ($qq) {
                        $qq->whereNotNull('delegado_id');
                    }
                ])
//                ->whereHas('users', fn ($q) => $q->where('users.id', $user->id))
                //        &pais_id=1&departamento_id=5&provincia_id=57&municipio_id=191&localidad_id=1988&page=1&per_page=10
                ->where('pais_id', 1)
                ->where('departamento_id', 5)
                ->where('provincia_id', 57)
                ->where('municipio_id', 191)
                ->where('localidad_id', 1988)
                ->get()
                ->map(function ($r) {
                    $total = (int)($r->mesas_count ?? 0);
                    $asignadas = (int)($r->mesas_asignadas_count ?? 0);
                    $delegadosOk = $total > 0 ? ($asignadas >= $total) : true;
                    $r->mesas_total = $total;
                    $r->mesas_asignadas = $asignadas;
                    $r->delegados_ok = $delegadosOk;
                    $r->mesas_faltan = max(0, $total - $asignadas);
                    return $r;
                });
        }

        // Supervisor ve SOLO sus recintos
        if ($user->role === 'Supervisor') {
            return Recinto::with(['jefe:id,name,username'])
                ->withCount([
                    'mesas',
                    'mesas as mesas_asignadas_count' => function ($qq) {
                        $qq->whereNotNull('delegado_id');
                    }
                ])
                ->whereHas('users', fn ($q) => $q->where('users.id', $user->id))
                ->get()
                ->map(function ($r) {
                    $total = (int)($r->mesas_count ?? 0);
                    $asignadas = (int)($r->mesas_asignadas_count ?? 0);
                    $delegadosOk = $total > 0 ? ($asignadas >= $total) : true;
                    $r->mesas_total = $total;
                    $r->mesas_asignadas = $asignadas;
                    $r->delegados_ok = $delegadosOk;
                    $r->mesas_faltan = max(0, $total - $asignadas);
                    return $r;
                });
        }

        return response()->json([], 403);
    }

    /**
     * Jefes disponibles para asignar
     */
    public function jefes(Request $request)
    {
        $user = $request->user();

        if (!in_array($user->role, ['Administrador','Supervisor'])) {
            return response()->json([], 403);
        }
        if ($user->role === 'Administrador') {
            return User::where('role', 'Jefe de Recinto')
                ->select('id','name','username')
                ->orderBy('name')
                ->get();

        }

        return $user->jefesAsignados()
            ->select('users.id','users.name','users.username')  // ✅ prefijo users.
            ->orderBy('users.name')
            ->get();
    }


    /**
     * Asignar jefe a recinto
     */
    public function asignar(Request $request, Recinto $recinto)
    {
        $data = $request->validate([
            'jefe_id' => 'required|exists:users,id'
        ]);

        // validar que el jefe sea JEFE DE RECINTO
        $jefe = User::findOrFail($data['jefe_id']);
        if ($jefe->role !== 'Jefe de Recinto') {
            return response()->json(['message'=>'Usuario no es Jefe de Recinto'], 422);
        }

        // reemplaza asignación
        $recinto->jefe()->sync([$jefe->id]);

        return response()->json(['ok'=>true]);
    }
}
