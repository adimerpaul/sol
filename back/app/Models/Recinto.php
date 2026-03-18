<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Auditable as AuditableTrait;

class Recinto extends Model implements Auditable
{
    use SoftDeletes, AuditableTrait;

    protected $table = 'recintos';

    protected $fillable = [
        'id_original',
        'localidad_id',
        'municipio_id',
        'provincia_id',
        'departamento_id',
        'pais_id',
        'distrito',
        'circunscripcion',
        'latitud',
        'longitud',
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
    public function jefe()
    {
        return $this->belongsToMany(
            User::class,
            'recinto_jefe',
            'recinto_id',
            'jefe_id'
        )->withPivot('super_jefe')->withTimestamps()->withTrashed();
    }

}
