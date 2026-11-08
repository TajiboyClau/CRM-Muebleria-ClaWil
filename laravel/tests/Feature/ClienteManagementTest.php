<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClienteManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_administrator_can_create_and_update_a_client(): void
    {
        $admin = User::factory()->create([
            'rol' => 'administrador',
            'activo' => true,
            'email_verified_at' => now(),
        ]);

        $this->actingAs($admin)
            ->post(route('clientes.store'), [
                'nombre' => 'Muebles El Roble',
                'nit' => '12345678',
                'telefono' => '55551234',
                'correo' => 'compras@elroble.test',
            ])
            ->assertRedirect(route('clientes.index'));

        $cliente = Cliente::where('nombre', 'Muebles El Roble')->firstOrFail();

        $this->actingAs($admin)
            ->patch(route('clientes.update', $cliente), [
                'nombre' => 'Muebles El Roble Premium',
                'nit' => '12345678',
                'telefono' => '55551235',
                'correo' => 'ventas@elroble.test',
            ])
            ->assertRedirect(route('clientes.index'));

        $this->assertDatabaseHas('clientes', [
            'nombre' => 'Muebles El Roble Premium',
            'telefono' => '55551235',
        ]);
    }

    public function test_supervisor_can_view_and_assign_a_client_to_their_seller(): void
    {
        $supervisor = User::factory()->create([
            'rol' => 'supervisor',
            'activo' => true,
            'email_verified_at' => now(),
        ]);
        $seller = User::factory()->create([
            'rol' => 'vendedor',
            'activo' => true,
            'supervisor_id' => $supervisor->id,
        ]);
        $cliente = Cliente::create([
            'nombre' => 'Cliente disponible',
            'telefono' => '55551236',
        ]);

        $this->actingAs($supervisor)
            ->get(route('clientes.index'))
            ->assertOk()
            ->assertSee('Cliente disponible');

        $this->actingAs($supervisor)
            ->patch(route('clientes.assign', $cliente), ['vendedor_id' => $seller->id])
            ->assertRedirect(route('clientes.index'));

        $this->assertDatabaseHas('clientes', [
            'id' => $cliente->id,
            'vendedor_id' => $seller->id,
        ]);
    }

    public function test_vendor_cannot_access_the_panel_client_list(): void
    {
        $seller = User::factory()->create([
            'rol' => 'vendedor',
            'activo' => true,
            'email_verified_at' => now(),
        ]);

        $this->actingAs($seller)
            ->get(route('clientes.index'))
            ->assertForbidden();
    }
}
