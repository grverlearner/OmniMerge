<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table(
            'phase_single_elimination_settings',
            function (Blueprint $table) {
                $table
                    ->string('series_format', 24)
                    ->default('BEST_OF')
                    ->after('reseed_each_round');

                $table
                    ->unsignedSmallInteger('fixed_games')
                    ->default(1)
                    ->after('default_best_of');
            }
        );

        Schema::table(
            'phase_single_elimination_round_rules',
            function (Blueprint $table) {
                $table
                    ->string('series_format', 24)
                    ->default('BEST_OF')
                    ->after('participants_in_round');

                $table
                    ->unsignedSmallInteger('fixed_games')
                    ->default(1)
                    ->after('best_of');
            }
        );
    }

    public function down(): void
    {
        Schema::table(
            'phase_single_elimination_round_rules',
            function (Blueprint $table) {
                $table->dropColumn([
                    'series_format',
                    'fixed_games',
                ]);
            }
        );

        Schema::table(
            'phase_single_elimination_settings',
            function (Blueprint $table) {
                $table->dropColumn([
                    'series_format',
                    'fixed_games',
                ]);
            }
        );
    }
};
