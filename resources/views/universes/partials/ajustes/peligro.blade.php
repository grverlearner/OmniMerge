{{--
    ZONA DE PELIGRO — archivar y borrar.

    Fuera del formulario de la configuración: cada acción tiene el suyo, y un
    formulario no puede ir dentro de otro.
--}}

<section id="peligro" data-seccion class="scroll-mt-24 space-y-2">

    <p class="flex items-center gap-1.5 px-1 text-[10px] font-black uppercase tracking-wider text-rose-400">
        <x-omni-icon name="aviso" size="h-3.5 w-3.5" />
        Zona de peligro
    </p>

    @can('update', $universe)
        @if ($universe->status !== 'ARCHIVED')
            <div class="flex flex-wrap items-center gap-3 rounded-2xl border border-slate-800 bg-slate-900/50 px-4 py-3">

                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-slate-800 text-slate-400">
                    <x-omni-icon name="capas" size="h-4 w-4" />
                </span>

                <div class="min-w-0 flex-1">
                    <p class="text-[12px] font-black text-slate-200">Archivar este mundo</p>
                    <p class="text-[10px] leading-4 text-slate-500">
                        Deja de pedir tu atención y se va al fondo de la estantería.
                        <strong class="text-slate-400">No se borra nada</strong>: sigue entero y puedes sacarlo cuando quieras.
                    </p>
                </div>

                <form method="POST" action="{{ route('universes.archive', $universe) }}" class="shrink-0">
                    @csrf
                    @method('PATCH')

                    <button type="submit"
                        class="rounded-xl border border-slate-700 px-3 py-2 text-[11px] font-black text-slate-400 transition hover:border-slate-500 hover:text-slate-200">
                        Archivar
                    </button>
                </form>
            </div>
        @endif
    @endcan

    @can('delete', $universe)
        @php
            $seVan = [
                [$cuentas['entities'], 'competidor', 'competidores'],
                [$cuentas['seasons'], 'temporada', 'temporadas'],
                [$cuentas['tournaments'], 'torneo', 'torneos'],
                [$cuentas['competitions'], 'competición', 'competiciones'],
            ];
        @endphp

        <div x-data="{ confirmando: false, escrito: '' }" class="overflow-hidden rounded-2xl border border-rose-500/30 bg-rose-500/5">

            <div class="flex flex-wrap items-center gap-3 px-4 py-3">
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-rose-500/15 text-rose-300">
                    <x-omni-icon name="cerrar" size="h-4 w-4" />
                </span>

                <div class="min-w-0 flex-1">
                    <p class="text-[12px] font-black text-white">Borrar este mundo</p>
                    <p class="text-[10px] leading-4 text-rose-200/70">
                        Se van con él
                        @foreach ($seVan as $indice => [$cuantos, $singular, $plural])
                            <strong class="text-rose-200">{{ $cuantos }}</strong> {{ $cuantos === 1 ? $singular : $plural }}@if ($indice === count($seVan) - 2) y @elseif ($indice < count($seVan) - 1), @endif
                        @endforeach
                        con toda su historia. No se puede deshacer.
                    </p>
                </div>

                <button type="button" @click="confirmando = true" x-show="! confirmando"
                    class="shrink-0 rounded-xl border border-rose-500/40 px-3 py-2 text-[11px] font-black text-rose-300 transition hover:bg-rose-500 hover:text-white">
                    Borrar
                </button>
            </div>

            <div x-show="confirmando" x-cloak x-collapse class="border-t border-rose-500/20 bg-rose-500/5 px-4 py-3">
                <p class="text-[11px] font-bold text-rose-100">
                    Para confirmarlo, escribe el nombre del universo: <span class="font-mono text-white">{{ $universe->name }}</span>
                </p>

                <input type="text" x-model="escrito" autocomplete="off"
                    class="mt-2 w-full max-w-sm rounded-xl border-rose-500/40 bg-slate-950 text-[12px] text-white focus:border-rose-400 focus:ring-rose-400">

                <div class="mt-2 flex flex-wrap gap-2">
                    <form method="POST" action="{{ route('universes.destroy', $universe) }}">
                        @csrf
                        @method('DELETE')

                        <button type="submit" :disabled="escrito.trim() !== @js($universe->name)"
                            class="rounded-xl bg-rose-500 px-4 py-2 text-[11px] font-black text-white transition hover:bg-rose-400 disabled:cursor-not-allowed disabled:opacity-40">
                            Sí, borrarlo para siempre
                        </button>
                    </form>

                    <button type="button" @click="confirmando = false; escrito = ''"
                        class="rounded-xl px-3 py-2 text-[11px] font-black text-slate-400 transition hover:text-slate-200">
                        No, dejarlo
                    </button>
                </div>
            </div>
        </div>
    @endcan
</section>
