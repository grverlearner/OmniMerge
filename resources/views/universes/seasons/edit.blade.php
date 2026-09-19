@php
    /*
     * Editar una temporada.
     *
     * Comparte el formulario con «nueva». Aquí solo van la cabecera y lo que
     * solo existe al editar: el aviso de a cuántas competiciones afecta, y el
     * borrado.
     */

    $competiciones = $season->competitions()->count();
@endphp

<x-universe-layout :universe="$universe" surface="dark">

    <x-slot name="header">Editar {{ $season->name }}</x-slot>

    <div class="space-y-4">

        <header class="flex flex-wrap items-center gap-3">

            <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl border border-violet-500/40 bg-violet-500/10 font-mono text-lg font-black text-violet-300">
                {{ $season->number }}
            </span>

            <div class="min-w-0 flex-1">
                <a href="{{ route('universes.seasons.show', [$universe, $season]) }}"
                    class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-600 transition hover:text-violet-400">
                    ← {{ $season->name }}
                </a>

                <h1 class="mt-0.5 truncate text-xl font-black tracking-tight text-white">
                    Editar la temporada
                </h1>

                <p class="text-[10px] text-slate-600">{{ $season->period_label }}</p>
            </div>

            <a href="{{ route('universes.seasons.show', [$universe, $season]) }}"
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


        @if ($competiciones > 0)
            <div class="flex flex-wrap items-center gap-3 rounded-2xl border border-amber-500/25 bg-amber-500/5 px-4 py-2.5">
                <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-amber-500/15 text-amber-300">
                    <x-omni-icon name="trofeo" size="h-3.5 w-3.5" />
                </span>

                <p class="min-w-0 flex-1 text-[11px] leading-relaxed text-amber-200/80">
                    <strong class="text-amber-200">{{ $competiciones }}</strong>
                    {{ $competiciones === 1 ? 'competición se jugó' : 'competiciones se jugaron' }} en esta
                    temporada. Cambiarle el nombre les cambia lo que enseñan; cambiarle las fechas, no.
                </p>
            </div>
        @endif


        <form method="POST" action="{{ route('universes.seasons.update', [$universe, $season]) }}">
            @csrf
            @method('PUT')

            @include('universes.seasons.partials.season-form', [
                'siguienteNumero' => $season->number,
            ])
        </form>


        @can('update', $universe)
            <section class="rounded-2xl border border-rose-500/25 bg-rose-500/5 p-4">
                <div class="flex flex-wrap items-center gap-3">

                    <div class="min-w-0 flex-1">
                        <h2 class="text-[12px] font-black text-rose-200">Eliminar esta temporada</h2>

                        <p class="mt-0.5 text-[10px] leading-relaxed text-rose-200/60">
                            @if ($competiciones > 0)
                                Se jugaron {{ $competiciones }}
                                {{ $competiciones === 1 ? 'competición' : 'competiciones' }} dentro. Si solo
                                quieres quitarla de en medio,
                                <strong class="text-rose-200">archívala</strong>: eso no borra nada.
                            @else
                                No se jugó nada dentro, así que borrarla no rompe ninguna historia.
                            @endif
                        </p>
                    </div>

                    <form method="POST"
                        action="{{ route('universes.seasons.destroy', [$universe, $season]) }}"
                        data-omni-confirm data-confirm-variant="danger" data-confirm-title="Eliminar la temporada"
                        data-confirm-message="Se elimina de este universo." data-confirm-subject="{{ $season->name }}"
                        data-confirm-detail="No se puede deshacer." data-confirm-action="Sí, eliminarla">
                        @csrf
                        @method('DELETE')

                        <button type="submit"
                            class="rounded-xl border border-rose-500/40 px-4 py-2 text-[11px] font-black text-rose-300 transition hover:bg-rose-500 hover:text-white">
                            Eliminar
                        </button>
                    </form>
                </div>
            </section>
        @endcan

    </div>

</x-universe-layout>
