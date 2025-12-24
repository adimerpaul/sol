<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Recinto extends Model
{
    use SoftDeletes;

    protected $table = 'recintos';

    protected $fillable = [
        'id_original',
        'localidad_id',
        'municipio_id',
        'provincia_id',
        'departamento_id',
        'pais_id',
        'nombre',
    ];

    public function pais() { return $this->belongsTo(Pais::class, 'pais_id'); }
    public function departamento() { return $this->belongsTo(Departamento::class, 'departamento_id'); }
    public function provincia() { return $this->belongsTo(Provincia::class, 'provincia_id'); }
    public function municipio() { return $this->belongsTo(Municipio::class, 'municipio_id'); }
    public function localidad() { return $this->belongsTo(Localidad::class, 'localidad_id'); }

    public function mesas()
    {
        return $this->hasMany(Mesa::class, 'recinto_id');
    }
    public function users()
    {
        return $this->belongsToMany(\App\Models\User::class, 'recinto_user')
            ->withTimestamps();
    }
}
