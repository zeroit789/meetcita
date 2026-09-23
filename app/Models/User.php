<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * ES: Modelo User del esqueleto de Laravel. El panel /panel NO lo usa (entra con
 *     la contraseña única de config/appointments.php); se mantiene por compatibilidad.
 * EN: Laravel skeleton User model. The /panel does NOT use it (it logs in with the
 *     single password from config/appointments.php); kept for compatibility.
 */
#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * ES: Conversiones de tipo de los atributos (fecha y contraseña hasheada).
     * EN: Get the attributes that should be cast (date and hashed password).
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
}
