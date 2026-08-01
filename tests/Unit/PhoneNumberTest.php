<?php

namespace Tests\Unit;

use App\Support\PhoneNumber;
use PHPUnit\Framework\TestCase;

class PhoneNumberTest extends TestCase
{
    public function test_local_reduces_any_togolese_form_to_8_digits(): void
    {
        $this->assertSame('90112233', PhoneNumber::local('+228 90 11 22 33'));
        $this->assertSame('90112233', PhoneNumber::local('0022890112233'));
        $this->assertSame('90112233', PhoneNumber::local('22890112233'));
        $this->assertSame('90112233', PhoneNumber::local('90 11 22 33'));
        $this->assertSame('90112233', PhoneNumber::local('90-11-22-33'));
    }

    public function test_international_prefixes_the_country_code_once(): void
    {
        $this->assertSame('22890112233', PhoneNumber::international('90112233'));
        $this->assertSame('22890112233', PhoneNumber::international('+228 90 11 22 33'));
        $this->assertSame('22890112233', PhoneNumber::international('0022890112233'));
    }

    public function test_an_empty_input_stays_empty(): void
    {
        $this->assertSame('', PhoneNumber::local(null));
        $this->assertSame('', PhoneNumber::international(''));
    }
}
