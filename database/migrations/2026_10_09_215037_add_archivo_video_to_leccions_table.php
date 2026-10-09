<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Video subido desde el computador (ruta en el disco privado). Convive con url_video (YouTube/Drive).
     */
    public function up(): void
    {
        Schema::table('leccions', function (Blueprint $table) {
            $table->string('archivo_video')->nullable()->after('url_video');
        });
    }

    public function down(): void
    {
        Schema::table('leccions', function (Blueprint $table) {
            $table->dropColumn('archivo_video');
        });
    }
};
