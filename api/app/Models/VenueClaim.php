<?php

namespace App\Models;

use App\Enums\VenueClaimStatus;
use Database\Factories\VenueClaimFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Pedido de reivindicação de um local (FR-026). Estados: pending, approved,
 * rejected. Vários `pending` por local são permitidos (FR-022); `decision_reason`
 * é obrigatório quando rejected (FR-021) — regra do caso de uso, não do model.
 */
class VenueClaim extends Model
{
    /** @use HasFactory<VenueClaimFactory> */
    use HasFactory;

    protected $table = 'venue_claims';

    /**
     * Mass assignment mínimo (Princípio V; $guarded = [] é proibido): só a
     * evidência que o solicitante informa. Local, conta, estado e decisão
     * são atribuídos pelo caso de uso, nunca pela requisição.
     */
    protected $fillable = [
        'requester_name',
        'requester_role',
        'best_contact_time',
        'ask_for',
    ];

    /** Evidência e conta do solicitante não saem em resposta pública. */
    protected $hidden = [
        'requester_name',
        'requester_role',
        'best_contact_time',
        'ask_for',
        'account_id',
    ];

    protected function casts(): array
    {
        return [
            'status' => VenueClaimStatus::class,
            'decided_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Venue, $this> */
    public function venue(): BelongsTo
    {
        return $this->belongsTo(Venue::class);
    }

    /** @return BelongsTo<User, $this> */
    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'account_id');
    }

    /** @return BelongsTo<User, $this> */
    public function decider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by_account_id');
    }
}
