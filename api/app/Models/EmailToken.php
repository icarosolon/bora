<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Link de uso único enviado por e-mail (spec 001).
 *
 * Serve às três finalidades com a mesma semântica: gerar, enviar, validar uma
 * vez, expirar. Guarda apenas o HASH — o valor em claro vai no e-mail e nunca
 * é persistido.
 */
class EmailToken extends Model
{
    public const EMAIL_VERIFICATION = 'email_verification';

    public const CREDENTIAL_MERGE = 'credential_merge';

    public const PASSWORD_RESET = 'password_reset';

    protected $table = 'email_tokens';

    /** Mass assignment explícito (Princípio V); $guarded = [] é proibido. */
    protected $fillable = [
        'user_id',
        'purpose',
        'token_hash',
        'expires_at',
        'used_at',
        'payload',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'used_at' => 'datetime',
            'payload' => 'array',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Ainda não usado — é o que garante o uso único. */
    public function scopeUnused(Builder $query): Builder
    {
        return $query->whereNull('used_at');
    }

    /** Dentro do prazo. */
    public function scopeNotExpired(Builder $query): Builder
    {
        return $query->where('expires_at', '>', now());
    }

    /** Utilizável: não usado E não expirado. */
    public function scopeValid(Builder $query): Builder
    {
        return $query->unused()->notExpired();
    }

    public function isValid(): bool
    {
        return $this->used_at === null && $this->expires_at->isFuture();
    }
}
