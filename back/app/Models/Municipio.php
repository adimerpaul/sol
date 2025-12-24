<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Municipio extends Model
{
    use SoftDeletes;

    protected $table = 'municipios';

    protected $fillable = [
        'id_original',
        'provincia_id',
        'nombre',
    ];

    public function provincia()
    {
        return $this->belongsTo(Provincia::class, 'provincia_id');
    }

    public function localidades()
    {
        return $this->hasMany(Localidad::class, 'municipio_id');
    }

    public function recintos()
    {
        return $this->hasMany(Recinto::class, 'municipio_id');
    }

    public function mesas()
    {
        return $this->hasMany(Mesa::class, 'municipio_id');
    }
}
