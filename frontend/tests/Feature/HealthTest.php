<?php

declare(strict_types=1);

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class HealthTest extends TestCase
{
    #[Test]
    public function health_endpoint_returns_200(): void
    {
        $response = $this->get('/health');

        $response->assertStatus(200);
        $response->assertSee('OK');
    }
}
