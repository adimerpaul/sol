<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ResultadoMesa extends Model
{
    use SoftDeletes;

    protected $table = 'resultados_mesa';

    protected $fillable = [
        'mesa_id','registrado_por',
        'aviso_antes','aviso_manana','aviso_mediodia','aviso_tarde',
        'etapa_1','etapa_2',
        'total_votos','total_validos','total_blancos','total_nulos',
        'observacion'
    ];

    protected $casts = [
        'aviso_antes' => 'boolean',
        'aviso_manana' => 'boolean',
        'aviso_mediodia' => 'boolean',
        'aviso_tarde' => 'boolean',
        'etapa_1' => 'boolean',
        'etapa_2' => 'boolean',
    ];

    public function mesa()     { return $this->belongsTo(Mesa::class); }
    public function detalles() { return $this->hasMany(ResultadoMesaDetalle::class, 'resultado_mesa_id'); }
}
