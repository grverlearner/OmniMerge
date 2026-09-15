@php
    /*
     * Elegir la contraseña nueva, desde el enlace del correo.
     *
     * Era la vista de Breeze en inglés. Ahora dice el único requisito que de
     * verdad se valida —Password::defaults() sin configurar: al menos 8
     * caracteres— y nada que no se compruebe.
     */
@endphp

<x-guest-layout>

    <x-slot name="titulo">Contraseña nueva</x-slot>

    <div>
        <p class="text-[11px] font-black uppercase tracking-[0.2em] text-indigo-400/80">Casi está</p>

        <h1 class="mt-2 text-3xl font-black tracking-tight text-white">Elige una contraseña nueva</h1>

        <p class="mt-2 text-[14px] leading-relaxed text-slate-400">
            Al guardarla podrás entrar con ella. La anterior deja de servir.
        </p>
    </div>

    <form method="POST" action="{{ route('password.store') }}" class="mt-6 space-y-4">
        @csrf

        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        @include('auth.partials.campo', [
            'nombre' => 'email',
            'etiqueta' => 'Correo',
            'tipo' => 'email',
            'icono' => 'correo',
            'valor' => old('email', $request->email),
            'autocomplete' => 'username',
        ])

        @include('auth.partials.clave', [
            'nombre' => 'password',
            'etiqueta' => 'Contraseña nueva',
            'autocomplete' => 'new-password',
            'placeholder' => 'Al menos 8 caracteres',
            'ayuda' => 'Al menos 8 caracteres.',
            'autofocus' => true,
        ])

        @include('auth.partials.clave', [
            'nombre' => 'password_confirmation',
            'etiqueta' => 'Repítela',
            'autocomplete' => 'new-password',
            'placeholder' => 'La misma otra vez',
        ])

        <button type="submit"
            class="flex w-full items-center justify-center gap-2 rounded-xl bg-indigo-500 px-5 py-3.5 text-[14px] font-black text-white shadow-lg shadow-indigo-500/20 transition hover:-translate-y-0.5 hover:bg-indigo-400 focus:outline-none focus:ring-2 focus:ring-indigo-400 focus:ring-offset-2 focus:ring-offset-slate-900">
            Guardar la contraseña nueva
        </button>
    </form>

</x-guest-layout>
