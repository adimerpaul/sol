<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ResultadoMesa extends Model
{
    use SoftDeletes;

    protected $table = 'resultados_mesas';

    protected $fillable = [
        'user_id',
        'pais_id',
        'departamento_id',
        'provincia_id',
        'municipio_id',
        'localidad_id',
        'recinto_id',
        'mesa_id',
        'resultados',
        'total_votos',
        'foto_1',
        'foto_2',
        'estado',
        'verificado',
        'observacion',
        'fecha_hora',
    ];

    protected $casts = [
        'resultados' => 'array',
        'verificado' => 'boolean',
        'fecha_hora' => 'datetime',
    ];

    public function mesa()     { return $this->belongsTo(Mesa::class); }
    public function recinto()  { return $this->belongsTo(Recinto::class); }
    public function user()     { return $this->belongsTo(User::class); }
}
