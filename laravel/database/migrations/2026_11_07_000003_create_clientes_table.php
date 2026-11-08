<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clientes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendedor_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->string('nombre', 150);
            $table->string('nit', 12)->nullable()->unique();
            $table->char('telefono', 8)->unique();
            $table->string('correo', 150)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clientes');
    }
};
