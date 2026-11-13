<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Http;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // With the Vite dev server running or an SSR bundle built, Inertia sends each page through the Http client to the SSR server.
        Http::preventStrayRequests();
    }
}
