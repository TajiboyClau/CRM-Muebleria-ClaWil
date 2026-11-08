<?php

namespace Tests\Feature;

use App\Models\Actividad;
use App\Models\Cliente;
use App\Models\Oportunidad;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OportunidadManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_administrator_can_create_and_update_an_opportunity(): void
    {
        $admin = User::factory()->create(['rol' => 'administrador', 'activo' => true, 'email_verified_at' => now()]);
        $seller = User::factory()->create(['rol' => 'vendedor', 'activo' => true]);
        $client = Cliente::create(['nombre' => 'Cliente de prueba', 'telefono' => '55551230']);

        $this->actingAs($admin)->post(route('oportunidades.store'), [
            'cliente_id' => $client->id,
            'vendedor_id' => $seller->id,
            'titulo' => 'Sala modular para apartamento',
            'etapa' => 'prospecto',
        ])->assertRedirect(route('oportunidades.index'));

        $opportunity = Oportunidad::firstOrFail();
        $this->actingAs($admin)->patch(route('oportunidades.update', $opportunity), [
            'cliente_id' => $client->id,
            'vendedor_id' => $seller->id,
            'titulo' => 'Sala modular para apartamento',
            'etapa' => 'propuesta',
        ])->assertRedirect(route('oportunidades.index'));

        $this->assertDatabaseHas('historial_etapas', ['oportunidad_id' => $opportunity->id, 'etapa_nueva' => 'propuesta']);
    }

    public function test_custom_rule_blocks_a_measurement_opportunity_without_completed_measurement(): void
    {
        $admin = User::factory()->create(['rol' => 'administrador', 'activo' => true, 'email_verified_at' => now()]);
        $seller = User::factory()->create(['rol' => 'vendedor', 'activo' => true]);
        $client = Cliente::create(['nombre' => 'Cliente a medida', 'telefono' => '55551231']);

        $this->actingAs($admin)->from(route('oportunidades.create'))->post(route('oportunidades.store'), [
            'cliente_id' => $client->id,
            'vendedor_id' => $seller->id,
            'titulo' => 'Cocina a medida',
            'etapa' => 'ganada',
            'fecha_cierre' => now()->format('Y-m-d H:i:s'),
        ])->assertSessionHasErrors('etapa');

        Actividad::create([
            'cliente_id' => $client->id,
            'vendedor_id' => $seller->id,
            'tipo' => 'reunion',
            'descripcion' => 'Medición final del espacio',
            'completada' => true,
        ]);

        $this->actingAs($admin)->post(route('oportunidades.store'), [
            'cliente_id' => $client->id,
            'vendedor_id' => $seller->id,
            'titulo' => 'Cocina a medida',
            'etapa' => 'ganada',
            'fecha_cierre' => now()->format('Y-m-d H:i:s'),
        ])->assertRedirect(route('oportunidades.index'));
    }

    public function test_supervisor_can_view_but_not_modify_opportunities(): void
    {
        $supervisor = User::factory()->create(['rol' => 'supervisor', 'activo' => true, 'email_verified_at' => now()]);

        $this->actingAs($supervisor)->get(route('oportunidades.index'))->assertOk()->assertSee('Oportunidades de venta');
        $this->actingAs($supervisor)->get(route('oportunidades.create'))->assertForbidden();
    }
}
