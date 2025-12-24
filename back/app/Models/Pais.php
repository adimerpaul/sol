<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Pais extends Model
{
    use SoftDeletes;

    protected $table = 'paises';

    protected $fillable = [
        'id_original',
        'nombre',
    ];

    public function departamentos()
    {
        return $this->hasMany(Departamento::class, 'pais_id');
    }

    public function recintos()
    {
        return $this->hasMany(Recinto::class, 'pais_id');
    }

    public function mesas()
    {
        return $this->hasMany(Mesa::class, 'pais_id');
    }
}
