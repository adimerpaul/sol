<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Auditable as AuditableTrait;

class Pais extends Model implements Auditable
{
    use SoftDeletes, AuditableTrait;

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
