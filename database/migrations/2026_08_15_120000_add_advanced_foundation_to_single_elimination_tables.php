<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table(
            'phase_single_elimination_settings',
            function (Blueprint $table) {
                $table->string('configuration_mode', 20)
                    ->default('BASIC')
                    ->after('phase_template_id');

                $table->string('input_mode', 24)
                    ->default('POOL')
                    ->after('configuration_mode');

                $table->string('routing_mode', 24)
                    ->default('AUTOMATIC')
                    ->after('input_mode');

                $table->unsignedSmallInteger('entrants_per_match')
                    ->default(2)
                    ->after('routing_mode');

                $table->unsignedSmallInteger('qualifiers_per_match')
                    ->default(1)
                    ->after('entrants_per_match');

                $table->string('encounter_profile', 32)
                    ->default('DUEL')
                    ->after('qualifiers_per_match');

                $table->string('remainder_policy', 32)
                    ->default('REJECT')
                    ->after('encounter_profile');
            }
        );

        /*
        |--------------------------------------------------------------------------
        | Compatibilidad con las fases existentes
        |--------------------------------------------------------------------------
        |
        | Las fases que ya permitían BYEs conservarán ese comportamiento.
        | Las que no los permitían mantendrán REJECT.
        |
        */

        DB::table('phase_single_elimination_settings')
            ->whereIn(
                'phase_template_id',
                DB::table('phase_templates')
                    ->where('allow_byes', true)
                    ->select('id')
            )
            ->update([
                'remainder_policy' => 'BYE',
            ]);

        Schema::table(
            'phase_single_elimination_round_rules',
            function (Blueprint $table) {
                $table->unsignedSmallInteger('entrants_per_match')
                    ->nullable()
                    ->after('participants_in_round');

                $table->unsignedSmallInteger('qualifiers_per_match')
                    ->nullable()
                    ->after('entrants_per_match');

                $table->string('encounter_profile', 32)
                    ->nullable()
                    ->after('qualifiers_per_match');
            }
        );
    }

    public function down(): void
    {
        Schema::table(
            'phase_single_elimination_round_rules',
            function (Blueprint $table) {
                $table->dropColumn([
                    'entrants_per_match',
                    'qualifiers_per_match',
                    'encounter_profile',
                ]);
            }
        );

        Schema::table(
            'phase_single_elimination_settings',
            function (Blueprint $table) {
                $table->dropColumn([
                    'configuration_mode',
                    'input_mode',
                    'routing_mode',
                    'entrants_per_match',
                    'qualifiers_per_match',
                    'encounter_profile',
                    'remainder_policy',
                ]);
            }
        );
    }
};
