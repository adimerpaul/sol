<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable;

class ResultadoMesaSegundaVueltaDetalle extends Model implements Auditable
{
    use SoftDeletes, AuditableTrait;

    protected $table = 'resultado_mesa_segunda_vuelta_detalles';

    protected $fillable = [
        'resultado_mesa_segunda_vuelta_id',
        'partido_id',
        'votos_gobernador',
    ];

    public function resultado()
    {
        return $this->belongsTo(ResultadoMesaSegundaVuelta::class, 'resultado_mesa_segunda_vuelta_id');
    }

    public function partido()
    {
        return $this->belongsTo(Partido::class);
    }
}
