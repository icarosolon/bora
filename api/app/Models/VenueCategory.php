<?php

namespace App\Models;

use Database\Factories\VenueCategoryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Categoria de local (RN-LOCAL-002). A lista é dado, não código: acrescentar
 * categoria é inserir linha. Sem timestamps (data-model.md).
 */
class VenueCategory extends Model
{
    /** @use HasFactory<VenueCategoryFactory> */
    use HasFactory;

    protected $table = 'venue_categories';

    public $timestamps = false;

    /**
     * Mass assignment explícito (Princípio V); $guarded = [] é proibido.
     * `slug` entra no $fillable porque o seeder o grava; a categoria é dado de
     * plataforma e nenhuma requisição de usuário a preenche.
     */
    protected $fillable = ['slug', 'name', 'active', 'position'];

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'position' => 'integer',
        ];
    }

    /** @return BelongsToMany<Venue, $this> */
    public function venues(): BelongsToMany
    {
        return $this->belongsToMany(Venue::class, 'venue_venue_category');
    }
}
