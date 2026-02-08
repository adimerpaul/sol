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
        'foto1','foto2','foto3','foto4','foto5','foto6','foto7','foto8','foto9','foto10',
        'latitud','longitud',
        'total_votos','total_validos','total_blancos','total_nulos',
        'blancos_gobernador','nulos_gobernador',
        'blancos_asambleista_distrito','nulos_asambleista_distrito',
        'blancos_asambleista_poblacion','nulos_asambleista_poblacion',
        'blancos_concejal','nulos_concejal',
        'blancos_alcalde','nulos_alcalde',
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
