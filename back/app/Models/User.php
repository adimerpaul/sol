<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements Auditable
{
    use HasFactory, Notifiable, SoftDeletes, HasApiTokens, HasRoles, AuditableTrait;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'created_by',
        'recinto_id',
        'nombres',
        'apellido_paterno',
        'apellido_materno',
        'ci',
        'fecha_nacimiento',
        'celular',
        'bloque',

        'ci_anverso',
        'ci_reverso',
        'foto_personal',

        'name',        // si aún lo usas
        'username',
        'role',
        'avatar',
        'email',
        'password',
    ];


    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'deleted_at',
        'created_at',
        'updated_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
//            'fecha_nacimiento' => 'date',
        ];
    }
    public function recintos()
    {
        return $this->belongsToMany(\App\Models\Recinto::class, 'recinto_user')
            ->withTimestamps();
    }
    // Supervisor -> muchos Jefes
    public function jefesAsignados()
    {
        return $this->belongsToMany(\App\Models\User::class, 'supervisor_jefe', 'supervisor_id', 'jefe_id')
            ->wherePivotNull('deleted_at')
            ->withTimestamps();
    }

// Jefe -> muchos Supervisores (por si lo necesitas)
    public function supervisores()
    {
        return $this->belongsToMany(\App\Models\User::class, 'supervisor_jefe', 'jefe_id', 'supervisor_id')
            ->wherePivotNull('deleted_at')
            ->withTimestamps();
    }

// Jefe -> muchos Delegados
    public function delegadosAsignados()
    {
        return $this->belongsToMany(\App\Models\User::class, 'jefe_delegado', 'jefe_id', 'delegado_id')
            ->wherePivotNull('deleted_at')
            ->withTimestamps();
    }

// Delegado -> muchos Jefes (por si lo necesitas)
    public function jefes()
    {
        return $this->belongsToMany(\App\Models\User::class, 'jefe_delegado', 'delegado_id', 'jefe_id')
            ->wherePivotNull('deleted_at')
            ->withTimestamps();
    }
    // jefes que administro (supervisor → jefes)
//    public function jefesAsignados()
//    {
//        return $this->belongsToMany(
//            User::class,
//            'supervisor_jefe',
//            'supervisor_id',
//            'jefe_id'
//        );
//    }

// recintos donde soy jefe
    public function recintosComoJefe()
    {
        return $this->belongsToMany(
            Recinto::class,
            'recinto_jefe',
            'jefe_id',
            'recinto_id'
        );
    }

    public function recinto()
    {
        return $this->belongsTo(Recinto::class, 'recinto_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function createdUsers()
    {
        return $this->hasMany(User::class, 'created_by');
    }


}
