<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| Cuando se trajo por ultima vez lo de la Biblioteca
|--------------------------------------------------------------------------
|
| imported_at dice cuando ENTRO al Universo y no cambia nunca. Hacia falta
| otra fecha: la de la ultima vez que se puso al dia. Reutilizar la primera
| habria borrado el dato de cuando llego.
|
*/
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('universe_entities', function (Blueprint $table) {
            $table->timestamp('synced_at')
                ->nullable()
                ->after('imported_at');
        });
    }

    public function down(): void
    {
        Schema::table('universe_entities', function (Blueprint $table) {
            $table->dropColumn('synced_at');
        });
    }
};
