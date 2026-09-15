<?php

namespace App\Services;

use App\Enums\PublicationPriority;
use App\Models\PublicationPackage;

/**
 * Publication-rights cost (client edit 17): the package's display-space price
 * plus the priority surcharge when the auction is published with priority.
 * All amounts are integer centimes; the same figures drive the live preview on
 * the admin form (data attributes) and the value stored on the auction.
 */
class PublicationFeeCalculator
{
    /**
     * @return array{package_price: int, priority_surcharge: int, total: int}
     */
    public function quote(?PublicationPackage $package, PublicationPriority $priority): array
    {
        if (! $package) {
            return ['package_price' => 0, 'priority_surcharge' => 0, 'total' => 0];
        }

        $base = (int) $package->price;
        $surcharge = $priority === PublicationPriority::PRIORITY ? (int) $package->priority_price : 0;

        return [
            'package_price' => $base,
            'priority_surcharge' => $surcharge,
            'total' => $base + $surcharge,
        ];
    }

    public function total(?PublicationPackage $package, PublicationPriority $priority): int
    {
        return $this->quote($package, $priority)['total'];
    }
}
