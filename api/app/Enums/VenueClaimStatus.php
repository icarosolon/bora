<?php

namespace App\Enums;

/**
 * Estados do pedido de reivindicação (data-model.md, `venue_claims.status`).
 */
enum VenueClaimStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';
}
