<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| El espacio de administración
|--------------------------------------------------------------------------
|
| Lo que necesita un admin para tener el control de verdad:
|
|   users            bloquear una cuenta (con motivo y, si se quiere, fecha
|                    de fin) y reconocer a un creador —Verificado o
|                    Confiable— con una etiqueta visible
|   content_flags    las mismas etiquetas sobre cualquier contenido
|                    (entidad, colección, plantilla…), más «destacado» y
|                    «oculto» para moderar la comunidad sin borrar nada
|   site_settings    lo que se configura del sitio entero: nombre, icono,
|                    color, anuncio, mantenimiento, registro
|   admin_actions    qué hizo cada admin, a quién y cuándo
|
*/

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('banned_at')->nullable()->after('status');
            $table->timestamp('banned_until')->nullable()->after('banned_at');
            $table->text('ban_reason')->nullable()->after('banned_until');
            $table->foreignId('banned_by')->nullable()->after('ban_reason')->constrained('users')->nullOnDelete();

            $table->string('creator_badge', 20)->nullable()->after('banned_by');
            $table->string('creator_badge_note', 200)->nullable()->after('creator_badge');

            $table->index('banned_at');
            $table->index('creator_badge');
        });

        Schema::create('content_flags', function (Blueprint $table) {
            $table->id();
            $table->string('content_type', 40);
            $table->unsignedBigInteger('content_id');
            $table->string('flag', 20);
            $table->string('note', 200)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['content_type', 'content_id', 'flag']);
            $table->index(['content_type', 'flag']);
        });

        Schema::create('site_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key', 80)->unique();
            $table->json('value')->nullable();
            $table->timestamps();
        });

        Schema::create('admin_actions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admin_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 60);
            $table->string('target_type', 40)->nullable();
            $table->unsignedBigInteger('target_id')->nullable();
            $table->string('target_label', 200)->nullable();
            $table->json('details')->nullable();
            $table->string('ip', 45)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['target_type', 'target_id']);
            $table->index('action');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_actions');
        Schema::dropIfExists('site_settings');
        Schema::dropIfExists('content_flags');

        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('banned_by');
            $table->dropIndex(['banned_at']);
            $table->dropIndex(['creator_badge']);
            $table->dropColumn(['banned_at', 'banned_until', 'ban_reason', 'creator_badge', 'creator_badge_note']);
        });
    }
};
