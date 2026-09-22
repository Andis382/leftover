<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Requests look like they come from the SPA, so Sanctum gives them a session.
        $this->withHeader('Referer', 'http://localhost');
    }
}
