<?php

namespace Tests\Feature;

use App\Models\Producto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductoManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_administrator_can_create_update_and_deactivate_a_product(): void
    {
        $admin = User::factory()->create([
            'rol' => 'administrador',
            'activo' => true,
            'email_verified_at' => now(),
        ]);

        $this->actingAs($admin)
            ->post(route('productos.store'), [
                'nombre' => 'Mesa auxiliar de roble',
                'precio' => '850.00',
                'activo' => '1',
            ])
            ->assertRedirect(route('productos.index'));

        $producto = Producto::where('nombre', 'Mesa auxiliar de roble')->firstOrFail();

        $this->actingAs($admin)
            ->patch(route('productos.update', $producto), [
                'nombre' => 'Mesa auxiliar de roble premium',
                'precio' => '950.00',
                'activo' => '1',
            ])
            ->assertRedirect(route('productos.index'));

        $this->actingAs($admin)
            ->patch(route('productos.deactivate', $producto->fresh()))
            ->assertRedirect(route('productos.index'));

        $this->assertDatabaseHas('productos', [
            'nombre' => 'Mesa auxiliar de roble premium',
            'activo' => false,
        ]);
    }

    public function test_supervisor_can_view_but_not_modify_the_catalog(): void
    {
        $supervisor = User::factory()->create([
            'rol' => 'supervisor',
            'activo' => true,
            'email_verified_at' => now(),
        ]);

        $this->actingAs($supervisor)
            ->get(route('productos.index'))
            ->assertOk()
            ->assertSee('Catálogo de muebles');

        $this->actingAs($supervisor)
            ->post(route('productos.store'), [
                'nombre' => 'Producto no permitido',
                'precio' => '100.00',
            ])
            ->assertForbidden();
    }

    public function test_product_price_must_be_positive(): void
    {
        $admin = User::factory()->create([
            'rol' => 'administrador',
            'activo' => true,
            'email_verified_at' => now(),
        ]);

        $this->actingAs($admin)
            ->from(route('productos.create'))
            ->post(route('productos.store'), [
                'nombre' => 'Producto inválido',
                'precio' => '0',
            ])
            ->assertSessionHasErrors('precio');
    }
}
