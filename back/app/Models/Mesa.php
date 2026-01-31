<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Mesa extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'recinto_id','localidad_id','municipio_id','provincia_id','departamento_id','pais_id',
        'numero_mesa','delegado_id','estado'
    ];

    public function recinto()   { return $this->belongsTo(Recinto::class); }
    public function delegado()  { return $this->belongsTo(User::class, 'delegado_id'); }
    public function resultado() { return $this->hasOne(ResultadoMesa::class, 'mesa_id'); }
}
