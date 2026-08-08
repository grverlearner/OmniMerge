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
         * Estos contadores se utilizan en la cabecera del perfil.
         */

        $user->loadCount([
            'entities',
            'attributes',
            'collections',
        ]);


        return view(
            'profile.edit',
            compact('user')
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
