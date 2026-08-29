@php
    /*
     * MODO TABLA — columnas comparables.
     *
     * Es la vista para responder «quién gana más», no «quién es este». Por
     * eso no lleva imágenes grandes ni atributos desplegados: lleva cifras
     * alineadas, que es lo único que se puede comparar de un vistazo.
     *
     * Va aparte de las demás vistas porque una tabla es un <table>, no una
     * rejilla de fichas, y meterla en el mismo x-for obligaría a que cada
     * ficha supiera si es una fila.
     */
@endphp

<div x-show="view === 'TABLE'" x-cloak
    class="overflow-x-auto rounded-2xl border border-slate-800 bg-slate-900/50">

    <table class="w-full min-w-[52rem] text-left">

        <thead class="border-b border-slate-800 bg-slate-950/60">
            <tr>
                <th class="px-3 py-2 text-[9px] font-black uppercase tracking-wider text-slate-500">Competidor</th>
                <th class="px-2 py-2 text-[9px] font-black uppercase tracking-wider text-slate-500">Atributos</th>
                <th class="px-2 py-2 text-center text-[9px] font-black uppercase tracking-wider text-slate-500">Torneos</th>
                <th class="px-2 py-2 text-center text-[9px] font-black uppercase tracking-wider text-slate-500">V–D</th>
                <th class="px-2 py-2 text-center text-[9px] font-black uppercase tracking-wider text-slate-500">%</th>
                <th class="px-2 py-2 text-center text-[9px] font-black uppercase tracking-wider text-amber-400">Títulos</th>
                <th class="px-2 py-2 text-center text-[9px] font-black uppercase tracking-wider text-violet-400">Trofeos</th>
                <th class="px-2 py-2 text-center text-[9px] font-black uppercase tracking-wider text-slate-500">Ver.</th>
            </tr>
        </thead>

        <tbody>
            <template x-for="e in shown" :key="'t' + e.id">
                <tr class="border-b border-slate-800/60 transition hover:bg-slate-800/40">

                    <td class="px-3 py-1.5">
                        <a :href="'{{ route('universes.entities.index', $universe) }}/' + e.id"
                            class="flex items-center gap-2">

                            <span class="flex h-8 w-8 shrink-0 items-center justify-center overflow-hidden rounded-lg bg-slate-950">
                                <template x-if="e.image_url">
                                    <img :src="e.image_url" alt="" loading="lazy" class="h-full w-full object-cover">
                                </template>
                                <template x-if="!e.image_url">
                                    <span class="font-mono text-[9px] font-black text-slate-700"
                                        x-text="e.name.slice(0, 2).toUpperCase()"></span>
                                </template>
                            </span>

                            <span class="min-w-0">
                                <span class="block truncate text-[11px] font-black text-slate-100" x-text="e.name"></span>
                                <span class="block truncate font-mono text-[8px] text-slate-600"
                                    x-text="e.code + (e.type ? ' · ' + e.type : '')"></span>
                            </span>
                        </a>
                    </td>

                    <td class="px-2 py-1.5">
                        <span class="flex flex-wrap gap-0.5">
                            <template x-for="chip in chipsOf(e).slice(0, 3)" :key="'tc' + e.id + chip.key">
                                <span class="rounded bg-slate-950 px-1 py-0.5 text-[8px] text-slate-400"
                                    :title="chip.attribute + ': ' + chip.value"
                                    x-text="chip.value"></span>
                            </template>

                            <span class="text-[8px] text-slate-700" x-show="chipsOf(e).length > 3"
                                x-text="'+' + (chipsOf(e).length - 3)"></span>

                            <span class="text-[8px] text-slate-700" x-show="chipsOf(e).length === 0">—</span>
                        </span>
                    </td>

                    <td class="px-2 py-1.5 text-center font-mono text-[11px] text-slate-300" x-text="e.record.tournaments"></td>

                    <td class="px-2 py-1.5 text-center font-mono text-[11px]">
                        <span class="text-emerald-300" x-text="e.record.wins"></span>
                        <span class="text-slate-700">–</span>
                        <span class="text-rose-300" x-text="e.record.losses"></span>
                    </td>

                    {{--
                        Sin partidos no hay porcentaje. Un guion dice «no
                        hay dato»; un 0% diría «pierde siempre», que es
                        mentira.
                    --}}
                    <td class="px-2 py-1.5 text-center font-mono text-[11px]"
                        :class="e.record.winrate === null ? 'text-slate-700'
                            : (e.record.winrate >= 50 ? 'text-emerald-300' : 'text-slate-400')"
                        x-text="e.record.winrate === null ? '—' : e.record.winrate + '%'"></td>

                    <td class="px-2 py-1.5 text-center font-mono text-[11px]"
                        :class="e.record.titles ? 'text-amber-300 font-black' : 'text-slate-700'"
                        x-text="e.record.titles || '—'"></td>

                    <td class="px-2 py-1.5 text-center">
                        <span class="flex items-center justify-center gap-0.5">
                            <template x-for="tr in e.trophies.slice(0, 3)" :key="'tt' + e.id + tr.id">
                                <span class="flex h-5 w-5 items-center justify-center overflow-hidden rounded bg-slate-950 text-[9px]"
                                    :title="tr.name">
                                    <template x-if="tr.image_url">
                                        <img :src="tr.image_url" alt="" class="h-full w-full object-cover">
                                    </template>
                                    <template x-if="!tr.image_url">
                                        <span x-text="tr.icon || '🏆'"></span>
                                    </template>
                                </span>
                            </template>

                            <span class="text-[8px] text-slate-700" x-show="e.trophies.length === 0">—</span>
                            <span class="font-mono text-[8px] text-slate-600" x-show="e.trophies.length > 3"
                                x-text="'+' + (e.trophies.length - 3)"></span>
                        </span>
                    </td>

                    <td class="px-2 py-1.5 text-center font-mono text-[11px]"
                        :class="e.versions.length > 1 ? 'text-violet-300' : 'text-slate-700'"
                        x-text="e.versions.length || '—'"></td>
                </tr>
            </template>
        </tbody>
    </table>
</div>
