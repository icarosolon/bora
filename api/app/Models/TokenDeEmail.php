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
class TokenDeEmail extends Model
{
    public const VERIFICACAO_EMAIL = 'verificacao_email';

    public const UNIAO_CREDENCIAIS = 'uniao_credenciais';

    public const REDEFINICAO_SENHA = 'redefinicao_senha';

    protected $table = 'tokens_de_email';

    /** Mass assignment explícito (Princípio V); $guarded = [] é proibido. */
    protected $fillable = [
        'user_id',
        'finalidade',
        'token_hash',
        'expira_em',
        'usado_em',
        'dados',
    ];

    protected function casts(): array
    {
        return [
            'expira_em' => 'datetime',
            'usado_em' => 'datetime',
            'dados' => 'array',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Ainda não usado — é o que garante o uso único. */
    public function scopeNaoUsado(Builder $query): Builder
    {
        return $query->whereNull('usado_em');
    }

    /** Dentro do prazo. */
    public function scopeNaoExpirado(Builder $query): Builder
    {
        return $query->where('expira_em', '>', now());
    }

    /** Utilizável: não usado E não expirado. */
    public function scopeValido(Builder $query): Builder
    {
        return $query->naoUsado()->naoExpirado();
    }

    public function estaValido(): bool
    {
        return $this->usado_em === null && $this->expira_em->isFuture();
    }
}
