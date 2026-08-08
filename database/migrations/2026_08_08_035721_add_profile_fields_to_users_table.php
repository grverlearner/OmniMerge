<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {

            // Ruta de la foto almacenada en storage.
            $table->string('avatar')
                ->nullable();

            // Descripción corta del creador.
            // Ejemplo: "Creador de universos de fantasía".
            $table->string('headline', 120)
                ->nullable();

            // Presentación más completa.
            $table->text('bio')
                ->nullable();

            // Ubicación opcional escrita por el usuario.
            $table->string('location', 100)
                ->nullable();

            // Página web o enlace personal.
            $table->string('website', 255)
                ->nullable();

            // Decide si el perfil puede verse desde Comunidad.
            $table->string('profile_visibility', 20)
                ->default('PUBLIC');

            $table->index('profile_visibility');
        });
    }


    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {

            $table->dropIndex(
                'users_profile_visibility_index'
            );

            $table->dropColumn([
                'avatar',
                'headline',
                'bio',
                'location',
                'website',
                'profile_visibility',
            ]);
        });
    }
};