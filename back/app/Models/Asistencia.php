<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Asistencia extends Model
{
    protected $table = 'asistencias';

    protected $fillable = [
        'mesa_id',
        'delegado_id',
        'aviso_antes',
        'aviso_antes_at',
        'aviso_antes_by',
        'aviso_manana',
        'aviso_manana_at',
        'aviso_manana_by',
        'aviso_mediodia',
        'aviso_mediodia_at',
        'aviso_mediodia_by',
        'aviso_tarde',
        'aviso_tarde_at',
        'aviso_tarde_by',
        'hora_apertura_mesa',
        'presente_latitud',
        'presente_longitud',
        'presente_at',
    ];

    protected $casts = [
        'aviso_antes' => 'boolean',
        'aviso_manana' => 'boolean',
        'aviso_mediodia' => 'boolean',
        'aviso_tarde' => 'boolean',
        'aviso_antes_at' => 'datetime',
        'aviso_manana_at' => 'datetime',
        'aviso_mediodia_at' => 'datetime',
        'aviso_tarde_at' => 'datetime',
        'presente_at' => 'datetime',
        'presente_latitud' => 'float',
        'presente_longitud' => 'float',
        'hora_apertura_mesa' => 'string',
    ];

    public function mesa()
    {
        return $this->belongsTo(Mesa::class, 'mesa_id');
    }
}
