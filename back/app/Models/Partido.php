<?php

// app/Models/Partido.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Partido extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'sigla',
        'nombre',
        'icono',
        'tipo',
        'alcalde'
    ];
    protected $hidden=[
        'deleted_at',
        'created_at',
        'updated_at'
    ];
}
