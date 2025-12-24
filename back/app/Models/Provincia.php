<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Provincia extends Model
{
    use SoftDeletes;

    protected $table = 'provincias';

    protected $fillable = [
        'id_original',
        'departamento_id',
        'nombre',
    ];

    public function departamento()
    {
        return $this->belongsTo(Departamento::class, 'departamento_id');
    }

    public function municipios()
    {
        return $this->hasMany(Municipio::class, 'provincia_id');
    }

    public function recintos()
    {
        return $this->hasMany(Recinto::class, 'provincia_id');
    }

    public function mesas()
    {
        return $this->hasMany(Mesa::class, 'provincia_id');
    }
}
