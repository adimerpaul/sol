<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Auditable as AuditableTrait;

class ResultadoMesaDetalle extends Model implements Auditable
{
    use SoftDeletes, AuditableTrait;

    protected $table = 'resultado_mesa_detalles';

    protected $fillable = [
        'resultado_mesa_id',
        'partido_id',
        'votos_gobernador',
        'votos_asambleista_distrito',
        'votos_asambleista_poblacion',
        'votos_concejal',
        'votos_alcalde',
    ];

    public function resultado() { return $this->belongsTo(ResultadoMesa::class, 'resultado_mesa_id'); }
    public function partido()   { return $this->belongsTo(Partido::class); }
}
