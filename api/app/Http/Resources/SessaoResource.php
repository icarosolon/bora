<?php

namespace App\Http\Resources;

use App\Domain\Account\SessaoAberta;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Sessão aberta: a conta, o token Bearer e quando ele vence.
 *
 * Este é o ÚNICO lugar em que o token em claro aparece numa resposta — no
 * corpo, nunca na URL (contrato auth-api.md). O `expira_em` é informativo: a
 * janela é deslizante (D7), então cada uso empurra esse prazo para frente.
 *
 * @mixin SessaoAberta
 */
class SessaoResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'conta' => new ContaResource($this->conta),
            'token' => $this->tokenEmClaro,
            'expira_em' => $this->expiraEm->format(DATE_ATOM),
        ];
    }
}
