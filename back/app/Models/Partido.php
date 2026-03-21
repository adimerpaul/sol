<?php

// app/Models/Partido.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Auditable as AuditableTrait;

class Partido extends Model implements Auditable
{
    use SoftDeletes, AuditableTrait;
    protected $fillable = [
        'sigla',
        'nombre',
        'icono',
        'tipo',
//        'alcalde',
        'color',
        'orden_municipal',
        'orden_departamental'
    ];
    protected $hidden=[
        'deleted_at',
        'created_at',
        'updated_at'
    ];

    public function aiControlDetalles()
    {
        return $this->hasMany(MesaAiControlDetalle::class);
    }
}
