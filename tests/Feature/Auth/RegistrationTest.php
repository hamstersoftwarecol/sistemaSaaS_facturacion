<?php

namespace Tests\Feature\Auth;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Role;
use App\Models\Subscription;
use App\Models\User;
use Database\Seeders\PlatformSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $this->seed(PlatformSeeder::class);

        $this->get('/register')->assertOk()->assertSee('Nombre de la empresa');
    }

    public function test_registration_provisions_a_complete_company(): void
    {
        $this->seed(PlatformSeeder::class);

        $response = $this->post('/register', [
            'company_name' => 'Mi Empresa de Prueba',
            'name' => 'Test User',
            'email' => 'test@example.com',
            'plan' => 'profesional',
            'currency' => 'COP',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));

        $user = User::where('email', 'test@example.com')->first();
        $company = Company::find($user->company_id);

        $this->assertTrue($user->is_owner);
        $this->assertSame('COP', $company->currency);
        $this->assertSame(1, Branch::withoutGlobalScopes()->where('company_id', $company->id)->where('is_main', true)->count());
        $this->assertSame(4, Role::withoutGlobalScopes()->where('company_id', $company->id)->count());

        $subscription = Subscription::withoutGlobalScopes()->where('company_id', $company->id)->first();
        $this->assertSame('trialing', $subscription->status);
        $this->assertTrue($subscription->isValid());

        $this->get('/dashboard')->assertOk();
    }
}
