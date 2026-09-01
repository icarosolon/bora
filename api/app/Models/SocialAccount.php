<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Vínculo entre a conta única e uma identidade externa (RN-PLAT-002).
 *
 * O vínculo é pelo `provider_user_id` — o identificador estável do provedor —,
 * nunca pelo e-mail: se a pessoa trocar o e-mail da conta Google, ela continua
 * entrando na mesma conta do Bora.
 */
class SocialAccount extends Model
{
    protected $table = 'social_accounts';

    /** Mass assignment explícito (Princípio V); $guarded = [] é proibido. */
    protected $fillable = [
        'user_id',
        'provider',
        'provider_user_id',
        'provider_email',
        'linked_at',
    ];

    protected function casts(): array
    {
        return [
            'linked_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
