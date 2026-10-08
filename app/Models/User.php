<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;

#[Fillable(['name', 'email', 'password', 'rol', 'cargo', 'fecha_ingreso', 'activo', 'debe_cambiar_password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

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
            'activo' => 'boolean',
            'debe_cambiar_password' => 'boolean',
            'fecha_ingreso' => 'date',
        ];
    }
    public function esAdmin(): bool
    {
        return $this->rol === 'admin';
    }

    public function areas()
    {
        return $this->belongsToMany(Area::class);
    }

    public function lecciones()
    {
        return $this->belongsToMany(Leccion::class)->withPivot('completada_en');
    }

    public function modulosAprobados()
    {
        return $this->hasMany(ModuloAprobado::class);
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->esAdmin();
    }
}
