<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Oportunidad;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OportunidadManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_panel_opportunities_are_read_only_and_supervisor_sees_only_their_team(): void
    {
        $admin = User::factory()->create(['rol' => 'administrador', 'activo' => true]);
        $supervisor = User::factory()->create(['rol' => 'supervisor', 'activo' => true]);
        $otherSupervisor = User::factory()->create(['rol' => 'supervisor', 'activo' => true]);
        $client = Cliente::create(['nombre' => 'Cliente de prueba', 'telefono' => '55551230']);

        foreach ([[$supervisor, 'Venta propia'], [$otherSupervisor, 'Venta ajena']] as [$manager, $title]) {
            $seller = User::factory()->create(['rol' => 'vendedor', 'supervisor_id' => $manager->id]);
            Oportunidad::create([
                'cliente_id' => $client->id,
                'vendedor_id' => $seller->id,
                'titulo' => $title,
                'etapa' => 'prospecto',
            ]);
        }

        $this->actingAs($supervisor)->get('/oportunidades')
            ->assertOk()->assertSee('Venta propia')->assertDontSee('Venta ajena');

        $this->actingAs($admin)->get('/oportunidades')
            ->assertOk()->assertSee('Venta propia')->assertSee('Venta ajena');
        $this->get('/oportunidades/crear')->assertNotFound();
        $this->post('/oportunidades')->assertStatus(405);
        $this->patch('/oportunidades/1')->assertNotFound();
    }
}
