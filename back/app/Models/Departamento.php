<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Departamento extends Model
{
    use SoftDeletes;

    protected $table = 'departamentos';

    protected $fillable = [
        'id_original',
        'pais_id',
        'nombre',
    ];

    public function pais()
    {
        return $this->belongsTo(Pais::class, 'pais_id');
    }

    public function provincias()
    {
        return $this->hasMany(Provincia::class, 'departamento_id');
    }

    public function recintos()
    {
        return $this->hasMany(Recinto::class, 'departamento_id');
    }

    public function mesas()
    {
        return $this->hasMany(Mesa::class, 'departamento_id');
    }
}
