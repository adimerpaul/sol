<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Localidad extends Model
{
    use SoftDeletes;

    protected $table = 'localidades';

    protected $fillable = [
        'id_original',
        'municipio_id',
        'nombre',
    ];

    public function municipio()
    {
        return $this->belongsTo(Municipio::class, 'municipio_id');
    }

    public function recintos()
    {
        return $this->hasMany(Recinto::class, 'localidad_id');
    }

    public function mesas()
    {
        return $this->hasMany(Mesa::class, 'localidad_id');
    }
}
