<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Vínculo entre a conta única e uma identidade externa (RN-PLAT-002).
 *
 * O vínculo é pelo `provedor_user_id` — o identificador estável do provedor —,
 * nunca pelo e-mail: se a pessoa trocar o e-mail da conta Google, ela continua
 * entrando na mesma conta do Bora.
 */
class ContaSocial extends Model
{
    protected $table = 'contas_sociais';

    /** Mass assignment explícito (Princípio V); $guarded = [] é proibido. */
    protected $fillable = [
        'user_id',
        'provedor',
        'provedor_user_id',
        'email_no_provedor',
        'vinculado_em',
    ];

    protected function casts(): array
    {
        return [
            'vinculado_em' => 'datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
