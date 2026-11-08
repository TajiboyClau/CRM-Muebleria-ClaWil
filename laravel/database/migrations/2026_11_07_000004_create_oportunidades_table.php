<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('oportunidades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->constrained('clientes')->cascadeOnDelete();
            $table->foreignId('vendedor_id')->constrained('users')->restrictOnDelete();
            $table->string('titulo', 150);
            $table->string('etapa', 20)->default('prospecto');
            $table->string('motivo_perdida', 255)->nullable();
            $table->dateTime('fecha_cierre')->nullable();
            $table->timestamps();

        });

        if (Schema::getConnection()->getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE oportunidades ADD CONSTRAINT chk_oportunidades_etapa CHECK (etapa IN ('prospecto', 'calificado', 'propuesta', 'negociacion', 'ganada', 'perdida'))");
            DB::statement("ALTER TABLE oportunidades ADD CONSTRAINT chk_oportunidades_motivo CHECK ((etapa = 'perdida' AND motivo_perdida IS NOT NULL) OR (etapa <> 'perdida' AND motivo_perdida IS NULL))");
            DB::statement("ALTER TABLE oportunidades ADD CONSTRAINT chk_oportunidades_cierre CHECK ((etapa IN ('ganada', 'perdida') AND fecha_cierre IS NOT NULL) OR (etapa NOT IN ('ganada', 'perdida') AND fecha_cierre IS NULL))");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('oportunidades');
    }
};
