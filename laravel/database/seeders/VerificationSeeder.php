<?php

namespace Database\Seeders;

use App\Models\Actividad;
use App\Models\Cliente;
use App\Models\HistorialEtapa;
use App\Models\Oportunidad;
use App\Models\Producto;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class VerificationSeeder extends Seeder
{
    public function run(): void
    {
        $password = 'Ventas2026';

        $admin = User::create([
            'name' => 'Alicia Morales',
            'email' => 'admin@crm.test',
            'password' => $password,
            'rol' => 'administrador',
            'activo' => true,
        ]);

        $sergio = User::create([
            'name' => 'Sergio Ruiz',
            'email' => 'sergio@crm.test',
            'password' => $password,
            'rol' => 'supervisor',
            'activo' => true,
        ]);

        $sandra = User::create([
            'name' => 'Sandra Pérez',
            'email' => 'sandra@crm.test',
            'password' => $password,
            'rol' => 'supervisor',
            'activo' => true,
        ]);

        $valeria = $this->seller('Valeria López', 'valeria@crm.test', $sergio->id, true, $password);
        $diego = $this->seller('Diego Castillo', 'diego@crm.test', $sergio->id, true, $password);
        $carlos = $this->seller('Carlos Ajú', 'carlos@crm.test', $sandra->id, true, $password);
        $ivan = $this->seller('Iván Toj', 'ivan@crm.test', $sandra->id, false, $password);

        $products = [];
        foreach ([
            ['Plan básico', 1200.00, true],
            ['Plan avanzado', 2750.50, true],
            ['Instalación', 450.00, true],
            ['Soporte anual', 3600.00, true],
            ['Licencia anterior', 999.99, false],
        ] as [$name, $price, $active]) {
            $products[$name] = Producto::create([
                'nombre' => $name,
                'precio' => $price,
                'activo' => $active,
            ]);
        }

        $clients = [];
        $clients['Comercial El Quetzal'] = $this->client($valeria, 'Comercial El Quetzal', '4521817K', '55551234', 'compras@elquetzal.test');
        $clients['Ferretería Xela'] = $this->client($valeria, 'Ferretería Xela', '98765434', '77612345');
        $clients['María Coyoy'] = $this->client($valeria, 'María Coyoy', null, '41234567');
        $clients['Farmacia Los Altos'] = $this->client($diego, 'Farmacia Los Altos', '34567895', '77634567', 'gerencia@losaltos.test');
        $clients['Luis Tzul'] = $this->client($diego, 'Luis Tzul', null, '52345678');
        $clients['Abarrotes San Juan'] = $this->client($carlos, 'Abarrotes San Juan', '12345679', '77651001');
        $clients['Panadería La Esperanza'] = $this->client($carlos, 'Panadería La Esperanza', null, '30001002');
        $clients['Taller El Volcán'] = $this->client($carlos, 'Taller El Volcán', '22233342', '77651003');
        $clients['Ana Xicará'] = $this->client($carlos, 'Ana Xicará', null, '30001004');
        $clients['Librería Cantel'] = $this->client($carlos, 'Librería Cantel', '55566677', '77651005');
        $clients['Hotel Los Pinos'] = $this->client($ivan, 'Hotel Los Pinos', '66655544', '77661001');
        $clients['Agencia Cuatro Caminos'] = $this->client(null, 'Agencia Cuatro Caminos', null, '30002001');
        $clients['Café Zunil'] = $this->client(null, 'Café Zunil', null, '30002002');
        $clients['Clínica San Rafael'] = $this->client(null, 'Clínica San Rafael', '8889990K', '77672003');
        $clients['Constructora Altiplano'] = $this->client(null, 'Constructora Altiplano', '44433328', '77672004');
        $clients['Escuela Futuro'] = $this->client(null, 'Escuela Futuro', null, '30002005');
        $clients['Gimnasio Fuerza'] = $this->client(null, 'Gimnasio Fuerza', null, '30002006');

        $opportunities = [];
        $opportunities[] = $this->opportunity(
            $clients['Comercial El Quetzal'],
            $valeria,
            'Renovación de planes',
            'ganada',
            null,
            '2026-10-05 16:00:00',
            [[$products['Plan avanzado'], 2, 2600.00], [$products['Instalación'], 1, 450.00]],
        );
        $opportunities[] = $this->opportunity(
            $clients['Ferretería Xela'],
            $valeria,
            'Equipo para sucursal',
            'ganada',
            null,
            '2026-11-01 03:30:00',
            [[$products['Plan básico'], 3, 1200.00]],
        );
        $opportunities[] = $this->opportunity(
            $clients['María Coyoy'],
            $valeria,
            'Soporte del hogar',
            'ganada',
            null,
            '2026-11-01 06:30:00',
            [[$products['Soporte anual'], 1, 3600.00]],
        );
        $opportunities[] = $this->opportunity(
            $clients['Comercial El Quetzal'],
            $valeria,
            'Ampliación a bodega',
            'negociacion',
            null,
            null,
            [[$products['Plan avanzado'], 1, 2750.50]],
        );
        $opportunities[] = $this->opportunity(
            $clients['Ferretería Xela'],
            $valeria,
            'Capacitación de personal',
            'propuesta',
            null,
            null,
            [[$products['Instalación'], 2, 450.00]],
        );
        $opportunities[] = $this->opportunity(
            $clients['María Coyoy'],
            $valeria,
            'Plan familiar',
            'perdida',
            'Eligió a la competencia',
            '2026-10-20 18:00:00',
            [[$products['Plan básico'], 1, 1200.00], [$products['Licencia anterior'], 1, 999.99]],
        );
        $opportunities[] = $this->opportunity(
            $clients['Comercial El Quetzal'],
            $valeria,
            'Seguimiento anual',
            'prospecto',
            null,
            null,
            [],
        );
        $opportunities[] = $this->opportunity(
            $clients['Farmacia Los Altos'],
            $diego,
            'Licencias de caja',
            'ganada',
            null,
            '2026-10-10 15:00:00',
            [[$products['Plan básico'], 2, 1200.00], [$products['Soporte anual'], 1, 3600.00]],
        );
        $opportunities[] = $this->opportunity(
            $clients['Luis Tzul'],
            $diego,
            'Mantenimiento',
            'calificado',
            null,
            null,
            [[$products['Instalación'], 1, 450.00]],
        );
        $opportunities[] = $this->opportunity(
            $clients['Abarrotes San Juan'],
            $carlos,
            'Plan anual',
            'ganada',
            null,
            '2026-10-15 20:00:00',
            [[$products['Soporte anual'], 2, 3600.00]],
        );
        $opportunities[] = $this->opportunity(
            $clients['Taller El Volcán'],
            $carlos,
            'Cotización de equipo',
            'negociacion',
            null,
            null,
            [[$products['Plan avanzado'], 2, 2750.50]],
        );

        foreach ($opportunities as $opportunity) {
            $this->historyFor($opportunity);
        }

        $this->activity($clients['Comercial El Quetzal'], $valeria, 'llamada', 'Confirmar fecha de instalación.', '2026-10-15 15:00:00', true);
        $this->activity($clients['Ferretería Xela'], $valeria, 'tarea', 'Enviar propuesta de capacitación.', '2030-01-15 15:00:00', false);
        $this->activity($clients['María Coyoy'], $valeria, 'reunion', 'Presentar opciones de soporte.', '2026-09-01 15:00:00', false);
        $this->activity($clients['Farmacia Los Altos'], $diego, 'llamada', 'Renovación de licencias.', '2030-02-01 15:00:00', false);
    }

    private function seller(string $name, string $email, int $supervisorId, bool $active, string $password): User
    {
        return User::create([
            'name' => $name,
            'email' => $email,
            'password' => $password,
            'rol' => 'vendedor',
            'supervisor_id' => $supervisorId,
            'activo' => $active,
        ]);
    }

    private function client(?User $seller, string $name, ?string $nit, string $phone, ?string $email = null): Cliente
    {
        return Cliente::create([
            'vendedor_id' => $seller?->id,
            'nombre' => $name,
            'nit' => $nit,
            'telefono' => $phone,
            'correo' => $email,
        ]);
    }

    private function opportunity(Cliente $client, User $seller, string $title, string $stage, ?string $lostReason, ?string $closedAt, array $lines): Oportunidad
    {
        $opportunity = Oportunidad::create([
            'cliente_id' => $client->id,
            'vendedor_id' => $seller->id,
            'titulo' => $title,
            'etapa' => $stage,
            'motivo_perdida' => $lostReason,
            'fecha_cierre' => $closedAt,
        ]);

        foreach ($lines as [$product, $quantity, $unitPrice]) {
            $opportunity->productos()->attach($product->id, [
                'cantidad' => $quantity,
                'precio_unitario' => $unitPrice,
            ]);
        }

        return $opportunity;
    }

    private function historyFor(Oportunidad $opportunity): void
    {
        $stage = $opportunity->etapa;
        $path = match ($stage) {
            'prospecto' => ['prospecto'],
            'calificado' => ['prospecto', 'calificado'],
            'propuesta' => ['prospecto', 'calificado', 'propuesta'],
            'negociacion' => ['prospecto', 'calificado', 'propuesta', 'negociacion'],
            'ganada' => ['prospecto', 'calificado', 'propuesta', 'negociacion', 'ganada'],
            'perdida' => ['prospecto', 'calificado', 'propuesta', 'perdida'],
        };

        $date = Carbon::parse('2026-10-01 16:00:00');
        foreach ($path as $index => $newStage) {
            HistorialEtapa::create([
                'oportunidad_id' => $opportunity->id,
                'usuario_id' => $opportunity->vendedor_id,
                'etapa_anterior' => $index === 0 ? null : $path[$index - 1],
                'etapa_nueva' => $newStage,
                'created_at' => $date->copy()->addDays($index * 3),
            ]);
        }
    }

    private function activity(Cliente $client, User $seller, string $type, string $description, string $dueAt, bool $completed): void
    {
        Actividad::create([
            'cliente_id' => $client->id,
            'vendedor_id' => $seller->id,
            'tipo' => $type,
            'descripcion' => $description,
            'vence_en' => $dueAt,
            'completada' => $completed,
        ]);
    }
}
