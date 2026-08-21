<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /*
    |--------------------------------------------------------------------------
    | Corrección arquitectónica
    |--------------------------------------------------------------------------
    |
    | tournament_templates.universe_id ataba una plantilla a un solo
    | Universo, rompiendo su reutilización. Se traslada la información
    | ya existente a universe_tournaments y se elimina la columna.
    |
    | Ver docs/md/23-Fase-Universos-Workspace.md §1.
    |
    */

    public function up(): void
    {
        if (
            ! Schema::hasColumn(
                'tournament_templates',
                'universe_id'
            )
        ) {
            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Traslado de datos
        |--------------------------------------------------------------------------
        */

        DB::table('tournament_templates')
            ->whereNotNull('universe_id')
            ->orderBy('id')
            ->chunkById(
                100,

                function ($templates) {

                    $now = now();

                    $rows = [];

                    foreach ($templates as $template) {

                        $rows[] = [

                            'universe_id' =>
                            $template->universe_id,

                            'tournament_template_id' =>
                            $template->id,

                            'name' =>
                            $template->name,

                            'description' =>
                            null,

                            'status' =>
                            $template->status === 'ACTIVE'
                                ? 'ACTIVE'
                                : 'DRAFT',

                            'created_at' =>
                            $now,

                            'updated_at' =>
                            $now,
                        ];
                    }

                    if ($rows) {

                        DB::table('universe_tournaments')
                            ->insert($rows);
                    }
                }
            );

        /*
        |--------------------------------------------------------------------------
        | Eliminación de la columna
        |--------------------------------------------------------------------------
        */

        Schema::table(
            'tournament_templates',

            function (Blueprint $table) {

                $table->dropForeign(
                    ['universe_id']
                );

                $table->dropIndex(
                    'tt_universe_index'
                );

                $table->dropColumn(
                    'universe_id'
                );
            }
        );
    }

    public function down(): void
    {
        if (
            Schema::hasColumn(
                'tournament_templates',
                'universe_id'
            )
        ) {
            return;
        }

        Schema::table(
            'tournament_templates',

            function (Blueprint $table) {

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

        /*
         * Restauración best-effort: si una plantilla fue adoptada
         * por varios Universos, solo puede volver a uno.
         */
        DB::table('universe_tournaments')
            ->whereNull('deleted_at')
            ->orderBy('id')
            ->get()
            ->groupBy('tournament_template_id')
            ->each(
                function ($rows, $templateId) {

                    DB::table('tournament_templates')
                        ->where('id', $templateId)
                        ->update([

                            'universe_id' =>
                            $rows->first()->universe_id,
                        ]);
                }
            );
    }
};
