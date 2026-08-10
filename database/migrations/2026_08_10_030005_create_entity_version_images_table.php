<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'entity_version_images',
            function (Blueprint $table) {

                $table->id();

                $table
                    ->foreignId(
                        'entity_version_id'
                    )
                    ->constrained()
                    ->cascadeOnDelete();

                $table
                    ->string(
                        'image'
                    );

                $table
                    ->string(
                        'caption',
                        200
                    )
                    ->nullable();

                $table
                    ->unsignedInteger(
                        'sort_order'
                    )
                    ->default(0);

                $table->timestamps();


                $table->index([
                    'entity_version_id',
                    'sort_order',
                ]);
            }
        );
    }


    public function down(): void
    {
        Schema::dropIfExists(
            'entity_version_images'
        );
    }
};
