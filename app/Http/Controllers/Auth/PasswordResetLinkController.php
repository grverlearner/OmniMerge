<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PasswordResetLinkController extends Controller
{
    /**
     * Display the password reset link request view.
     */
    public function create(): View
    {
        return view('auth.forgot-password');
    }

    /**
     * Handle an incoming password reset link request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        // We will send the password reset link to this user. Once we have attempted
        // to send the link, we will examine the response then see the message we
        // need to show to the user. Finally, we'll send out a proper response.
        $status = Password::sendResetLink(
            $request->only('email')
        );

        return $status == Password::RESET_LINK_SENT
                    ? back()->with('status', $this->mensaje($status))
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
