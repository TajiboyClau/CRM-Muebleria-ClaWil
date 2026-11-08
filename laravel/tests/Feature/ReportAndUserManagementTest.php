<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportAndUserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_panel_roles_can_view_reports(): void
    {
        foreach (['administrador', 'supervisor'] as $role) {
            $user = User::factory()->create(['rol' => $role, 'activo' => true, 'email_verified_at' => now()]);
            $this->actingAs($user)->get(route('reportes.index'))->assertOk()->assertSee('Reportes del CRM');
        }
    }

    public function test_only_administrator_can_create_users(): void
    {
        $admin = User::factory()->create(['rol' => 'administrador', 'activo' => true, 'email_verified_at' => now()]);
        $this->actingAs($admin)->post(route('usuarios.store'), [
            'name' => 'Vendedor nuevo',
            'email' => 'nuevo@crm.test',
            'password' => 'Ventas2026',
            'password_confirmation' => 'Ventas2026',
            'rol' => 'vendedor',
        ])->assertRedirect(route('usuarios.index'));
        $this->assertDatabaseHas('users', ['email' => 'nuevo@crm.test', 'rol' => 'vendedor']);

        $supervisor = User::factory()->create(['rol' => 'supervisor', 'activo' => true, 'email_verified_at' => now()]);
        $this->actingAs($supervisor)->get(route('usuarios.index'))->assertForbidden();
    }
}
