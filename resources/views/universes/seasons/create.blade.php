@php
    /*
     * Nueva temporada.
     *
     * La cáscara es fina: el trabajo vive en el formulario compartido con la
     * edición. Aquí van la cabecera y el recordatorio de que, si hacen falta
     * varias, existe el creador en lote.
     */
@endphp

<x-universe-layout :universe="$universe" surface="dark">

    <x-slot name="header">Nueva temporada</x-slot>

    <div class="space-y-4">

        <header class="flex flex-wrap items-end gap-4">

            <div class="min-w-0 flex-1">
                <a href="{{ route('universes.seasons.index', $universe) }}"
                    class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-600 transition hover:text-violet-400">
                    ← Temporadas
                </a>

                <h1 class="mt-1 text-xl font-black tracking-tight text-white">
                    Temporada {{ $nextNumber }}
                </h1>

                <p class="mt-0.5 text-[11px] text-slate-500">
                    Un tramo más del tiempo de {{ $universe->name }}. El número ya está decidido: es
                    correlativo, y es lo que hace que la recurrencia de los torneos cuadre.
                </p>
            </div>

            <a href="{{ route('universes.seasons.index', $universe) }}"
                class="rounded-xl border border-slate-800 bg-slate-900/50 px-3 py-2 text-[11px] font-black text-slate-400 transition hover:border-slate-700 hover:text-white">
                Cancelar
            </a>
        </header>


        @if ($errors->any())
            <div class="rounded-2xl border border-rose-500/30 bg-rose-500/10 px-4 py-3">
                <p class="text-[12px] font-black text-rose-200">Falta algo antes de poder guardar:</p>
                <ul class="mt-1 space-y-0.5 text-[11px] leading-relaxed text-rose-200/80">
                    @foreach ($errors->all() as $error)
                        <li>· {{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif


        <div class="flex flex-wrap items-center gap-3 rounded-2xl border border-violet-500/25 bg-violet-500/5 px-4 py-2.5">
            <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-violet-500/15 text-violet-300">
                <x-omni-icon name="calendario" size="h-3.5 w-3.5" />
            </span>

            <p class="min-w-0 flex-1 text-[11px] leading-relaxed text-slate-400">
                ¿Necesitas varias? En el panel de temporadas hay un
                <strong class="text-cyan-300">creador en lote</strong> que monta hasta cincuenta de golpe,
                numeradas y con sus fechas encadenadas.
            </p>

            <a href="{{ route('universes.seasons.index', $universe) }}"
                class="shrink-0 rounded-xl border border-cyan-500/40 px-2.5 py-1.5 text-[10px] font-black text-cyan-300 transition hover:bg-cyan-500 hover:text-white">
                Crear varias →
            </a>
        </div>


        <form method="POST" action="{{ route('universes.seasons.store', $universe) }}">
            @csrf

            @include('universes.seasons.partials.season-form', [
                'siguienteNumero' => $nextNumber,
            ])
        </form>

    </div>

</x-universe-layout>
