@php
    /*
     * Olvidé la contraseña.
     *
     * Era la vista de Breeze sin tocar: en inglés, en claro y prometiendo un
     * correo. Esa promesa depende de la instalación: con MAIL_MAILER=log el
     * enlace se escribe en el registro del servidor y no llega a ningún buzón.
     * Cuando es así, se dice, en vez de dejar a alguien esperando un correo que
     * no va a llegar.
     */

    $correoDeVerdad = ! in_array(config('mail.default'), ['log', 'array'], true);
@endphp

<x-guest-layout>

    <x-slot name="titulo">Recuperar la contraseña</x-slot>

    <div>
        <p class="text-[11px] font-black uppercase tracking-[0.2em] text-indigo-400/80">Sin problema</p>

        <h1 class="mt-2 text-3xl font-black tracking-tight text-white">Recupera tu contraseña</h1>

        <p class="mt-2 text-[14px] leading-relaxed text-slate-400">
            Escribe el correo de tu cuenta y te mandamos un enlace para elegir una contraseña
            nueva.
        </p>
    </div>

    @unless ($correoDeVerdad)
        <div class="mt-5 rounded-xl border border-amber-500/30 bg-amber-500/10 px-3.5 py-2.5">
            <p class="text-[12px] font-black text-amber-200">En esta instalación el correo no sale</p>
            <p class="mt-0.5 text-[11px] leading-4 text-amber-200/70">
                Está configurado para escribirse en el registro del servidor, no para enviarse.
                El enlace se generará igual, pero no llegará a tu buzón: quien administre el
                servidor lo encontrará en <code class="font-mono">storage/logs</code>.
            </p>
        </div>
    @endunless

    <x-auth-session-status class="mt-5" :status="session('status')" />

    <form method="POST" action="{{ route('password.email') }}" class="mt-6 space-y-4">
        @csrf

        @include('auth.partials.campo', [
            'nombre' => 'email',
            'etiqueta' => 'Correo',
            'tipo' => 'email',
            'icono' => 'correo',
            'valor' => old('email'),
            'autocomplete' => 'email',
            'placeholder' => 'tu@correo.com',
            'autofocus' => true,
        ])

        <button type="submit"
            class="flex w-full items-center justify-center gap-2 rounded-xl bg-indigo-500 px-5 py-3.5 text-[14px] font-black text-white shadow-lg shadow-indigo-500/20 transition hover:-translate-y-0.5 hover:bg-indigo-400 focus:outline-none focus:ring-2 focus:ring-indigo-400 focus:ring-offset-2 focus:ring-offset-slate-900">
            Enviarme el enlace
            <x-omni-icon name="flecha-derecha" size="h-4 w-4" />
        </button>
    </form>

    <div class="mt-6 border-t border-white/10 pt-5 text-center">
        <a href="{{ route('login') }}"
            class="inline-flex items-center gap-1.5 text-[13px] font-black text-indigo-400 transition hover:text-indigo-200">
            <x-omni-icon name="flecha-izquierda" size="h-3.5 w-3.5" />
            Volver a entrar
        </a>
    </div>

</x-guest-layout>
