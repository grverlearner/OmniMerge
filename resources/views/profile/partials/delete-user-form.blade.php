@php
    /*
     * Borrar la cuenta.
     *
     * Se dice exactamente que se pierde, con sus numeros. «Esta accion no se
     * puede deshacer» no informa de nada; «se van 22 entidades, 12 torneos y 3
     * mundos con toda su historia» si.
     */

    $seVan = [
        [$usuario->entities()->count(), 'entidad', 'entidades'],
        [$usuario->collections()->count(), 'colección', 'colecciones'],
        [$usuario->attributes()->count(), 'atributo', 'atributos'],
        [$usuario->tournamentTemplates()->count(), 'torneo', 'torneos'],
        [$usuario->phaseTemplates()->count(), 'fase', 'fases'],
        [$usuario->universes()->count(), 'universo', 'universos'],
    ];

    $seVan = array_values(array_filter($seVan, fn($f) => $f[0] > 0));
@endphp

<section x-data="{ confirmando: {{ $errors->userDeletion->isNotEmpty() ? 'true' : 'false' }} }"
    class="overflow-hidden rounded-2xl border border-rose-500/25 bg-rose-500/5">

    <div class="flex flex-wrap items-center gap-3 px-4 py-3">

        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-rose-500/15 text-rose-300">
            <x-omni-icon name="cerrar" size="h-4 w-4" />
        </span>

        <div class="min-w-0 flex-1">
            <p class="text-[12px] font-black text-white">Borrar mi cuenta</p>

            <p class="text-[10px] leading-3 text-rose-200/70">
                @if ($seVan === [])
                    Tu cuenta está vacía, así que no se pierde nada más que ella. No se puede
                    deshacer.
                @else
                    Se van con ella
                    @foreach ($seVan as $indice => [$cuantos, $singular, $plural])
                        <strong class="text-rose-200">{{ $cuantos }}</strong>
                        {{ $cuantos === 1 ? $singular : $plural }}@if ($indice === count($seVan) - 2)
                            y
                        @elseif ($indice < count($seVan) - 1),
                        @endif
                    @endforeach
                    con toda su historia. No se puede deshacer.
                @endif
            </p>
        </div>

        <button type="button" @click="confirmando = true" x-show="! confirmando"
            class="shrink-0 rounded-xl border border-rose-500/40 px-3 py-2 text-[11px] font-black text-rose-300 transition hover:bg-rose-500 hover:text-white">
            Borrar
        </button>
    </div>


    <div x-show="confirmando" x-cloak x-collapse
        class="border-t border-rose-500/20 bg-rose-500/5 px-4 py-3">

        <form method="POST" action="{{ route('profile.destroy') }}">

            @csrf
            @method('DELETE')

            <p class="text-[11px] font-bold text-rose-100">
                Escribe tu contraseña para confirmar que quieres borrarlo todo.
            </p>

            <div class="mt-2 flex flex-wrap items-start gap-2">

                <label class="min-w-[200px] flex-1">
                    <span class="sr-only">Contraseña</span>
                    <input type="password" name="password" placeholder="Tu contraseña"
                        class="w-full rounded-xl border-rose-500/30 bg-slate-950 text-[12px] text-slate-200 placeholder:text-slate-600 focus:border-rose-500 focus:ring-rose-500">
                    <x-input-error :messages="$errors->userDeletion->get('password')" class="mt-1" />
                </label>

                <button type="submit"
                    class="rounded-xl bg-rose-500 px-4 py-2.5 text-[11px] font-black text-white transition hover:bg-rose-400">
                    Sí, borrar mi cuenta
                </button>

                <button type="button" @click="confirmando = false"
                    class="rounded-xl px-3 py-2.5 text-[11px] font-black text-slate-400 transition hover:text-slate-200">
                    No, dejarlo
                </button>
            </div>
        </form>
    </div>
</section>
