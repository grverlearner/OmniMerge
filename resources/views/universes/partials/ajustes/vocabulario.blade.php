{{--
    VOCABULARIO — cómo se llaman aquí las cosas.

    En un mundo ninja los competidores son «Ninjas» y las temporadas
    «Arcos»; en uno de fútbol, «Selecciones» y «Mundiales». Estos nombres
    salen en el sidebar, en las cifras del Resumen y en la temporada en curso.
--}}

@php
    $palabras = [
        'label_entities' => ['Cómo se llaman sus habitantes', 'En el menú y en las cifras del Resumen', ['Personajes', 'Ninjas', 'Luchadores', 'Selecciones', 'Países', 'Equipos']],
        'label_season' => ['Una temporada', 'Junto a su número: «Temporada 3», «Arco 3»…', ['Temporada', 'Arco', 'Saga', 'Año', 'Era', 'Edición']],
        'label_seasons' => ['Varias temporadas', 'En el menú y en las cifras', ['Temporadas', 'Arcos', 'Sagas', 'Años', 'Eras']],
        'label_tournaments' => ['Sus torneos', 'En el menú y en las cifras', ['Torneos', 'Copas', 'Ligas', 'Campeonatos', 'Mundiales']],
        'label_competitions' => ['Cada edición jugada', 'En el menú y en las cifras', ['Competiciones', 'Ediciones', 'Combates', 'Partidas', 'Duelos']],
    ];
@endphp

<section id="vocabulario" data-seccion class="scroll-mt-24 overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/50">

    <header class="flex flex-wrap items-center gap-2 border-b border-slate-800 px-4 py-3"
        style="background: linear-gradient(120deg, #38bdf81a, transparent 60%)">
        <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-sky-500/15 text-sky-300">
            <x-omni-icon name="libro" size="h-4 w-4" />
        </span>
        <div class="min-w-0 flex-1">
            <h2 class="text-[14px] font-black text-white">Vocabulario</h2>
            <p class="text-[10px] text-slate-500">Los nombres de este mundo. Se nota en el sidebar y en el Resumen.</p>
        </div>
        <button type="button" @click="restablecer(@js(array_keys($palabras)))"
            class="flex items-center gap-1 rounded-lg border border-slate-800 px-2 py-1 text-[10px] font-black text-slate-500 transition hover:border-slate-600 hover:text-white">
            <x-omni-icon name="deshacer" size="h-3 w-3" />
            Restablecer
        </button>
    </header>

    <div class="grid gap-3 p-4 md:grid-cols-2">
        @foreach ($palabras as $clave => [$titulo, $ayuda, $ideas])
            <div class="rounded-xl border border-slate-800 bg-slate-950/60 p-3">
                <label class="block">
                    <span class="block text-[12px] font-black text-slate-200">{{ $titulo }}</span>
                    <span class="block text-[10px] text-slate-600">{{ $ayuda }}</span>
                    <input type="text" name="settings[{{ $clave }}]" x-model="s.{{ $clave }}" maxlength="30" @keydown.enter.prevent
                        placeholder="{{ \App\Support\Universes\UniverseSettings::LABELS[$clave] }}"
                        class="mt-1.5 w-full rounded-lg border-slate-700 bg-slate-900 text-[13px] font-black text-white placeholder:text-slate-600 focus:border-sky-500 focus:ring-sky-500">
                </label>

                <div class="mt-1.5 flex flex-wrap gap-1">
                    @foreach ($ideas as $idea)
                        <button type="button" @click="s.{{ $clave }} = @js($idea)"
                            class="rounded-full border px-2 py-0.5 text-[10px] font-bold transition"
                            :class="s.{{ $clave }} === @js($idea) ? 'border-sky-400 bg-sky-500/15 text-sky-200' : 'border-slate-800 text-slate-500 hover:border-slate-600 hover:text-slate-300'">{{ $idea }}</button>
                    @endforeach
                </div>
            </div>
        @endforeach

        <p class="rounded-xl border border-dashed border-slate-800 px-3 py-2 text-[10px] leading-4 text-slate-500 md:col-span-2">
            Así se leería: «<span class="font-black text-slate-300" x-text="s.label_season || 'Temporada'"></span> 3 en curso ·
            <span class="font-black text-slate-300" x-text="cuentas.entities + ' ' + (s.label_entities || 'Entidades').toLowerCase()"></span> ·
            <span class="font-black text-slate-300" x-text="cuentas.tournaments + ' ' + (s.label_tournaments || 'Torneos').toLowerCase()"></span>».
        </p>
    </div>
</section>
