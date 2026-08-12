<?php

namespace Tests;

use App\Models\Setting;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * PHPUnit runs every test method in one process, so Setting's per-request
     * static memo would otherwise leak values across tests. Reset it each time.
     */
    protected function setUp(): void
    {
        parent::setUp();

        Setting::flushCache();
    }
}
