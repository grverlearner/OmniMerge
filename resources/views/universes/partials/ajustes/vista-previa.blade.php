{{--
    CÓMO QUEDA — la configuración aplicada, antes de guardarla.

    Tres sitios donde se nota: el sidebar del universo, su tarjeta en
    «Mis universos» y la clasificación con su sistema de puntos.
--}}

<p class="px-1 text-[10px] font-black uppercase tracking-wider text-slate-500">Cómo queda</p>

{{-- ============ EL SIDEBAR ============ --}}

<div class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-950">
    <span class="block h-0.5" :style="`background: linear-gradient(90deg, transparent, ${s.accent}, transparent)`"></span>

    <div class="p-3">
        <p class="mb-2 text-[9px] font-black uppercase tracking-wider text-slate-600">Sidebar</p>

        <div class="flex items-center gap-2.5">
            <span class="flex h-10 w-10 shrink-0 items-center justify-center overflow-hidden rounded-xl text-white"
                :style="`background-image: linear-gradient(135deg, ${s.accent}, ${s.accent}88)`">
                <template x-if="portada"><img :src="portada" alt="" class="h-full w-full object-cover" :style="`object-position: ${s.cover_position}`"></template>
                <template x-if="! portada">
                    <span>
                        @foreach (\App\Support\Universes\UniverseSettings::ICONS as $icono)
                            <span x-show="s.icon === '{{ $icono }}'"><x-omni-icon :name="$icono" size="h-5 w-5" /></span>
                        @endforeach
                    </span>
                </template>
            </span>
            <span class="min-w-0">
                <span class="block truncate text-[14px] font-black text-white" x-text="nombre || 'Sin nombre'"></span>
                <span class="block truncate text-[9px] font-black uppercase tracking-wider text-violet-400">
                    <span x-text="codigo"></span>
                    <span x-show="temporada" class="font-normal normal-case text-slate-500" x-text="' · ' + (s.label_season || 'Temporada') + ' ' + temporada"></span>
                </span>
            </span>
        </div>

        <div class="mt-3 space-y-0.5">
            <span class="flex items-center gap-2 rounded-lg bg-violet-500 px-2 py-1.5 text-[11px] font-black text-white">
                <x-omni-icon name="cuadricula" size="h-3.5 w-3.5" /> Resumen
                <span x-show="s.home === 'show'" class="ml-auto rounded bg-white/20 px-1 text-[8px] uppercase">inicio</span>
            </span>

            @foreach (\App\Support\Universes\UniverseSettings::NAV as $clave => [$ruta, $texto, $icono])
                <span x-show="navVisible('{{ $clave }}')" class="flex items-center gap-2 rounded-lg px-2 py-1.5 text-[11px] font-bold text-slate-400">
                    <x-omni-icon :name="$icono" size="h-3.5 w-3.5" />
                    <span class="truncate" x-text="navLabel('{{ $clave }}')"></span>
                    <span x-show="s.home === '{{ $clave }}' && homeValido" class="ml-auto rounded px-1 text-[8px] font-black uppercase text-slate-950" :style="`background-color: ${s.accent}`">inicio</span>
                </span>
            @endforeach

            <span class="flex items-center gap-2 rounded-lg px-2 py-1.5 text-[11px] font-bold text-slate-500">
                <x-omni-icon name="engranaje" size="h-3.5 w-3.5" /> Configuración
            </span>
        </div>
    </div>
</div>


{{-- ============ LA TARJETA ============ --}}

