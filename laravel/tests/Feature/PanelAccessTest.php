<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PanelAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_administrator_can_access_the_panel(): void
    {
        $user = User::factory()->create([
            'rol' => 'administrador',
            'activo' => true,
            'email_verified_at' => now(),
        ]);

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('Panel del CRM');
    }

    public function test_supervisor_can_access_the_panel(): void
    {
        $user = User::factory()->create([
            'rol' => 'supervisor',
            'activo' => true,
            'email_verified_at' => now(),
        ]);

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertOk();
    }

    public function test_vendor_cannot_access_the_panel(): void
    {
        $user = User::factory()->create([
            'rol' => 'vendedor',
            'activo' => true,
            'email_verified_at' => now(),
        ]);

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertForbidden();
    }

    public function test_inactive_panel_user_cannot_access_the_panel(): void
    {
        $user = User::factory()->create([
            'rol' => 'administrador',
            'activo' => false,
            'email_verified_at' => now(),
        ]);

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertForbidden();
    }
}
