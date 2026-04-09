<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Auditable as AuditableTrait;

class Mesa extends Model implements Auditable
{
    use SoftDeletes, AuditableTrait;

    protected $fillable = [
        'recinto_id','localidad_id','municipio_id','provincia_id','departamento_id','pais_id',
        'numero_mesa','habilitados','delegado_id','delegado_segunda_vuelta_id','estado','estado_segunda_vuelta','asistencia_capacitacion',
        'delegado_latitud','delegado_longitud','delegado_presente_at'
    ];

    protected $casts = [
        'asistencia_capacitacion' => 'boolean',
        'delegado_presente_at' => 'datetime',
    ];
    public function departamento() { return $this->belongsTo(Departamento::class); }
    public function provincia()   { return $this->belongsTo(Provincia::class); }
    public function municipio()  { return $this->belongsTo(Municipio::class); }
    public function localidad()  { return $this->belongsTo(Localidad::class); }
//    pais
    public function pais()      { return $this->belongsTo(Pais::class); }


    public function recinto()   { return $this->belongsTo(Recinto::class); }
    public function delegado()  { return $this->belongsTo(User::class, 'delegado_id'); }
    public function delegadoSegundaVuelta() { return $this->belongsTo(User::class, 'delegado_segunda_vuelta_id'); }
    public function resultado() { return $this->hasOne(ResultadoMesa::class, 'mesa_id'); }
    public function resultadoSegundaVuelta() { return $this->hasOne(ResultadoMesaSegundaVuelta::class, 'mesa_id'); }
    public function aiControles() { return $this->hasMany(MesaAiControl::class, 'mesa_id'); }
}
