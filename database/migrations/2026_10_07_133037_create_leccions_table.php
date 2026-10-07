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
        Schema::create('leccions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('modulo_id')->constrained()->cascadeOnDelete();
            $table->string('titulo');
            $table->string('tipo')->default('texto'); // texto | video | pdf
            $table->longText('contenido')->nullable();
            $table->string('url_recurso', 500)->nullable();
            $table->unsignedSmallInteger('duracion_min')->nullable();
            $table->unsignedSmallInteger('orden')->default(0);
            $table->boolean('activa')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leccions');
    }
};
