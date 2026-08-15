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

    public function test_e164_passes_through_the_legacy_togo_form_unchanged(): void
    {
        // Forme historique déjà stockée partout avant le support multi-pays :
        // ne doit jamais être altérée par le nouveau parsing multi-région.
        $this->assertSame('22890112233', PhoneNumber::e164('22890112233'));
    }

    public function test_e164_normalizes_a_bare_togo_number_using_the_default_region(): void
    {
        $this->assertSame('22890112233', PhoneNumber::e164('90112233'));
        $this->assertSame('22890112233', PhoneNumber::e164('90 11 22 33'));
    }

    public function test_e164_parses_an_explicit_foreign_number(): void
    {
        $this->assertSame('33612345678', PhoneNumber::e164('+33612345678'));
        $this->assertSame('33612345678', PhoneNumber::e164('0033612345678'));
        $this->assertSame('33612345678', PhoneNumber::e164('+33 6 12 34 56 78'));
    }

    public function test_e164_rejects_an_invalid_number(): void
    {
        $this->assertSame('', PhoneNumber::e164('not-a-phone'));
        $this->assertSame('', PhoneNumber::e164('123'));
        $this->assertSame('', PhoneNumber::e164(null));
    }
}
