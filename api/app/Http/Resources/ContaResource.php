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
class ContaResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nome' => $this->name,
            'email' => $this->email,
            'email_verificado' => $this->email_verified_at !== null,
            'papeis' => $this->getRoleNames()->values()->all(),
            // Informa à tela quais caminhos de entrada esta conta tem — é o que
            // permite orientar ("esta conta entra com Google") em vez de dar
            // erro genérico.
            'entra_com' => array_values(array_filter([
                $this->temSenha() ? 'senha' : null,
                ...$this->contasSociais->pluck('provedor')->all(),
            ])),
            'criada_em' => $this->created_at?->toIso8601String(),
        ];
    }
}
