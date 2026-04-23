<?php

namespace Tests\Feature\Payment;

use App\Enums\PaymentMethod;
use Tests\TestCase;

class PaymentMethodTest extends TestCase
{
    public function test_mayar_enum_value_is_mayar_string(): void
    {
        $this->assertSame('mayar', PaymentMethod::Mayar->value);
    }

    public function test_mayar_label_is_mayar(): void
    {
        $this->assertSame('Mayar', PaymentMethod::Mayar->label());
    }

    public function test_midtrans_still_exists_for_historical_data(): void
    {
        $this->assertSame('midtrans', PaymentMethod::Midtrans->value);
    }
}
