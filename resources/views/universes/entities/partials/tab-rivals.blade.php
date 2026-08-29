@php
    /*
     * RIVALES — contra quién ha jugado y cómo le fue.
     *
     * Con cara: un rival se reconoce por su imagen antes que por su nombre,
     * y esta lista existe para reconocerlos.
     *
     * La barra reparte ganados, empatados y perdidos, porque un 8–4 y un
     * 4–8 se leen igual en texto y al revés en color.
     *
     * Los datos de cada rival —imagen, atributos, récord— no vienen del
     * servicio de estadísticas, que solo devuelve nombre y cifras: se cruzan
     * con las fichas del panel, que es el mismo sitio del que salen en el
     * resto de la aplicación.
     */

    $fichas = collect(
        app(\App\Services\Universes\UniverseEntityBrowser::class)
            ->browse($universe, request())['entities']
    )->keyBy('id');

    $lista = collect($rivals)
        ->map(function ($r) use ($fichas) {

            $ficha = $fichas->get($r['entity_id']);

            $ganados = (int) ($r['wins'] ?? 0);
            $empatados = (int) ($r['draws'] ?? 0);
            $perdidos = (int) ($r['losses'] ?? 0);
            $jugados = $ganados + $empatados + $perdidos;

            return [
                'id' => $r['entity_id'],
                'name' => $ficha['name'] ?? $r['name'],
                'image_url' => $ficha['image_url'] ?? null,
                'type' => $ficha['type'] ?? null,
                'attributes' => $ficha['attributes'] ?? [],
                'record' => $ficha['record'] ?? null,
                'matches' => (int) ($r['matches'] ?? 0),
                'wins' => $ganados,
                'draws' => $empatados,
                'losses' => $perdidos,
                'played' => $jugados,
                'winrate' => $jugados > 0 ? (int) round($ganados / $jugados * 100) : null,
            ];
        })
        ->values()
        ->all();
@endphp

