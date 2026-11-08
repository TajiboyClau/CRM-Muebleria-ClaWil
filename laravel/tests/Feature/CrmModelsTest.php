<?php

namespace Tests\Feature;

use App\Models\Actividad;
use App\Models\Cliente;
use App\Models\Oportunidad;
use App\Models\Producto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CrmModelsTest extends TestCase
{
    use RefreshDatabase;

    public function test_crm_models_keep_their_relationships(): void
    {
        $supervisor = User::factory()->create([
            'rol' => 'supervisor',
        ]);

        $vendedor = User::factory()->create([
            'rol' => 'vendedor',
            'supervisor_id' => $supervisor->id,
        ]);

        $cliente = Cliente::create([
            'vendedor_id' => $vendedor->id,
            'nombre' => 'Muebles El Roble',
            'nit' => '12345678',
            'telefono' => '55551234',
            'correo' => 'compras@elroble.test',
        ]);

        $producto = Producto::create([
            'nombre' => 'Sala modular de tres piezas',
            'precio' => 4250.00,
            'activo' => true,
        ]);

        $oportunidad = Oportunidad::create([
            'cliente_id' => $cliente->id,
            'vendedor_id' => $vendedor->id,
            'titulo' => 'Sala para vivienda nueva',
            'etapa' => 'propuesta',
        ]);

        $oportunidad->productos()->attach($producto->id, [
            'cantidad' => 1,
            'precio_unitario' => 4250.00,
        ]);

        Actividad::create([
            'cliente_id' => $cliente->id,
            'vendedor_id' => $vendedor->id,
            'tipo' => 'llamada',
            'descripcion' => 'Confirmar medidas del espacio.',
            'completada' => false,
        ]);

        $this->assertTrue($vendedor->fresh()->clientes->contains($cliente));
        $this->assertTrue($cliente->fresh()->oportunidades->contains($oportunidad));
        $this->assertSame('Sala modular de tres piezas', $oportunidad->fresh()->productos->first()->nombre);
        $this->assertSame(1, $oportunidad->fresh()->productos->first()->pivot->cantidad);
        $this->assertCount(1, $cliente->fresh()->actividades);
    }
}
