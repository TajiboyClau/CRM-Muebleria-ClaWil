<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'sqlite') {
            return;
        }

        if ($driver === 'pgsql') {
            DB::statement("ALTER TABLE clientes ADD CONSTRAINT chk_clientes_telefono CHECK (telefono ~ '^[2-9][0-9]{7}$')");
            return;
        }

        DB::statement("ALTER TABLE clientes ADD CONSTRAINT chk_clientes_telefono CHECK (telefono REGEXP '^[2-9][0-9]{7}$')");
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'sqlite') {
            DB::statement('ALTER TABLE clientes DROP CONSTRAINT chk_clientes_telefono');
        }
    }
};
