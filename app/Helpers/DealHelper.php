<?php

namespace App\Helpers;

use Illuminate\Support\Collection;

class DealHelper
{
    public static function transformTrailerTypes(Collection $deals): void
    {
        $deals->each(function ($deal) {
            $deal->trailer_types = $deal->trailerTypes->pluck('type')->values();
            unset($deal->trailerTypes);
        });
    }

    public static function transformSingleDeal($deal): void
    {
        $deal->trailer_types = $deal->trailerTypes->pluck('type')->values();
        unset($deal->trailerTypes);
    }
}