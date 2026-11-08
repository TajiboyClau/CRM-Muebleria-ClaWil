<?php

namespace Tests\Feature;

use App\Models\Actividad;
use App\Models\Cliente;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActividadManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_administrator_can_create_and_complete_an_activity(): void
    {
        $admin = User::factory()->create(['rol' => 'administrador', 'activo' => true, 'email_verified_at' => now()]);
        $seller = User::factory()->create(['rol' => 'vendedor', 'activo' => true]);
        $client = Cliente::create(['nombre' => 'Cliente de actividad', 'telefono' => '55551232']);

        $this->actingAs($admin)->post(route('actividades.store'), [
            'cliente_id' => $client->id,
            'vendedor_id' => $seller->id,
            'tipo' => 'reunion',
            'descripcion' => 'Revisar medidas del comedor',
        ])->assertRedirect(route('actividades.index'));

        $activity = Actividad::firstOrFail();
        $this->actingAs($admin)->patch(route('actividades.complete', $activity), ['completada' => 1])->assertRedirect(route('actividades.index'));
        $this->assertDatabaseHas('actividades', ['id' => $activity->id, 'completada' => true]);
    }

    public function test_supervisor_can_complete_activity_of_their_team(): void
    {
        $supervisor = User::factory()->create(['rol' => 'supervisor', 'activo' => true, 'email_verified_at' => now()]);
        $seller = User::factory()->create(['rol' => 'vendedor', 'activo' => true, 'supervisor_id' => $supervisor->id]);
        $client = Cliente::create(['nombre' => 'Cliente equipo', 'telefono' => '55551233']);
        $activity = Actividad::create(['cliente_id' => $client->id, 'vendedor_id' => $seller->id, 'tipo' => 'llamada', 'descripcion' => 'Confirmar pedido']);

        $this->actingAs($supervisor)->patch(route('actividades.complete', $activity), ['completada' => 1])->assertRedirect(route('actividades.index'));
        $this->assertDatabaseHas('actividades', ['id' => $activity->id, 'completada' => true]);
    }
}
