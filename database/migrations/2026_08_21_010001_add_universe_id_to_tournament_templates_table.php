<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table(
            'tournament_templates',
            function (Blueprint $table) {

                /*
                |--------------------------------------------------------------------------
                | Universo (opcional)
                |--------------------------------------------------------------------------
                |
                | Nullable a propósito: las plantillas existentes no
                | pertenecen a ningún Universo y deben seguir funcionando
                | exactamente igual. Eliminar un Universo desasocia sus
                | plantillas en vez de borrarlas (nullOnDelete).
                |
                */

                $table
                    ->foreignId('universe_id')
                    ->nullable()
                    ->after('user_id')
                    ->constrained('universes')
                    ->nullOnDelete();

                $table->index(
                    'universe_id',
                    'tt_universe_index'
                );
            }
        );
    }

    public function down(): void
    {
        Schema::table(
            'tournament_templates',
            function (Blueprint $table) {

                $table->dropConstrainedForeignId(
                    'universe_id'
                );
            }
        );
    }
};
