<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable;

class MesaAiControl extends Model implements Auditable
{
    use SoftDeletes, AuditableTrait;

    protected $table = 'mesa_ai_controles';

    protected $fillable = [
        'mesa_id',
        'resultado_mesa_id',
        'registrado_por',
        'fuente_tipo',
        'fuente_slot',
        'fuente_slot_secundaria',
        'imagen_path',
        'imagen_path_secundaria',
        'modelo',
        'estado',
        'total_detectado',
        'blancos_gobernador',
        'nulos_gobernador',
        'papeletas_no_utilizadas_gobernador',
        'blancos_asambleista_distrito',
        'nulos_asambleista_distrito',
        'papeletas_no_utilizadas_asambleista_distrito',
        'blancos_asambleista_poblacion',
        'nulos_asambleista_poblacion',
        'papeletas_no_utilizadas_asambleista_poblacion',
        'blancos_concejal',
        'nulos_concejal',
        'papeletas_no_utilizadas_concejal',
        'blancos_alcalde',
        'nulos_alcalde',
        'papeletas_no_utilizadas_alcalde',
        'resumen_json',
        'respuesta_json',
        'respuesta_raw',
        'observaciones',
        'confirmado_at',
    ];

    protected $casts = [
        'resumen_json' => 'array',
        'respuesta_json' => 'array',
        'confirmado_at' => 'datetime',
    ];

    public function mesa()
    {
        return $this->belongsTo(Mesa::class);
    }

    public function resultadoMesa()
    {
        return $this->belongsTo(ResultadoMesa::class, 'resultado_mesa_id');
    }

    public function registradoPor()
    {
        return $this->belongsTo(User::class, 'registrado_por');
    }

    public function detalles()
    {
        return $this->hasMany(MesaAiControlDetalle::class);
    }
}
