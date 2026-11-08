<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('historial_etapas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('oportunidad_id')->constrained('oportunidades')->cascadeOnDelete();
            $table->foreignId('usuario_id')->constrained('users')->restrictOnDelete();
            $table->string('etapa_anterior', 20)->nullable();
            $table->string('etapa_nueva', 20);
            $table->timestamp('created_at')->useCurrent();
        });

        if (Schema::getConnection()->getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE historial_etapas ADD CONSTRAINT chk_historial_etapas CHECK ((etapa_anterior IS NULL OR etapa_anterior IN ('prospecto', 'calificado', 'propuesta', 'negociacion', 'ganada', 'perdida')) AND etapa_nueva IN ('prospecto', 'calificado', 'propuesta', 'negociacion', 'ganada', 'perdida'))");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('historial_etapas');
    }
};
