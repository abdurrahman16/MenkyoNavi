<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_from_dashboard(): void
    {
        $this->get('/dashboard')->assertRedirect(route('login'));
    }

    public function test_verified_users_can_view_dashboard(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $this->actingAs($user)
            ->withSession(['locale' => 'en'])
            ->get('/dashboard')
            ->assertSuccessful()
            ->assertSee(__('panel.welcome', [], 'en'), false)
            ->assertSee(__('panel.mistake_vault', [], 'en'), false)
            ->assertSee(__('panel.start_practice', [], 'en'), false)
            ->assertSee(__('panel.no_progress_title', [], 'en'), false)
            ->assertSee(__('panel.study_methods', [], 'en'), false);
    }

    public function test_mistake_vault_shows_empty_state(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $this->actingAs($user)
            ->withSession(['locale' => 'en'])
            ->get('/mistakes')
            ->assertSuccessful()
            ->assertSee('No saved mistakes yet', false);
    }
}
