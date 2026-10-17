<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
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
            'last_seen_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /** @return HasMany<SocialAccount, $this> */
    public function socialAccounts(): HasMany
    {
        return $this->hasMany(SocialAccount::class);
    }

    /** @return HasMany<EmailToken, $this> */
    public function emailTokens(): HasMany
    {
        return $this->hasMany(EmailToken::class);
    }

    /**
     * Locais que esta conta criou (`venues.created_by_account_id`). Não muda
     * quando o perfil é transferido. NÃO concede gestão: quem autoriza é
     * `managedVenues()`.
     *
     * @return HasMany<Venue, $this>
     */
    public function venues(): HasMany
    {
        return $this->hasMany(Venue::class, 'created_by_account_id');
    }

    /**
     * Locais que esta conta gerencia hoje (N:N, RN-LOCAL-004) — a MESMA conta
     * gerencia vários locais, nunca contas paralelas (Princípio I). Só vínculos
     * ativos (`revoked_at` nulo); esta é a relação que autoriza.
     *
     * @return BelongsToMany<Venue, $this>
     */
    public function managedVenues(): BelongsToMany
    {
        return $this->belongsToMany(Venue::class, 'venue_managers', 'account_id', 'venue_id')
            ->withPivot(['granted_at', 'granted_by_claim_id', 'revoked_at'])
            ->wherePivotNull('revoked_at');
    }

    /**
     * A conta entra por e-mail/senha? Falso enquanto ela só tiver vínculo social.
     * Usado para orientar a pessoa em vez de dar "senha incorreta" (US2-4).
     */
    public function hasPassword(): bool
    {
        return $this->password !== null;
    }

    public function hasLinkWith(string $provider): bool
    {
        return $this->socialAccounts()->where('provider', $provider)->exists();
    }
}
