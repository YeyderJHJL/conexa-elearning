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
        Schema::table('leccions', function (Blueprint $table) {
            $table->string('url_video')->nullable()->after('contenido');
            $table->string('archivo_pdf')->nullable()->after('url_video');
            $table->dropColumn(['tipo', 'url_recurso']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('leccions', function (Blueprint $table) {
            $table->string('tipo')->default('texto')->after('titulo');
            $table->string('url_recurso', 500)->nullable()->after('contenido');
            $table->dropColumn(['url_video', 'archivo_pdf']);
        });
    }
};
