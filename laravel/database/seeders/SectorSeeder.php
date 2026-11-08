<?php

namespace Database\Seeders;

use App\Models\Actividad;
use App\Models\Cliente;
use App\Models\Oportunidad;
use App\Models\Producto;
use App\Models\User;
use Illuminate\Database\Seeder;

class SectorSeeder extends Seeder
{
    public function run(): void
    {
        $supervisor = User::create([
            'name' => 'Marta Quiñónez',
            'email' => 'marta@muebleriaclawil.test',
            'password' => 'Ventas2026',
            'rol' => 'supervisor',
            'activo' => true,
        ]);

        $seller = User::create([
            'name' => 'Marco Cifuentes',
            'email' => 'marco@muebleriaclawil.test',
            'password' => 'Ventas2026',
            'rol' => 'vendedor',
            'supervisor_id' => $supervisor->id,
            'activo' => true,
        ]);

        $products = collect([
            ['Sofá modular ClaWil', 4850.00],
            ['Comedor de madera para seis personas', 6200.00],
            ['Ropero de tres puertas', 3800.00],
            ['Escritorio ejecutivo', 2950.00],
            ['Cama matrimonial con cabecera', 5400.00],
        ])->mapWithKeys(function (array $data) {
            [$name, $price] = $data;

            return [$name => Producto::create([
                'nombre' => $name,
                'precio' => $price,
                'activo' => true,
            ])];
        });

        $client = Cliente::create([
            'vendedor_id' => $seller->id,
            'nombre' => 'Familia López',
            'nit' => '77778888',
            'telefono' => '55559876',
            'correo' => 'familialopez@muebleriaclawil.test',
        ]);

        $customOpportunity = Oportunidad::create([
            'cliente_id' => $client->id,
            'vendedor_id' => $seller->id,
            'titulo' => 'Cocina a medida para familia López',
            'etapa' => 'propuesta',
        ]);
        $customOpportunity->productos()->attach($products['Comedor de madera para seis personas']->id, [
            'cantidad' => 1,
            'precio_unitario' => 6200.00,
        ]);

        Actividad::create([
            'cliente_id' => $client->id,
            'vendedor_id' => $seller->id,
            'tipo' => 'reunion',
            'descripcion' => 'Medición completada del espacio para el diseño a medida.',
            'vence_en' => '2026-11-10 16:00:00',
            'completada' => true,
        ]);

        $secondClient = Cliente::create([
            'vendedor_id' => $seller->id,
            'nombre' => 'Oficinas del Valle',
            'nit' => '99990000',
            'telefono' => '55559877',
            'correo' => 'compras@oficinasdelvalle.test',
        ]);

        $officeOpportunity = Oportunidad::create([
            'cliente_id' => $secondClient->id,
            'vendedor_id' => $seller->id,
            'titulo' => 'Mobiliario para sala de reuniones',
            'etapa' => 'calificado',
        ]);
        $officeOpportunity->productos()->attach($products['Escritorio ejecutivo']->id, [
            'cantidad' => 4,
            'precio_unitario' => 2950.00,
        ]);
    }
}
