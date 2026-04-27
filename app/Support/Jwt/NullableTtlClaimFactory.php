<?php

namespace App\Support\Jwt;

use Tymon\JWTAuth\Claims\Factory as BaseClaimFactory;

class NullableTtlClaimFactory extends BaseClaimFactory
{
    /**
     * Preserve null so jwt-auth can omit the exp claim for non-expiring tokens.
     */
    public function setTTL($ttl)
    {
        $this->ttl = $ttl === null ? null : (int) $ttl;

        return $this;
    }
}
