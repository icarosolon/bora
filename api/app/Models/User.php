<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

/**
 * A conta única do Bora (RN-PLAT-001).
 *
 * Uma pessoa tem UMA conta; os papéis (rolezeiro, gestor, artista) são perfis
 * vinculados a ela via spatie/laravel-permission, nunca contas paralelas.
 *
 * `password` é opcional: conta que nasce pelo Google não tem senha até a pessoa
 * definir uma, já autenticada (D1, direção inversa).
 */
#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, HasRoles, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'ultimo_acesso_em' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /** @return HasMany<ContaSocial, $this> */
    public function contasSociais(): HasMany
    {
        return $this->hasMany(ContaSocial::class);
    }

    /** @return HasMany<TokenDeEmail, $this> */
    public function tokensDeEmail(): HasMany
    {
        return $this->hasMany(TokenDeEmail::class);
    }

    /**
     * A conta entra por e-mail/senha? Falso enquanto ela só tiver vínculo social.
     * Usado para orientar a pessoa em vez de dar "senha incorreta" (US2-4).
     */
    public function temSenha(): bool
    {
        return $this->password !== null;
    }

    public function temVinculoCom(string $provedor): bool
    {
        return $this->contasSociais()->where('provedor', $provedor)->exists();
    }
}
