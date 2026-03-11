<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Auditable as AuditableTrait;

class Municipio extends Model implements Auditable
{
    use SoftDeletes, AuditableTrait;

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

    public function partidos()
    {
        return $this->belongsToMany(Partido::class, 'municipio_partido', 'municipio_id', 'partido_id')
            ->withPivot([
                'habilitado_gobernador',
                'habilitado_asambleista_poblacion',
                'habilitado_asambleista_distrito',
                'habilitado_alcalde',
                'habilitado_concejal',
            ])
            ->withTimestamps();
    }
}
