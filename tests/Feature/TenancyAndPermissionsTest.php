<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Subscription;
use App\Models\SubscriptionPayment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesCompanies;
use Tests\TestCase;

class TenancyAndPermissionsTest extends TestCase
{
    use CreatesCompanies, RefreshDatabase;

    public function test_companies_cannot_see_each_others_data(): void
    {
        $ownerA = $this->createCompanyOwner('a@test.com');
        $ownerB = $this->createCompanyOwner('b@test.com');

        $customerB = $this->asTenant($ownerB, fn () => Customer::create(['name' => 'Cliente secreto de B']));

        $this->actingAs($ownerA)->get(route('customers.index'))->assertOk()->assertDontSee('Cliente secreto de B');
        $this->actingAs($ownerA)->get(route('customers.show', $customerB))->assertNotFound();
        $this->actingAs($ownerA)->put(route('customers.update', $customerB), ['name' => 'Hack'])->assertNotFound();
        $this->actingAs($ownerB)->get(route('customers.show', $customerB))->assertOk();

        // Validación de claves foráneas por empresa.
        $this->actingAs($ownerA)->post(route('invoices.store'), [
            'customer_id' => $customerB->id, 'date' => today()->toDateString(),
            'items' => [['description' => 'X', 'quantity' => 1, 'unit_price' => 1]],
        ])->assertSessionHasErrors('customer_id');
    }

    public function test_role_permissions_are_enforced(): void
    {
        $owner = $this->createCompanyOwner();
        $seller = $this->createUserWithRole($owner, 'Vendedor', 'seller@test.com');

        $this->actingAs($seller)->get(route('invoices.index'))->assertOk();
        $this->actingAs($seller)->get(route('customers.create'))->assertOk();
        $this->actingAs($seller)->get(route('expenses.index'))->assertForbidden();
        $this->actingAs($seller)->get(route('reports.index'))->assertForbidden();
        $this->actingAs($seller)->get(route('users.index'))->assertForbidden();
        $this->actingAs($seller)->get(route('settings.company'))->assertForbidden();

        $accountant = $this->createUserWithRole($owner, 'Contador', 'accountant@test.com');
        $this->actingAs($accountant)->get(route('expenses.index'))->assertOk();
        $this->actingAs($accountant)->get(route('reports.show', 'perdidas-y-ganancias'))->assertOk();
        $this->actingAs($accountant)->get(route('products.create'))->assertForbidden();
    }

    public function test_only_the_owner_can_edit_the_owner_account(): void
    {
        $owner = $this->createCompanyOwner();
        $admin = $this->createUserWithRole($owner, 'Administrador', 'second-admin@test.com');

        $this->actingAs($admin)->get(route('users.edit', $owner))->assertForbidden();
        $this->actingAs($admin)->put(route('users.update', $owner), ['name' => 'X', 'email' => 'hijack@test.com', 'password' => 'new-password', 'password_confirmation' => 'new-password'])->assertForbidden();
        $this->actingAs($owner)->get(route('users.edit', $owner))->assertOk();
    }

    public function test_admin_panel_is_restricted_to_super_admins(): void
    {
        $owner = $this->createCompanyOwner();
        $this->actingAs($owner)->get(route('admin.dashboard'))->assertForbidden();

        $admin = User::where('is_super_admin', true)->first();
        $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk();
        $this->actingAs($admin)->get(route('dashboard'))->assertRedirect(route('admin.dashboard'));
    }

    public function test_expired_subscription_redirects_and_admin_approval_reactivates(): void
    {
        $owner = $this->createCompanyOwner('late@test.com', 'basico');
        $subscription = Subscription::withoutGlobalScopes()->where('company_id', $owner->company_id)->first();
        $subscription->update(['trial_ends_at' => now()->subDay()]);

        $this->actingAs($owner)->get(route('invoices.index'))->assertRedirect(route('subscription.index'));
        $this->actingAs($owner)->get(route('subscription.index'))->assertOk();

        $plan = $subscription->plan;
        $this->actingAs($owner)->post(route('subscription.pay', $plan), [
            'billing_cycle' => 'monthly', 'payment_method_id' => 1, 'reference' => 'REF-1',
        ])->assertRedirect(route('subscription.index'));

        $payment = SubscriptionPayment::withoutGlobalScopes()->where('company_id', $owner->company_id)->firstOrFail();
        $this->assertSame('pending', $payment->status);

        $admin = User::where('is_super_admin', true)->first();
        $this->actingAs($admin)->post(route('admin.payments.approve', $payment))->assertRedirect();

        $subscription->refresh();
        $this->assertSame('active', $subscription->status);
        $this->assertTrue($subscription->isValid());
        $this->actingAs($owner->fresh())->get(route('invoices.index'))->assertOk();
    }

    public function test_plan_limits_block_new_users(): void
    {
        $owner = $this->createCompanyOwner('free@test.com', 'gratis');

        $this->actingAs($owner)->post(route('users.store'), [
            'name' => 'Extra', 'email' => 'extra@test.com', 'role_id' => $owner->role_id,
            'password' => 'password', 'password_confirmation' => 'password', 'is_active' => 1,
        ])->assertSessionHas('error');

        $this->assertDatabaseMissing('users', ['email' => 'extra@test.com']);
    }

    public function test_suspended_company_users_are_logged_out(): void
    {
        $owner = $this->createCompanyOwner();
        $owner->company->update(['status' => 'suspended']);

        $this->actingAs($owner)->get(route('dashboard'))->assertRedirect(route('login'));
        $this->assertGuest();
    }
}
