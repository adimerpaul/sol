<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Auditable as AuditableTrait;

class ResultadoMesa extends Model implements Auditable
{
    use SoftDeletes, AuditableTrait;

    protected $table = 'resultados_mesa';

    protected $fillable = [
        'mesa_id','registrado_por','origen_registro',
        'aviso_antes','aviso_manana','aviso_mediodia','aviso_tarde',
        'etapa_1','etapa_2',
        'hora_apertura_mesa',
        'foto1','foto2','foto3','foto4','foto5','foto6','foto7','foto8','foto9','foto10',
        'latitud','longitud',
        'total_votos','total_validos','total_blancos','total_nulos',
        'blancos_gobernador','nulos_gobernador',
        'papeletas_no_utilizadas_gobernador',
        'blancos_asambleista_distrito','nulos_asambleista_distrito',
        'papeletas_no_utilizadas_asambleista_distrito',
        'blancos_asambleista_poblacion','nulos_asambleista_poblacion',
        'papeletas_no_utilizadas_asambleista_poblacion',
        'blancos_concejal','nulos_concejal',
        'papeletas_no_utilizadas_concejal',
        'blancos_alcalde','nulos_alcalde',
        'papeletas_no_utilizadas_alcalde',
        'observacion',
        'observacion_gobernador',
        'observacion_asambleista_distrito',
        'observacion_asambleista_poblacion',
        'observacion_concejal',
        'observacion_alcalde'
    ];

    protected $casts = [
        'aviso_antes' => 'boolean',
        'aviso_manana' => 'boolean',
        'aviso_mediodia' => 'boolean',
        'aviso_tarde' => 'boolean',
        'etapa_1' => 'boolean',
        'etapa_2' => 'boolean',
        'hora_apertura_mesa' => 'string',
    ];

    public function mesa()     { return $this->belongsTo(Mesa::class); }
    public function detalles() { return $this->hasMany(ResultadoMesaDetalle::class, 'resultado_mesa_id'); }
    public function registradoPor() { return $this->belongsTo(User::class, 'registrado_por'); }
}
