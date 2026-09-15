<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class NewPasswordController extends Controller
{
    /**
     * Display the password reset view.
     */
    public function create(Request $request): View
    {
        return view('auth.reset-password', ['request' => $request]);
    }

    /**
     * Handle an incoming new password request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ], [
            'token.required' => 'Falta el enlace de restablecimiento. Pide uno nuevo.',
            'email.required' => 'El correo es obligatorio.',
            'email.email' => 'Ingresa un correo electrónico válido.',
            'password.required' => 'La contraseña es obligatoria.',
            'password.confirmed' => 'Las contraseñas no coinciden.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
        ]);

        // Here we will attempt to reset the user's password. If it is successful we
        // will update the password on an actual user model and persist it to the
        // database. Otherwise we will parse the error and return the response.
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user) use ($request) {
                $user->forceFill([
                    'password' => Hash::make($request->password),
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            }
        );

        // If the password was successfully reset, we will redirect the user back to
        // the application's home authenticated view. If there is an error we can
        // redirect them back to where they came from with their error message.
        return $status == Password::PASSWORD_RESET
                    ? redirect()->route('login')->with('status', $this->mensaje($status))
                    : back()->withInput($request->only('email'))
                        ->withErrors(['email' => $this->mensaje($status)]);
    }

    /*
     * Los mensajes del broker de contraseñas, en español.
     *
     * Salían con __($status) y, sin carpeta lang y con la app en «en», el
     * usuario leía los textos de fábrica de Laravel en inglés. El resto del
     * proyecto escribe sus mensajes en español directamente en el código
     * (RegisteredUserController, LoginRequest), y aquí se hace igual.
     */
    private function mensaje(string $status): string
    {
        return match ($status) {
            Password::RESET_LINK_SENT => 'Te hemos mandado un enlace para elegir una contraseña nueva.',
            Password::PASSWORD_RESET => 'Contraseña cambiada. Ya puedes entrar con la nueva.',
            Password::INVALID_USER => 'No hay ninguna cuenta con ese correo.',
            Password::INVALID_TOKEN => 'El enlace ya no sirve: caducó o ya se usó. Pide otro.',
            Password::RESET_THROTTLED => 'Espera un momento antes de volver a pedirlo.',
            default => __($status),
        };
    }
}