<div x-show="tab === 'rivals'" x-cloak
    x-data="{
        search: '',
        sort: 'matches',
        view: 'LIST',

        get shown() {
            let lista = @js($lista);

            const q = this.search.trim().toLowerCase();

            if (q) {
                lista = lista.filter((r) =>
                    r.name.toLowerCase().includes(q)
                    || (r.type ?? '').toLowerCase().includes(q)
                    || (r.attributes ?? []).some((a) =>
                        a.key.includes(q) || a.keys.some((v) => v.includes(q))
                    )
                );
            }

            const porNombre = (a, b) => a.name.localeCompare(b.name, 'es');

            const desc = (leer) => (a, b) => {
                const d = leer(b) - leer(a);
                return d !== 0 ? d : porNombre(a, b);
            };

            return [...lista].sort({
                matches: desc((r) => r.matches),
                name: porNombre,
                wins: desc((r) => r.wins),
                losses: desc((r) => r.losses),

                /* Sin partidos no hay porcentaje: al final, no como un 0% */
                winrate: desc((r) => r.winrate ?? -1),
            }[this.sort] ?? porNombre);
        },
    }">

    <section class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/50">

        <div class="flex flex-wrap items-center gap-2 border-b border-slate-800 bg-rose-500/10 px-4 py-2">
            <span class="text-[11px]">⚔</span>
            <h2 class="text-[11px] font-black uppercase tracking-wider text-rose-300">Rivales</h2>
            <span class="font-mono text-[10px] text-slate-600" x-text="shown.length"></span>

            @if (count($lista))
                <input type="search" x-model="search" placeholder="buscar rival…"
                    class="ml-auto w-40 rounded-lg border-slate-700 bg-slate-950 px-2 py-1 text-[10px] text-slate-200 placeholder:text-slate-700 focus:border-rose-500 focus:ring-rose-500">

                <select x-model="sort"
                    class="rounded-lg border-slate-700 bg-slate-950 px-2 py-1 text-[10px] text-slate-300 focus:border-rose-500 focus:ring-rose-500">
                    <option value="matches">Más veces</option>
                    <option value="name">Nombre</option>
                    <option value="wins">Más le ganó</option>
                    <option value="losses">Más le perdió</option>
                    <option value="winrate">Mejor porcentaje</option>
                </select>

                <div class="flex rounded-lg border border-slate-800 bg-slate-950 p-0.5">
                    @foreach (['LIST' => '☰', 'GALLERY' => '▤'] as $modo => $icono)
                        <button type="button" @click="view = '{{ $modo }}'"
                            class="rounded-md px-1.5 py-0.5 text-[10px] transition"
                            :class="view === '{{ $modo }}'
                                ? 'bg-rose-500 text-slate-950'
                                : 'text-slate-500 hover:text-slate-200'">{{ $icono }}</button>
                    @endforeach
                </div>
            @endif
        </div>

        @if (count($lista) === 0)
            <p class="px-4 py-8 text-center text-[11px] leading-relaxed text-slate-600">
                Todavía no se ha cruzado con nadie.
            </p>
        @else

            {{-- ====== LISTA ====== --}}

            <div x-show="view === 'LIST'" class="divide-y divide-slate-800/60">
                <template x-for="r in shown" :key="'rl' + r.id">
                    <a :href="'{{ route('universes.entities.head-to-head', [$universe, $entity]) }}?rival=' + r.id"
                        class="flex flex-wrap items-center gap-2.5 px-3 py-2 transition hover:bg-slate-800/40">

                        <span class="flex h-11 w-11 shrink-0 items-center justify-center overflow-hidden rounded-lg bg-slate-950">
                            <template x-if="r.image_url">
                                <img :src="r.image_url" alt="" loading="lazy" class="h-full w-full object-cover">
                            </template>
                            <template x-if="!r.image_url">
                                <span class="font-mono text-[10px] font-black text-slate-700"
                                    x-text="r.name.slice(0, 2).toUpperCase()"></span>
                            </template>
                        </span>

                        <span class="w-40 shrink-0">
                            <span class="block truncate text-[12px] font-black text-slate-100" x-text="r.name"></span>
                            <span class="block truncate font-mono text-[9px] text-slate-600"
                                x-text="r.matches + (r.matches === 1 ? ' enfrentamiento' : ' enfrentamientos')
                                    + (r.type ? ' · ' + r.type : '')"></span>
                        </span>

                        {{-- Sus atributos: por qué se parecen o no --}}
                        <span class="hidden min-w-0 flex-1 flex-wrap gap-0.5 sm:flex">
                            <template x-for="a in (r.attributes ?? []).slice(0, 4)" :key="'ra' + r.id + a.key">
                                <span class="truncate rounded bg-slate-950 px-1.5 py-0.5 text-[9px] text-slate-400"
                                    :title="a.name + ': ' + a.display"
                                    x-text="a.display || a.values.join(', ')"></span>
                            </template>
                        </span>

                        <span class="flex h-2 w-32 shrink-0 overflow-hidden rounded-full bg-slate-950 sm:w-40">
                            <span class="bg-emerald-500" :style="'width:' + (r.played ? r.wins / r.played * 100 : 0) + '%'"></span>
                            <span class="bg-slate-600" :style="'width:' + (r.played ? r.draws / r.played * 100 : 0) + '%'"></span>
                            <span class="bg-rose-500" :style="'width:' + (r.played ? r.losses / r.played * 100 : 0) + '%'"></span>
                        </span>

                        <span class="w-20 shrink-0 text-right font-mono text-[12px] font-black">
                            <span class="text-emerald-300" x-text="r.wins"></span><span class="text-slate-700">–</span><span class="text-slate-500" x-text="r.draws"></span><span class="text-slate-700">–</span><span class="text-rose-300" x-text="r.losses"></span>
                        </span>

                        <span class="w-12 shrink-0 text-right font-mono text-[12px]"
                            :class="r.winrate === null ? 'text-slate-700'
                                : (r.winrate >= 50 ? 'text-emerald-300' : 'text-slate-400')"
                            x-text="r.winrate === null ? '—' : r.winrate + '%'"></span>
                    </a>
                </template>
            </div>


            {{-- ====== GALERÍA ====== --}}

            <div x-show="view === 'GALLERY'" x-cloak
                class="grid gap-2 p-3 grid-cols-2 sm:grid-cols-4 lg:grid-cols-6">

                <template x-for="r in shown" :key="'rg' + r.id">
                    <a :href="'{{ route('universes.entities.head-to-head', [$universe, $entity]) }}?rival=' + r.id"
                        class="group overflow-hidden rounded-xl border border-slate-800 bg-slate-950/60 transition hover:border-rose-500/50">

                        <span class="relative block aspect-[4/5] overflow-hidden bg-slate-950">
                            <template x-if="r.image_url">
                                <img :src="r.image_url" alt="" loading="lazy"
                                    class="h-full w-full object-cover transition duration-200 group-hover:scale-105">
                            </template>
                            <template x-if="!r.image_url">
                                <span class="flex h-full w-full items-center justify-center font-mono text-[15px] font-black text-slate-700"
                                    x-text="r.name.slice(0, 2).toUpperCase()"></span>
                            </template>

                            <span class="absolute left-1 top-1 rounded bg-slate-950/90 px-1 py-0.5 font-mono text-[9px] font-black">
                                <span class="text-emerald-300" x-text="r.wins"></span><span class="text-slate-700">–</span><span class="text-slate-500" x-text="r.draws"></span><span class="text-slate-700">–</span><span class="text-rose-300" x-text="r.losses"></span>
                            </span>

                            <span class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-slate-950 to-transparent px-1.5 pb-1 pt-5">
                                <span class="block truncate text-[10px] font-black text-slate-100" x-text="r.name"></span>
                                <span class="block truncate text-[8px] text-slate-500"
                                    x-text="r.matches + (r.matches === 1 ? ' vez' : ' veces')"></span>
                            </span>
                        </span>

                        <span class="flex h-1.5 overflow-hidden bg-slate-950">
                            <span class="bg-emerald-500" :style="'width:' + (r.played ? r.wins / r.played * 100 : 0) + '%'"></span>
                            <span class="bg-slate-600" :style="'width:' + (r.played ? r.draws / r.played * 100 : 0) + '%'"></span>
                            <span class="bg-rose-500" :style="'width:' + (r.played ? r.losses / r.played * 100 : 0) + '%'"></span>
                        </span>
                    </a>
                </template>
            </div>

            <template x-if="shown.length === 0">
                <p class="px-4 py-8 text-center text-[11px] text-slate-600">
                    Ningún rival coincide con la búsqueda.
                </p>
            </template>
        @endif
    </section>
</div>
