@php
    /*
     * Entrar.
     *
     * Antes decía «accede a tu biblioteca, tus entidades, atributos, colecciones
     * y contenido de la comunidad»: la mitad de lo que hay. Ahora habla de lo que
     * uno se ha dejado a medias —sus mundos y sus torneos también—, en oscuro y
     * con los campos compartidos del resto del flujo.
     */
@endphp

<x-guest-layout>

    <x-slot name="titulo">Entrar</x-slot>

    <div>
        <p class="text-[11px] font-black uppercase tracking-[0.2em] text-indigo-400/80">De vuelta</p>

        <h1 class="mt-2 text-3xl font-black tracking-tight text-white">Entra en OmniMerge</h1>

        <p class="mt-2 text-[14px] leading-relaxed text-slate-400">
            Tus entidades, tus mundos y tus torneos siguen donde los dejaste.
        </p>
    </div>

    <x-auth-session-status class="mt-5" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="mt-6 space-y-4">
        @csrf

        @include('auth.partials.campo', [
            'nombre' => 'email',
            'etiqueta' => 'Correo',
            'tipo' => 'email',
            'icono' => 'correo',
            'valor' => old('email'),
            'autocomplete' => 'username',
            'placeholder' => 'tu@correo.com',
            'autofocus' => true,
        ])

        @include('auth.partials.clave', [
            'nombre' => 'password',
            'etiqueta' => 'Contraseña',
            'autocomplete' => 'current-password',
            'placeholder' => 'Tu contraseña',
            'enlace' => Route::has('password.request') ? [route('password.request'), '¿La olvidaste?'] : null,
        ])

        <label for="remember" class="flex cursor-pointer items-center gap-2.5">
            <input id="remember" type="checkbox" name="remember"
                class="rounded border-slate-700 bg-slate-950 text-indigo-500 focus:ring-indigo-500 focus:ring-offset-slate-900">
            <span class="text-[13px] text-slate-400">Mantener la sesión abierta en este equipo</span>
        </label>

        <button type="submit"
            class="flex w-full items-center justify-center gap-2 rounded-xl bg-indigo-500 px-5 py-3.5 text-[14px] font-black text-white shadow-lg shadow-indigo-500/20 transition hover:-translate-y-0.5 hover:bg-indigo-400 focus:outline-none focus:ring-2 focus:ring-indigo-400 focus:ring-offset-2 focus:ring-offset-slate-900">
            Entrar
            <x-omni-icon name="flecha-derecha" size="h-4 w-4" />
        </button>
    </form>

    <div class="mt-6 border-t border-white/10 pt-5 text-center">
        <p class="text-[13px] text-slate-500">
            ¿Todavía no tienes cuenta?
            <a href="{{ route('register') }}" class="ml-1 font-black text-indigo-400 transition hover:text-indigo-200">
                Crear una
            </a>
        </p>
    </div>

</x-guest-layout>
