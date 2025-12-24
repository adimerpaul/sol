<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Mesa extends Model
{
    use SoftDeletes;

    protected $table = 'mesas';

    protected $fillable = [
        'id_original',
        'recinto_id',
        'localidad_id',
        'municipio_id',
        'provincia_id',
        'departamento_id',
        'pais_id',
        'numero_mesa',
    ];

    public function pais() { return $this->belongsTo(Pais::class, 'pais_id'); }
    public function departamento() { return $this->belongsTo(Departamento::class, 'departamento_id'); }
    public function provincia() { return $this->belongsTo(Provincia::class, 'provincia_id'); }
    public function municipio() { return $this->belongsTo(Municipio::class, 'municipio_id'); }
    public function localidad() { return $this->belongsTo(Localidad::class, 'localidad_id'); }
    public function recinto() { return $this->belongsTo(Recinto::class, 'recinto_id'); }
}
