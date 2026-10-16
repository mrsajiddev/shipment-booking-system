<?php

namespace App\Services;

/**
 * Works out the delivery price.
 *
 * Its constructor needs plain numbers (float), and the container cannot guess
 * plain numbers. That is why ShipmentServiceProvider registers it with a closure.
 */
class RateCalculator
{
    public function __construct(
        private float $baseFee,
        private float $perKg,
        private float $outOfCityMultiplier,
    ) {
    }

    /** @return array{base_fee: float, weight_charge: float, same_city: bool, total: float} */
    public function quote(float $weightKg, string $fromCity, string $toCity): array
    {
        $sameCity = strcasecmp(trim($fromCity), trim($toCity)) === 0;
        $weightCharge = round($weightKg * $this->perKg, 2);
        $subtotal = $this->baseFee + $weightCharge;
        $total = $sameCity ? $subtotal : $subtotal * $this->outOfCityMultiplier;

        return [
            'base_fee' => $this->baseFee,
            'weight_charge' => $weightCharge,
            'same_city' => $sameCity,
            'total' => round($total, 2),
        ];
    }
}
