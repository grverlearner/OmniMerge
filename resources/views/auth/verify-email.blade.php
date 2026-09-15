@php
    /*
     * Confirmar el correo.
     *
     * Era la vista de Breeze en inglés. Hoy User no implementa MustVerifyEmail,
     * así que nadie está obligado a pasar por aquí; la pantalla se conserva
     * porque la ruta existe, y se hace coherente con el resto por si se activa.
     */

    $correoDeVerdad = ! in_array(config('mail.default'), ['log', 'array'], true);
@endphp

<x-guest-layout>

    <x-slot name="titulo">Confirma tu correo</x-slot>

    <div>
        <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-indigo-500/15 text-indigo-300">
            <x-omni-icon name="correo" size="h-6 w-6" />
        </span>

        <h1 class="mt-4 text-3xl font-black tracking-tight text-white">Confirma tu correo</h1>

        <p class="mt-2 text-[14px] leading-relaxed text-slate-400">
            Te hemos enviado un enlace a
            <strong class="text-slate-200">{{ auth()->user()?->email }}</strong>.
            Ábrelo para confirmar que el correo es tuyo.
        </p>
    </div>

    @unless ($correoDeVerdad)
        <div class="mt-5 rounded-xl border border-amber-500/30 bg-amber-500/10 px-3.5 py-2.5">
            <p class="text-[12px] font-black text-amber-200">En esta instalación el correo no sale</p>
            <p class="mt-0.5 text-[11px] leading-4 text-amber-200/70">
                El enlace se escribe en el registro del servidor
                (<code class="font-mono">storage/logs</code>) en vez de enviarse.
            </p>
        </div>
    @endunless

    @if (session('status') === 'verification-link-sent')
        <x-auth-session-status class="mt-5" status="Te hemos mandado un enlace nuevo." />
    @endif

    <div class="mt-6 space-y-3">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <button type="submit"
                class="flex w-full items-center justify-center gap-2 rounded-xl bg-indigo-500 px-5 py-3.5 text-[14px] font-black text-white shadow-lg shadow-indigo-500/20 transition hover:-translate-y-0.5 hover:bg-indigo-400">
                Mandarme otro enlace
            </button>
        </form>

        <form method="POST" action="{{ route('logout') }}" class="text-center">
            @csrf
            <button type="submit" class="text-[13px] font-bold text-slate-500 transition hover:text-slate-200">
                Salir y entrar con otra cuenta
            </button>
        </form>
    </div>

</x-guest-layout>
