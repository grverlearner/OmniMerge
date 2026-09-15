@php
    /*
     * La tabla: todas las cifras a la vez.
     *
     * Aqui no se esconde nada. Cuando lo que se quiere es comparar numeros
     * exactos -cual tiene mas torneos, cual mas trofeos- una rejilla de
     * tarjetas obliga a ir y volver; una tabla no.
     *
     * El orden lo sigue poniendo el selector de arriba, para que lo que se ve
     * aqui y lo que se ve en galeria sea lo mismo en el mismo orden.
     */
@endphp

<section x-show="vista === 'tabla'" x-cloak
    class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/50">

    <div class="overflow-x-auto">
        <table class="w-full min-w-[900px]">

            <thead class="border-b border-slate-800 text-left">
                <tr class="text-[9px] font-black uppercase tracking-wider text-slate-600">
                    <th class="px-4 py-2.5">Mundo</th>
                    <th class="px-3 py-2.5">Estado</th>
                    <th class="px-3 py-2.5">Temporada</th>
                    <th class="px-3 py-2.5 text-right">Gente</th>
                    <th class="px-3 py-2.5 text-right">Temporadas</th>
                    <th class="px-3 py-2.5 text-right">Torneos</th>
                    <th class="px-3 py-2.5 text-right">Jugadas</th>
                    <th class="px-3 py-2.5 text-right">En juego</th>
                    <th class="px-3 py-2.5 text-right">Atascadas</th>
                    <th class="px-3 py-2.5 text-right">Trofeos</th>
                    <th class="px-3 py-2.5 text-right">Se movió</th>
                </tr>
            </thead>

            <tbody class="divide-y divide-slate-800/70">
                @foreach ($universes as $mundo)
                    @php
                        [$tono, $textoEstado] = $tonosEstado[$mundo->status] ?? ['#94a3b8', $mundo->status];
                        $temporada = $temporadaPorUniverso[$mundo->id] ?? null;
                    @endphp

                    <tr class="transition hover:bg-slate-950/50">

                        <td class="px-4 py-2">
                            <a href="{{ route('universes.show', $mundo) }}" class="flex items-center gap-2">
                                <span class="h-8 w-8 shrink-0 overflow-hidden rounded-lg border bg-slate-950"
                                    style="border-color: {{ $tono }}55">
                                    @if ($mundo->image_url)
                                        <img src="{{ $mundo->image_url }}" alt="" loading="lazy" class="h-full w-full object-cover">
                                    @else
                                        <span class="flex h-full w-full items-center justify-center text-slate-700">
                                            <x-omni-icon name="globo" size="h-3.5 w-3.5" />
                                        </span>
                                    @endif
                                </span>

                                <span class="min-w-0">
                                    <span class="block truncate text-[12px] font-black text-white">{{ $mundo->name }}</span>
                                    <span class="block font-mono text-[9px] text-slate-600">{{ $mundo->code }}</span>
                                </span>
                            </a>
                        </td>

                        <td class="px-3 py-2">
                            <span class="rounded px-1.5 py-0.5 text-[9px] font-black uppercase tracking-wider"
                                style="color: {{ $tono }}; background-color: {{ $tono }}1f">{{ $textoEstado }}</span>
                        </td>

                        <td class="px-3 py-2">
                            @if ($temporada)
                                <span class="font-mono text-[10px] text-violet-300">
                                    T{{ $temporada->number }} · {{ $temporada->name }}
                                </span>
                            @elseif ($mundo->seasons_count > 0)
                                <span class="text-[10px] text-amber-500">ninguna en marcha</span>
                            @else
                                <span class="text-[10px] text-slate-700">sin temporadas</span>
                            @endif
                        </td>

                        @foreach ([[$mundo->entities_count, '#a78bfa'], [$mundo->seasons_count, '#60a5fa'], [$mundo->universe_tournaments_count, '#22d3ee'], [$mundo->tournament_instances_count, '#34d399'], [$mundo->vivas_count, '#34d399'], [$mundo->atascadas_count, '#fb7185'], [$mundo->trophies_count, '#fbbf24']] as [$valor, $tonoC])
                            <td class="px-3 py-2 text-right font-mono text-[11px] font-black"
                                style="color: {{ $valor > 0 ? $tonoC : '#475569' }}">{{ $valor }}</td>
                        @endforeach

                        <td class="px-3 py-2 text-right font-mono text-[10px] text-slate-500">
                            @if ($mundo->activities_max_occurred_at)
                                {{ \Illuminate\Support\Carbon::parse($mundo->activities_max_occurred_at)->diffForHumans(null, true) }}
                            @else
                                <span class="text-slate-700">nunca</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</section>
