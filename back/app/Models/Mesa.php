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
        'numero_mesa','delegado_id','estado','asistencia_capacitacion'
    ];
    public function departamento() { return $this->belongsTo(Departamento::class); }
    public function provincia()   { return $this->belongsTo(Provincia::class); }
    public function municipio()  { return $this->belongsTo(Municipio::class); }
    public function localidad()  { return $this->belongsTo(Localidad::class); }
//    pais
    public function pais()      { return $this->belongsTo(Pais::class); }


    public function recinto()   { return $this->belongsTo(Recinto::class); }
    public function delegado()  { return $this->belongsTo(User::class, 'delegado_id'); }
    public function resultado() { return $this->hasOne(ResultadoMesa::class, 'mesa_id'); }
}
