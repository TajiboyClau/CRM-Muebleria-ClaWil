<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('rol', ['administrador', 'supervisor', 'vendedor'])
                ->default('vendedor')
                ->after('password');
            $table->foreignId('supervisor_id')
                ->nullable()
                ->after('rol')
                ->constrained('users')
                ->nullOnDelete();
            $table->boolean('activo')->default(true)->after('supervisor_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['supervisor_id']);
            $table->dropColumn(['rol', 'supervisor_id', 'activo']);
        });
    }
};