<div class="relative overflow-hidden rounded-2xl border bg-slate-900/60" :style="`border-color: ${s.accent}55`">
    <span class="absolute inset-x-0 top-0 z-10 h-1" :style="`background-color: ${s.accent}`"></span>

    <div class="relative h-24 overflow-hidden bg-slate-950">
        <span class="absolute inset-0" :style="`background: linear-gradient(120deg, #020617 18%, ${s.accent}33 65%, #02061788 100%)`"></span>
        <span class="absolute left-3 top-3 flex h-16 w-16 items-center justify-center overflow-hidden rounded-xl border-2 bg-slate-950"
            :style="`border-color: ${s.accent}; color: ${s.accent}`">
            <template x-if="portada"><img :src="portada" alt="" class="h-full w-full object-cover" :style="`object-position: ${s.cover_position}`"></template>
            <template x-if="! portada">
                <span>
                    @foreach (\App\Support\Universes\UniverseSettings::ICONS as $icono)
                        <span x-show="s.icon === '{{ $icono }}'"><x-omni-icon :name="$icono" size="h-7 w-7" /></span>
                    @endforeach
                </span>
            </template>
        </span>
        <span class="absolute bottom-2 right-2 rounded-md bg-slate-950/70 px-1.5 py-0.5 text-[9px] font-black text-slate-400">Mis universos</span>
    </div>

    <div class="p-3">
        <p class="truncate text-[14px] font-black text-white" x-text="nombre || 'Sin nombre'"></p>
        <p x-show="s.tagline" class="truncate text-[11px] font-black" :style="`color: ${s.accent}`" x-text="s.tagline"></p>
        <div class="mt-2 grid grid-cols-3 gap-1">
            <span class="rounded-lg border border-slate-800 bg-slate-950 py-1 text-center">
                <span class="block font-mono text-[12px] font-black text-violet-300" x-text="cuentas.entities"></span>
                <span class="block truncate px-1 text-[8px] font-black uppercase text-slate-600" x-text="s.label_entities || 'Entidades'"></span>
            </span>
            <span class="rounded-lg border border-slate-800 bg-slate-950 py-1 text-center">
                <span class="block font-mono text-[12px] font-black text-cyan-300" x-text="cuentas.tournaments"></span>
                <span class="block truncate px-1 text-[8px] font-black uppercase text-slate-600" x-text="s.label_tournaments || 'Torneos'"></span>
            </span>
            <span class="rounded-lg border border-slate-800 bg-slate-950 py-1 text-center">
                <span class="block font-mono text-[12px] font-black text-emerald-300" x-text="cuentas.competitions"></span>
                <span class="block truncate px-1 text-[8px] font-black uppercase text-slate-600" x-text="s.label_competitions || 'Competiciones'"></span>
            </span>
        </div>
    </div>
</div>


{{-- ============ LOS PUNTOS ============ --}}

<div class="rounded-2xl border border-slate-800 bg-slate-950 p-3">
    <p class="mb-2 text-[9px] font-black uppercase tracking-wider text-slate-600">Clasificación</p>

    <div class="space-y-1 font-mono text-[11px]">
        @foreach ([['points_champion', 'Título', '#fbbf24'], ['points_win', 'Victoria', '#34d399'], ['points_draw', 'Empate', '#38bdf8'], ['points_loss', 'Derrota', '#f87171'], ['points_participation', 'Participar', '#a78bfa']] as [$clave, $texto, $tono])
            <div class="flex items-center gap-2">
                <span class="w-16 text-slate-500">{{ $texto }}</span>
                <span class="h-1.5 flex-1 overflow-hidden rounded-full bg-slate-800">
                    <span class="block h-full rounded-full" style="background-color: {{ $tono }}"
                        :style="`width: ${Math.min(100, (Number(s.{{ $clave }}) || 0) * 8)}%; background-color: {{ $tono }}`"></span>
                </span>
                <span class="w-8 text-right font-black text-slate-200" x-text="s.{{ $clave }}"></span>
            </div>
        @endforeach
    </div>

    <p class="mt-2 text-[10px] leading-4 text-slate-500">
        <span x-text="s.ranking_min_competitions > 0 ? 'Aparece quien jugó al menos ' + s.ranking_min_competitions + '.' : 'Aparece todo el que jugó.'"></span>
        <span x-show="s.ranking_tiebreaks.length" x-text="' Empates: ' + s.ranking_tiebreaks.map((d) => desempates[d].toLowerCase()).join(', luego ') + '.'"></span>
    </p>
</div>
