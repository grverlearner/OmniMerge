<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attributes', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('source_attribute_id')
                ->nullable()
                ->constrained('attributes')
                ->nullOnDelete();

            $table->string('code', 50);
            $table->string('name', 150);
            $table->string('slug', 180);

            $table->text('description')->nullable();
            $table->text('help_text')->nullable();
            $table->string('placeholder')->nullable();

            /*
             * TEXT, LONG_TEXT, INTEGER, DECIMAL,
             * BOOLEAN, DATE, COLOR, OPTION
             */
            $table->string('data_type', 30)
                ->default('TEXT');

            /*
             * FREE, CATALOG, MIXED
             */
            $table->string('value_source', 30)
                ->default('FREE');

            /*
             * TEXTBOX, TEXTAREA, NUMBER, SELECT,
             * MULTISELECT, RADIO, CHECKBOX,
             * TAGS, SLIDER, COLOR_PICKER, DATE_PICKER
             */
            $table->string('display_style', 30)
                ->default('TEXTBOX');

            $table->boolean('allows_multiple')
                ->default(false);

            $table->boolean('allows_custom_values')
                ->default(false);

            $table->boolean('is_required')
                ->default(false);

            $table->boolean('is_filterable')
                ->default(true);

            $table->boolean('is_comparable')
                ->default(true);

            $table->boolean('is_searchable')
                ->default(true);

            $table->boolean('is_visible')
                ->default(true);

            $table->boolean('is_featured')
                ->default(false);

            $table->decimal(
                'min_numeric_value',
                18,
                4
            )->nullable();

            $table->decimal(
                'max_numeric_value',
                18,
                4
            )->nullable();

            $table->unsignedInteger('min_length')
                ->nullable();

            $table->unsignedInteger('max_length')
                ->nullable();

            $table->string('unit', 30)->nullable();

            $table->unsignedInteger('sort_order')
                ->default(0);

            $table->unsignedInteger('hierarchy_level')
                ->default(0);

            $table->string('scope', 20)
                ->default('PRIVATE');

            $table->json('default_value')->nullable();
            $table->json('validation_rules')->nullable();
            $table->json('configuration')->nullable();

            $table->string('status', 20)
                ->default('ACTIVE');

            $table->timestamps();
            $table->softDeletes();

            $table->unique(
                ['user_id', 'code'],
                'attributes_user_code_unique'
            );

            $table->unique(
                ['user_id', 'slug'],
                'attributes_user_slug_unique'
            );

            $table->index(['user_id', 'status']);
            $table->index('data_type');
            $table->index('value_source');
            $table->index('sort_order');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attributes');
    }
};