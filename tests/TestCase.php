<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Feature tests render Inertia pages through Blade; they must not require a built Vite manifest.
        $this->withoutVite();
    }
}
