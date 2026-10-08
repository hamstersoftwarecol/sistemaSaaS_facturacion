<?php

namespace Tests\Feature;

use Database\Seeders\PlatformSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_landing_and_pricing_pages_show_plans(): void
    {
        $this->seed(PlatformSeeder::class);

        $this->get('/')->assertOk()->assertSee('Profesional')->assertSee('5 estilos de factura');
        $this->get('/precios')->assertOk()->assertSee('Empresarial');
    }
}
