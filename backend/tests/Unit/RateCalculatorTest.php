<?php

namespace Tests\Unit;

use App\Services\RateCalculator;
use PHPUnit\Framework\TestCase;

class RateCalculatorTest extends TestCase
{
    public function test_same_city_has_no_extra_charge(): void
    {
        $calculator = new RateCalculator(baseFee: 200, perKg: 150, outOfCityMultiplier: 1.5);

        $quote = $calculator->quote(2, 'Lahore', ' lahore ');

        $this->assertTrue($quote['same_city']);
        $this->assertSame(500.0, $quote['total']);
    }

    public function test_out_of_city_is_multiplied(): void
    {
        $calculator = new RateCalculator(baseFee: 200, perKg: 150, outOfCityMultiplier: 1.5);

        $this->assertSame(750.0, $calculator->quote(2, 'Lahore', 'Karachi')['total']);
    }
}
