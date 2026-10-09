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
        Schema::table('areas', function (Blueprint $table) {
            $table->string('color', 7)->nullable()->after('icono');
            $table->string('imagen')->nullable()->after('color');
        });

        Schema::table('modulos', function (Blueprint $table) {
            $table->string('imagen')->nullable()->after('descripcion');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('areas', function (Blueprint $table) {
            $table->dropColumn(['color', 'imagen']);
        });

        Schema::table('modulos', function (Blueprint $table) {
            $table->dropColumn('imagen');
        });
    }
};
