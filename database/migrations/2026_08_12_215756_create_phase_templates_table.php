<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('phase_templates', function (Blueprint $table) {
            $table->id();

            /*
            |--------------------------------------------------------------------------
            | Propietario y procedencia
            |--------------------------------------------------------------------------
            */

            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->foreignId('source_phase_template_id')
                ->nullable()
                ->constrained('phase_templates')
                ->nullOnDelete();

            /*
            |--------------------------------------------------------------------------
            | Identidad
            |--------------------------------------------------------------------------
            */

            $table->unsignedInteger('sequence_number');

            $table->string('code', 30);
            $table->string('name', 150);
            $table->string('slug', 180);

            $table->text('description')->nullable();
            $table->string('image')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Tipo de Fase
            |--------------------------------------------------------------------------
            */

            $table->string('phase_type', 40)->default('SINGLE_ELIMINATION');

            $table->string('participant_mode', 20)->default('INDIVIDUAL');

            /*
            |--------------------------------------------------------------------------
            | Contrato de entrada
            |--------------------------------------------------------------------------
            */

            $table->unsignedSmallInteger('min_participants')->default(2);
            $table->unsignedSmallInteger('max_participants')->nullable();
            $table->unsignedSmallInteger('exact_participants')->nullable();
            $table->unsignedSmallInteger('participant_multiple')->nullable();

            $table->boolean('allow_byes')->default(false);

            /*
            |--------------------------------------------------------------------------
            | Configuración competitiva inicial
            |--------------------------------------------------------------------------
            |
            | En T1 solo es una base. SINGLE_ELIMINATION será el
            | primer tipo que desarrollaremos completamente.
            |
            */

            $table->unsignedTinyInteger('best_of')->default(1);

            /*
            |--------------------------------------------------------------------------
            | Estado
            |--------------------------------------------------------------------------
            */

            $table->string('status', 20)->default('DRAFT');
            $table->string('visibility', 20)->default('PRIVATE');

            /*
            |--------------------------------------------------------------------------
            | Comunidad futura
            |--------------------------------------------------------------------------
            */

            $table->boolean('allow_cloning')->default(true);

            $table->unsignedBigInteger('views_count')->default(0);
            $table->unsignedBigInteger('clones_count')->default(0);

            $table->timestamp('published_at')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Extensión
            |--------------------------------------------------------------------------
            */

            $table->json('settings')->nullable();
            $table->json('metadata')->nullable();

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
                ['user_id', 'sequence_number'],
                'pt_user_sequence_unique'
            );

            $table->unique(
                ['user_id', 'code'],
                'pt_user_code_unique'
            );

            $table->unique(
                ['user_id', 'slug'],
                'pt_user_slug_unique'
            );

            $table->index(
                ['user_id', 'status'],
                'pt_user_status_index'
            );

            $table->index(
                ['user_id', 'phase_type'],
                'pt_user_type_index'
            );

            $table->index(
                ['visibility', 'status', 'published_at'],
                'pt_public_index'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('phase_templates');
    }
};
