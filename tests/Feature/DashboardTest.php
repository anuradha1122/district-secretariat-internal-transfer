<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get(route('dashboard'))
            ->assertRedirect(route('login'));
    }

    public function test_an_authenticated_user_can_view_the_dashboard(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('dashboard')
                ->where('dashboardData.name', $user->name)
                ->where('dashboardData.cards.0.key', 'notifications')
                ->where('dashboardData.cards.0.value', 0)
                ->where('dashboardData.latestApplication', null)
            );
    }

    public function test_an_admin_can_see_management_cards(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $user = User::factory()->create();
        $user->assignRole('Admin');

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('dashboard')
                ->where('dashboardData.roles.0', 'Admin')
                ->has('dashboardData.cards', 4)
                ->where('dashboardData.cards.1.key', 'eligible-employees')
                ->where('dashboardData.cards.2.key', 'offices')
                ->where('dashboardData.cards.3.key', 'services')
            );
    }
}