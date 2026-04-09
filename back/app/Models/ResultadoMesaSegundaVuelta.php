<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable;

class ResultadoMesaSegundaVuelta extends Model implements Auditable
{
    use SoftDeletes, AuditableTrait;

    protected $table = 'resultados_mesa_segunda_vuelta';

    protected $fillable = [
        'mesa_id',
        'registrado_por',
        'origen_registro',
        'total_votos',
        'total_validos',
        'total_blancos',
        'total_nulos',
        'blancos',
        'nulos',
        'papeletas_no_utilizadas',
        'foto_pizarra',
        'foto_acta',
        'observacion',
    ];

    public function mesa()
    {
        return $this->belongsTo(Mesa::class, 'mesa_id');
    }

    public function detalles()
    {
        return $this->hasMany(ResultadoMesaSegundaVueltaDetalle::class, 'resultado_mesa_segunda_vuelta_id');
    }

    public function registradoPor()
    {
        return $this->belongsTo(User::class, 'registrado_por');
    }
}
