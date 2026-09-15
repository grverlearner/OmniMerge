<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\User;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Mostrar perfil
    |--------------------------------------------------------------------------
    */

    public function edit(
        Request $request
    ): View {

        /** @var User $user */
        $user = $request->user();

        /*
        |----------------------------------------------------------------------
        | Que se ve de ti
        |----------------------------------------------------------------------
        |
        | La pregunta que esta pantalla no contestaba. Se podia cambiar el
        | perfil a publico sin tener ni idea de QUE quedaba visible, porque la
        | visibilidad de cada cosa se decide en la ficha de cada cosa, una por
        | una, en otra pantalla.
        |
        | Aqui se cuenta: de cada tipo, cuantas hay y cuantas de esas son
        | publicas. Con el enlace a donde se cambia.
        */

        $publicas = fn($relacion, string $columna = 'visibility') =>
            (clone $relacion)
            ->where($columna, 'PUBLIC')
            ->where('status', 'ACTIVE')
            ->whereNotNull('published_at')
            ->count();

        $loQueSeVe = [

            [
                'etiqueta' => 'Entidades',
                'icono' => 'libro',
                'tono' => '#818cf8',
                'total' => $user->entities()->count(),
                'publicas' => $publicas($user->entities()),
                'url' => route('entities.index'),
            ],

            [
                'etiqueta' => 'Colecciones',
                'icono' => 'capas',
                'tono' => '#34d399',
                'total' => $user->collections()->count(),
                'publicas' => $publicas($user->collections()),
                'url' => route('collections.index'),
            ],

            [
                'etiqueta' => 'Atributos',
                'icono' => 'controles',
                'tono' => '#22d3ee',
                'total' => $user->attributes()->count(),
                'publicas' => $publicas($user->attributes(), 'scope'),
                'url' => route('attributes.index'),
            ],

            [
                'etiqueta' => 'Torneos',
                'icono' => 'trofeo',
                'tono' => '#fbbf24',
                'total' => $user->tournamentTemplates()->count(),
                'publicas' => $publicas($user->tournamentTemplates()),
                'url' => route('tournaments.templates.index'),
            ],

            [
                'etiqueta' => 'Fases',
                'icono' => 'grafo',
                'tono' => '#f472b6',
                'total' => $user->phaseTemplates()->count(),
                'publicas' => $publicas($user->phaseTemplates()),
                'url' => route('tournaments.phase-templates.index'),
            ],
        ];

        /*
         * Los universos no se publican: son tuyos y solo tuyos. Se dice, en vez
         * de dejar que alguien se pregunte por que no aparecen en la lista.
         */
        $privados = $user->universes()->count();


        return view(
            'profile.edit',
            compact('user', 'loQueSeVe', 'privados')
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Actualizar perfil
    |--------------------------------------------------------------------------
    */

    public function update(
        ProfileUpdateRequest $request
    ): RedirectResponse {

        /** @var User $user */
        $user = $request->user();


        /*
         * Obtenemos todos los datos validados excepto los datos auxiliares
         * relacionados con archivos.
         */

        $data = $request
            ->safe()
            ->except([
                'avatar',
                'remove_avatar',
            ]);


        /*
         * Si se solicita eliminar la imagen actual o subir una nueva,
         * eliminamos primero el archivo anterior.
         */

        if (
            (
                $request->boolean('remove_avatar')
                ||
                $request->hasFile('avatar')
            )
            &&
            $user->avatar
        ) {
            $this->deleteAvatar(
                $user->avatar
            );
        }


        /*
         * Nueva imagen.
         */

        if ($request->hasFile('avatar')) {

            $data['avatar'] =
                $request
                ->file('avatar')
                ->store(
                    'avatars',
                    'public'
                );
        } elseif (
            $request->boolean('remove_avatar')
        ) {

            $data['avatar'] = null;
        }


        /*
         * Actualizamos el usuario.
         */

        $user->fill($data);


        /*
         * Si cambió el correo, Laravel considera nuevamente el correo
         * como no verificado.
         */

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }


        $user->save();


        return Redirect::route(
            'profile.edit'
        )->with(
            'status',
            'profile-updated'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Eliminar cuenta
    |--------------------------------------------------------------------------
    */

    public function destroy(
        Request $request
    ): RedirectResponse {

        $request->validateWithBag(
            'userDeletion',
            [
                'password' => [
                    'required',
                    'current_password',
                ],
            ]
        );


        /** @var User $user */
        $user = $request->user();


        /*
         * Eliminamos el avatar antes del borrado lógico del usuario
         * para evitar archivos huérfanos.
         */

        if ($user->avatar) {
            $this->deleteAvatar(
                $user->avatar
            );
        }


        Auth::logout();


        $user->delete();


        $request
            ->session()
            ->invalidate();


        $request
            ->session()
            ->regenerateToken();


        return Redirect::to('/');
    }


    /*
    |--------------------------------------------------------------------------
    | Helper privado para eliminar avatar
    |--------------------------------------------------------------------------
    */

    private function deleteAvatar(
        ?string $path
    ): void {

        if (! $path) {
            return;
        }


        /** @var FilesystemAdapter $disk */
        $disk =
            Storage::disk('public');


        if ($disk->exists($path)) {
            $disk->delete($path);
        }
    }
}
