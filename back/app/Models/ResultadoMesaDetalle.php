<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ResultadoMesaDetalle extends Model
{
    use SoftDeletes;

    protected $table = 'resultado_mesa_detalles';

    protected $fillable = ['resultado_mesa_id','partido_id','votos'];

    public function resultado() { return $this->belongsTo(ResultadoMesa::class, 'resultado_mesa_id'); }
    public function partido()   { return $this->belongsTo(Partido::class); }
}
