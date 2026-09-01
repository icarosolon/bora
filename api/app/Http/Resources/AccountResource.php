<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A conta como a API a expõe (Princípio IV: nunca Model serializado direto).
 *
 * O que NÃO sai daqui, de propósito: `password` (mesmo hasheada),
 * `remember_token` e qualquer token de sessão. Datas em ISO 8601.
 *
 * @mixin User
 */
class AccountResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'email_verified' => $this->email_verified_at !== null,
            'roles' => $this->getRoleNames()->values()->all(),
            // Informa à tela quais caminhos de entrada esta conta tem — é o que
            // permite orientar ("esta conta entra com Google") em vez de dar
            // erro genérico.
            'signs_in_with' => array_values(array_filter([
                $this->hasPassword() ? 'password' : null,
                ...$this->socialAccounts->pluck('provider')->all(),
            ])),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
