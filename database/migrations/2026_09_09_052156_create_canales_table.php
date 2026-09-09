<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('canales', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');          // Nombre del canal (ej. Red Uno, Unitel, etc.)
            $table->string('enlace_streaming'); // El enlace .m3u8 o la URL de la señal
            $table->string('logo')->nullable(); // Imagen o logo opcional del canal
            $table->enum('estado', ['Activo', 'Inactivo'])->default('Activo'); // Para saber si está habilitado o no
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('canales');
    }
};
