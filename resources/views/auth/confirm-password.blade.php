@php
    /*
     * Confirmar la contraseña antes de algo delicado.
     *
     * Era la vista de Breeze en inglés. Laravel la muestra cuando una ruta pide
     * haber tecleado la contraseña hace poco.
     */
@endphp

<x-guest-layout>

    <x-slot name="titulo">Confirma que eres tú</x-slot>

    <div>
        <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-amber-500/15 text-amber-300">
            <x-omni-icon name="candado" size="h-6 w-6" />
        </span>

        <h1 class="mt-4 text-3xl font-black tracking-tight text-white">Confirma que eres tú</h1>

        <p class="mt-2 text-[14px] leading-relaxed text-slate-400">
            Lo que vas a hacer es delicado. Escribe tu contraseña para seguir.
        </p>
    </div>

    <form method="POST" action="{{ route('password.confirm') }}" class="mt-6 space-y-4">
        @csrf

        @include('auth.partials.clave', [
            'nombre' => 'password',
            'etiqueta' => 'Contraseña',
            'autocomplete' => 'current-password',
            'placeholder' => 'Tu contraseña',
            'autofocus' => true,
        ])

        <button type="submit"
            class="flex w-full items-center justify-center gap-2 rounded-xl bg-indigo-500 px-5 py-3.5 text-[14px] font-black text-white shadow-lg shadow-indigo-500/20 transition hover:-translate-y-0.5 hover:bg-indigo-400 focus:outline-none focus:ring-2 focus:ring-indigo-400 focus:ring-offset-2 focus:ring-offset-slate-900">
            Confirmar y seguir
        </button>
    </form>

</x-guest-layout>
