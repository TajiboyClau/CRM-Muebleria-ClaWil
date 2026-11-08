<?php

use Illuminate\Support\Facades\DB;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('rol', 20)
                ->default('vendedor')
                ->after('password');
            $table->foreignId('supervisor_id')
                ->nullable()
                ->after('rol')
                ->constrained('users')
                ->nullOnDelete();
            $table->boolean('activo')->default(true)->after('supervisor_id');
        });

        if (Schema::getConnection()->getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE users ADD CONSTRAINT chk_users_rol CHECK (rol IN ('administrador', 'supervisor', 'vendedor'))");
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['supervisor_id']);
            $table->dropColumn(['rol', 'supervisor_id', 'activo']);
        });
    }
};
