<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'universes',
            function (Blueprint $table) {

                $table->id();

                /*
                |--------------------------------------------------------------------------
                | Propietario
                |--------------------------------------------------------------------------
                */

                $table
                    ->foreignId('user_id')
                    ->constrained()
                    ->cascadeOnDelete();

                /*
                |--------------------------------------------------------------------------
                | Identidad
                |--------------------------------------------------------------------------
                */

                $table->unsignedInteger(
                    'sequence_number'
                );

                $table->string(
                    'code',
                    30
                );

                $table->string(
                    'name',
                    150
                );

                $table->string(
                    'slug',
                    180
                );

                $table
                    ->text('description')
                    ->nullable();

                $table
                    ->string('image')
                    ->nullable();

                /*
                |--------------------------------------------------------------------------
                | Estado
                |--------------------------------------------------------------------------
                */

                $table
                    ->string('status', 20)
                    ->default('DRAFT');

                /*
                |--------------------------------------------------------------------------
                | Configuración extensible
                |--------------------------------------------------------------------------
                |
                | Sin uso todavía. Evita una migración aditiva para la
                | primera configuración pequeña que necesite el Universo.
                |
                */

                $table
                    ->json('settings')
                    ->nullable();

                /*
                |--------------------------------------------------------------------------
                | Laravel
                |--------------------------------------------------------------------------
                */

                $table->timestamps();

                $table->softDeletes();

                /*
                |--------------------------------------------------------------------------
                | Índices
                |--------------------------------------------------------------------------
                */

                $table->unique(
                    [
                        'user_id',
                        'sequence_number',
                    ],
                    'uni_user_sequence_unique'
                );

                $table->unique(
                    [
                        'user_id',
                        'code',
                    ],
                    'uni_user_code_unique'
                );

                $table->unique(
                    [
                        'user_id',
                        'slug',
                    ],
                    'uni_user_slug_unique'
                );

                $table->index(
                    [
                        'user_id',
                        'status',
                    ],
                    'uni_user_status_index'
                );
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'universes'
        );
    }
};
