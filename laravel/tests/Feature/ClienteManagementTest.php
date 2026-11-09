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
                'nit' => '00452 1817-k',
                'telefono' => '+502 5555-1234',
                'correo' => 'compras@elroble.test',
            ])
            ->assertRedirect(route('clientes.index'));

        $cliente = Cliente::where('nombre', 'Muebles El Roble')->firstOrFail();

        $this->actingAs($admin)
            ->patch(route('clientes.update', $cliente), [
                'nombre' => 'Muebles El Roble Premium',
                'nit' => '00452 1817-k',
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

    public function test_supervisor_can_create_and_edit_a_client_in_their_team(): void
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

        $this->actingAs($supervisor)
            ->post(route('clientes.store'), [
                'nombre' => 'Muebles del Valle',
                'telefono' => '55551237',
                'vendedor_id' => $seller->id,
            ])
            ->assertRedirect(route('clientes.index'));

        $cliente = Cliente::where('nombre', 'Muebles del Valle')->firstOrFail();

        $this->actingAs($supervisor)
            ->patch(route('clientes.update', $cliente), [
                'nombre' => 'Muebles del Valle Premium',
                'telefono' => '55551238',
                'vendedor_id' => $seller->id,
            ])
            ->assertRedirect(route('clientes.index'));

        $this->assertDatabaseHas('clientes', [
            'id' => $cliente->id,
            'nombre' => 'Muebles del Valle Premium',
            'vendedor_id' => $seller->id,
        ]);
    }

    public function test_administrator_can_delete_only_a_client_without_opportunities(): void
    {
        $admin = User::factory()->create(['rol' => 'administrador', 'activo' => true]);
        $client = Cliente::create(['nombre' => 'Sin oportunidades', 'telefono' => '55551239']);

        $this->actingAs($admin)
            ->delete(route('clientes.destroy', $client))
            ->assertRedirect(route('clientes.index'));

        $this->assertDatabaseMissing('clientes', ['id' => $client->id]);
    }

    public function test_panel_rejects_an_eleventh_client_but_allows_editing_an_existing_client(): void
    {
        $admin = User::factory()->create(['rol' => 'administrador', 'activo' => true]);
        $seller = User::factory()->create(['rol' => 'vendedor', 'activo' => true]);

        for ($i = 0; $i < 10; $i++) {
            Cliente::create([
                'nombre' => "Cliente $i",
                'telefono' => (string) (55551000 + $i),
                'vendedor_id' => $seller->id,
            ]);
        }

        $available = Cliente::create(['nombre' => 'Disponible', 'telefono' => '55552000']);
        $assigned = Cliente::where('vendedor_id', $seller->id)->firstOrFail();

        $this->actingAs($admin)
            ->post(route('clientes.store'), [
                'nombre' => 'Cliente adicional',
                'telefono' => '55552001',
                'vendedor_id' => $seller->id,
            ])->assertSessionHasErrors('vendedor_id');

        $this->patch(route('clientes.assign', $available), ['vendedor_id' => $seller->id])
            ->assertSessionHasErrors('vendedor_id');

        $this->patch(route('clientes.update', $available), [
            'nombre' => $available->nombre,
            'telefono' => $available->telefono,
            'vendedor_id' => $seller->id,
        ])->assertSessionHasErrors('vendedor_id');

        $this->patch(route('clientes.update', $assigned), [
            'nombre' => 'Nombre actualizado',
            'telefono' => $assigned->telefono,
            'vendedor_id' => $seller->id,
        ])->assertRedirect(route('clientes.index'));

        $this->assertNull($available->refresh()->vendedor_id);
        $this->assertSame('Nombre actualizado', $assigned->refresh()->nombre);
        $this->assertSame(10, Cliente::where('vendedor_id', $seller->id)->count());
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
