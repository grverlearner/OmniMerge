@php
    /*
     * CARA A CARA
     *
     * Dos pantallas en una, y cuál sale depende de si hay rival:
     *
     *   sin rival   el selector, con caras, búsqueda y orden
     *   con rival   la comparación, enfrentando sus cifras
     *
     * Entrar sin elegir a nadie es lo normal —se viene aquí justamente a
     * elegir—, y antes eso devolvía 404 porque el rival se buscaba con
     * findOrFail sobre el id 0.
     */

    $versiones = app(\App\Services\Universes\UniverseEntityVersionResolver::class);

    /* Cómo se lee una cifra enfrentada: quién gana esa fila */
    $mejor = fn ($a, $b) => $a === $b ? null : ($a > $b ? 'left' : 'right');
@endphp

<x-universe-layout :universe="$universe" surface="dark">

    <x-slot name="header">Cara a cara</x-slot>

    <div x-data="{
            search: '',
            sort: 'crossed',
            view: 'GALLERY',

            get shown() {
                let lista = @js($candidates);

                const q = this.search.trim().toLowerCase();

                if (q) {
                    lista = lista.filter((c) =>
                        c.name.toLowerCase().includes(q)
                        || (c.type ?? '').toLowerCase().includes(q)
                        || c.attributes.some((a) =>
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
                    /* Los ya cruzados primero: es contra quien hay historia */
                    crossed: desc((c) => c.h2h?.matches ?? 0),
                    name: porNombre,
                    titles: desc((c) => c.record.titles),
                    wins: desc((c) => c.record.wins),
                    winrate: desc((c) => c.record.winrate ?? -1),
                }[this.sort] ?? porNombre);
            },
        }">


        {{-- ============ LOS DOS EN LA CABECERA ============ --}}

        <div class="mb-3 flex flex-wrap items-center gap-2">

            <a href="{{ route('universes.entities.show', [$universe, $entity]) }}"
                class="rounded-lg border border-slate-800 px-2 py-1.5 text-[11px] font-black text-slate-400 transition hover:border-slate-600 hover:text-slate-100">←</a>

            <span class="flex h-10 w-10 shrink-0 items-center justify-center overflow-hidden rounded-lg bg-slate-950">
                @if ($entity->image_url)
                    <img src="{{ $entity->image_url }}" alt="" class="h-full w-full object-cover">
                @else
                    <span class="font-mono text-[10px] font-black text-slate-700">
                        {{ mb_strtoupper(mb_substr($entity->display_label, 0, 2)) }}
                    </span>
                @endif
            </span>

            <div>
                <p class="text-[9px] font-black uppercase tracking-[0.18em] text-rose-300">
                    {{ $universe->name }} · cara a cara
                </p>
                <h1 class="text-lg font-black text-slate-100">
                    {{ $entity->display_label }}
                    @if ($rival)
                        <span class="text-slate-600">contra</span> {{ $rival->display_label }}
                    @endif
                </h1>
            </div>

            @if ($rival)
                <a href="{{ route('universes.entities.head-to-head', [$universe, $entity]) }}"
                    class="ml-auto rounded-lg border border-slate-800 px-2.5 py-1.5 text-[10px] font-black text-slate-400 transition hover:border-rose-500 hover:text-rose-300">
                    cambiar de rival
                </a>
            @endif
        </div>


        @if (! $rival)

            {{-- ==================== ELEGIR RIVAL ==================== --}}

            <div class="mb-3 rounded-2xl border border-slate-800 bg-slate-900/50 p-2">
                <div class="flex flex-wrap items-center gap-1.5">

                    <input type="search" x-model="search"
                        placeholder="buscar por nombre, tipo o atributo…"
                        class="min-w-0 flex-1 rounded-lg border-slate-700 bg-slate-950 px-3 py-1.5 text-[11px] text-slate-200 placeholder:text-slate-700 focus:border-rose-500 focus:ring-rose-500">

                    <select x-model="sort"
                        class="rounded-lg border-slate-700 bg-slate-950 px-2 py-1.5 text-[11px] text-slate-300 focus:border-rose-500 focus:ring-rose-500">
                        <option value="crossed">Con los que ya se cruzó</option>
                        <option value="name">Nombre</option>
                        <option value="titles">Más títulos</option>
                        <option value="wins">Más victorias</option>
                        <option value="winrate">Mejor porcentaje</option>
                    </select>

                    <div class="flex rounded-lg border border-slate-800 bg-slate-950 p-0.5">
                        @foreach (['GALLERY' => '▤', 'LIST' => '☰'] as $modo => $icono)
                            <button type="button" @click="view = '{{ $modo }}'"
                                class="rounded-md px-2 py-1 text-[11px] transition"
                                :class="view === '{{ $modo }}'
                                    ? 'bg-rose-500 text-slate-950'
                                    : 'text-slate-500 hover:text-slate-200'">{{ $icono }}</button>
                        @endforeach
                    </div>

                    <span class="font-mono text-[10px] text-slate-600" x-text="shown.length + ' rivales'"></span>
                </div>
            </div>


            {{-- Galería --}}

            <div x-show="view === 'GALLERY'" x-cloak
                class="grid gap-2 grid-cols-2 sm:grid-cols-4 lg:grid-cols-6">

                <template x-for="c in shown" :key="'g' + c.id">
                    <a :href="'{{ route('universes.entities.head-to-head', [$universe, $entity]) }}?rival=' + c.id"
                        class="group overflow-hidden rounded-2xl border transition"
                        :class="c.h2h
                            ? 'border-rose-500/40 bg-rose-500/5 hover:border-rose-400'
                            : 'border-slate-800 bg-slate-900/50 hover:border-slate-600'">

                        <span class="relative block aspect-[4/5] overflow-hidden bg-slate-950">
                            <template x-if="c.image_url">
                                <img :src="c.image_url" alt="" loading="lazy"
                                    class="h-full w-full object-cover transition duration-200 group-hover:scale-105">
                            </template>
                            <template x-if="!c.image_url">
                                <span class="flex h-full w-full items-center justify-center font-mono text-[16px] font-black text-slate-700"
                                    x-text="c.name.slice(0, 2).toUpperCase()"></span>
                            </template>

                            {{-- Si ya se cruzaron, cuántas veces y cómo fue --}}
                            <template x-if="c.h2h">
                                <span class="absolute left-1 top-1 rounded bg-slate-950/90 px-1 py-0.5 font-mono text-[8px] font-black">
                                    <span class="text-emerald-300" x-text="c.h2h.wins"></span><span class="text-slate-700">–</span><span class="text-slate-500" x-text="c.h2h.draws"></span><span class="text-slate-700">–</span><span class="text-rose-300" x-text="c.h2h.losses"></span>
                                </span>
                            </template>

                            <span x-show="c.record.titles"
                                class="absolute right-1 top-1 rounded bg-amber-500 px-1 font-mono text-[8px] font-black text-slate-950"
                                x-text="'★' + c.record.titles"></span>

                            <span class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-slate-950 via-slate-950/80 to-transparent px-2 pb-1.5 pt-6">
                                <span class="block truncate text-[11px] font-black text-slate-100" x-text="c.name"></span>
                                <span class="block truncate text-[9px]"
                                    :class="c.h2h ? 'text-rose-300' : 'text-slate-500'"
                                    x-text="c.h2h
                                        ? c.h2h.matches + (c.h2h.matches === 1 ? ' cruce' : ' cruces')
                                        : 'nunca se han visto'"></span>
                            </span>
                        </span>

                        <span class="flex flex-wrap gap-0.5 p-1.5">
                            <template x-for="a in c.attributes.slice(0, 3)" :key="'ga' + c.id + a.key">
                                <span class="truncate rounded bg-slate-950 px-1 py-0.5 text-[8px] text-slate-400"
                                    :title="a.name + ': ' + a.display"
                                    x-text="a.display || a.values.join(', ')"></span>
                            </template>
                        </span>
                    </a>
                </template>
            </div>


            {{-- Lista --}}

            <div x-show="view === 'LIST'" x-cloak class="space-y-1">
                <template x-for="c in shown" :key="'l' + c.id">
                    <a :href="'{{ route('universes.entities.head-to-head', [$universe, $entity]) }}?rival=' + c.id"
                        class="flex items-center gap-2.5 rounded-xl border p-2 transition"
                        :class="c.h2h
                            ? 'border-rose-500/30 bg-rose-500/5 hover:border-rose-400'
                            : 'border-slate-800 bg-slate-900/50 hover:border-slate-600'">

                        <span class="flex h-11 w-11 shrink-0 items-center justify-center overflow-hidden rounded-lg bg-slate-950">
                            <template x-if="c.image_url">
                                <img :src="c.image_url" alt="" loading="lazy" class="h-full w-full object-cover">
                            </template>
                            <template x-if="!c.image_url">
                                <span class="font-mono text-[10px] font-black text-slate-700"
                                    x-text="c.name.slice(0, 2).toUpperCase()"></span>
                            </template>
                        </span>

                        <span class="w-40 shrink-0">
                            <span class="block truncate text-[12px] font-black text-slate-100" x-text="c.name"></span>
                            <span class="block truncate font-mono text-[8px] text-slate-600"
                                x-text="c.code + (c.type ? ' · ' + c.type : '')"></span>
                        </span>

                        <span class="flex min-w-0 flex-1 flex-wrap gap-0.5">
                            <template x-for="a in c.attributes" :key="'la' + c.id + a.key">
                                <span class="rounded bg-slate-950 px-1.5 py-0.5 text-[9px]">
                                    <span class="text-slate-600" x-text="a.name"></span>
                                    <span class="ml-1 text-slate-300" x-text="a.display || a.values.join(', ')"></span>
                                </span>
                            </template>
                        </span>

                        <span class="w-28 shrink-0 text-right">
                            <template x-if="c.h2h">
                                <span>
                                    <span class="block font-mono text-[12px] font-black">
                                        <span class="text-emerald-300" x-text="c.h2h.wins"></span><span class="text-slate-700">–</span><span class="text-slate-500" x-text="c.h2h.draws"></span><span class="text-slate-700">–</span><span class="text-rose-300" x-text="c.h2h.losses"></span>
                                    </span>
                                    <span class="block font-mono text-[8px] text-rose-300"
                                        x-text="c.h2h.matches + ' cruces'"></span>
                                </span>
                            </template>

                            <template x-if="!c.h2h">
                                <span class="block text-[9px] text-slate-700">nunca se han visto</span>
                            </template>
                        </span>
                    </a>
                </template>
            </div>

            <template x-if="shown.length === 0">
                <p class="rounded-2xl border border-dashed border-slate-700 px-4 py-10 text-center text-[11px] text-slate-600">
                    Ningún competidor coincide con la búsqueda.
                </p>
            </template>

        @else

            @include('universes.entities.partials.h2h-comparison')

        @endif
    </div>

</x-universe-layout>
