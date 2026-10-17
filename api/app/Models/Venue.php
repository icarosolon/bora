<?php

namespace App\Models;

use Database\Factories\VenueFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * O estabelecimento. Um local nunca é excluído, só inativado (Princípio X).
 *
 * As invariantes (perfil não reivindicado não aceita campos ricos; slug
 * imutável; ao menos uma categoria) vivem no domínio, não neste model.
 * O estado "reivindicado" é derivado de existir claim `approved` (R6).
 */
class Venue extends Model
{
    /** @use HasFactory<VenueFactory> */
    use HasFactory;

    protected $table = 'venues';

    /** Mass assignment explícito (Princípio V); $guarded = [] é proibido. */
    protected $fillable = [
        'name',
        'phone',
        'postal_code',
        'street',
        'number',
        'complement',
        'district',
        'city',
        'state',
        'latitude',
        'longitude',
        'description',
        'instagram',
        'logo_path',
    ];

    /*
     * Fora do $fillable de propósito: `slug` (imutável), `name_normalized`
     * (derivado), `active` e `created_by_account_id` são definidos só pelo
     * caso de uso, nunca por entrada da requisição.
     */
    protected $hidden = ['created_by_account_id', 'name_normalized'];

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'active' => 'boolean',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_account_id');
    }

    /** @return BelongsToMany<VenueCategory, $this> */
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(VenueCategory::class, 'venue_venue_category');
    }

    /** @return BelongsToMany<User, $this> */
    public function managers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'venue_managers', 'venue_id', 'account_id')
            ->withPivot(['granted_at', 'granted_by_claim_id', 'revoked_at'])
            ->wherePivotNull('revoked_at');
    }

    /** @return HasMany<VenueClaim, $this> */
    public function claims(): HasMany
    {
        return $this->hasMany(VenueClaim::class);
    }
}
