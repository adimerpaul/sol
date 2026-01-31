<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, Notifiable, SoftDeletes, HasApiTokens, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
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

}
