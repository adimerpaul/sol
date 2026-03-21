<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MesaAiControlDetalle extends Model
{
    protected $table = 'mesa_ai_control_detalles';

    protected $fillable = [
        'mesa_ai_control_id',
        'partido_id',
        'votos_gobernador',
        'votos_asambleista_distrito',
        'votos_asambleista_poblacion',
        'votos_concejal',
        'votos_alcalde',
        'confianza',
        'fuente_json',
    ];

    protected $casts = [
        'fuente_json' => 'array',
        'confianza' => 'decimal:2',
    ];

    public function control()
    {
        return $this->belongsTo(MesaAiControl::class, 'mesa_ai_control_id');
    }

    public function partido()
    {
        return $this->belongsTo(Partido::class);
    }
}
