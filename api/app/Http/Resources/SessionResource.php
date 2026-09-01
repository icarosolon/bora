<?php

namespace App\Http\Resources;

use App\Domain\Account\OpenSession;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Sessão aberta: a conta, o token Bearer e quando ele vence.
 *
 * Este é o ÚNICO lugar em que o token em claro aparece numa resposta — no
 * corpo, nunca na URL (contrato auth-api.md). O `expires_at` é informativo: a
 * janela é deslizante (D7), então cada uso empurra esse prazo para frente.
 *
 * @mixin OpenSession
 */
class SessionResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'account' => new AccountResource($this->account),
            'token' => $this->plainTextToken,
            'expires_at' => $this->expiresAt->format(DATE_ATOM),
        ];
    }
}
