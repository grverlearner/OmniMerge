@php
    /*
     * Los valores de catálogo de la comunidad, de cinco formas.
     *
     * La especial es «por catálogo»: un valor suelto no significa nada —«Uzumaki»
     * fuera de «Clan» es una palabra— así que agruparlos por el catálogo al que
     * pertenecen es la única forma de mirarlos que se entiende sola.
     */

    $porCatalogo = $items instanceof \Illuminate\Contracts\Pagination\Paginator
        || $items instanceof \Illuminate\Pagination\LengthAwarePaginator
        ? $items->getCollection()->groupBy('attribute_id')
        : $items->groupBy('attribute_id');
@endphp

{{-- ---------- GALERÍA ---------- --}}

<div x-show="vista === 'gallery'" x-cloak class="grid gap-2" :class="columnas">
    @foreach ($items as $valor)
        @php $tono = $valor->color ?: ($valor->attribute?->color ?: '#6366f1'); @endphp

        <a href="{{ route('community.catalogs.show', $valor) }}" title="{{ $valor->name }}"
            class="group relative overflow-hidden rounded-xl border bg-slate-950 transition hover:-translate-y-0.5"
            style="border-color: {{ $valor->image_url ? $tono . '40' : '#f43f5e40' }}">

            <span class="block aspect-square overflow-hidden bg-slate-900">
                @if ($valor->image_url)
                    <img src="{{ $valor->image_url }}" alt="" loading="lazy"
                        class="h-full w-full object-cover transition duration-300 group-hover:scale-105">
                @else
                    <span class="flex h-full w-full items-center justify-center text-2xl"
                        style="color: {{ $tono }}66">{{ $valor->icon ?: '◇' }}</span>
                @endif
            </span>

            @if ($valor->clones->isNotEmpty())
                <span class="absolute right-1 top-1 rounded bg-emerald-500 px-1 text-[8px] font-black text-emerald-950">✓</span>
            @endif

            <span class="block truncate px-1.5 pt-1 text-center text-[10px] font-black text-slate-300">
                {{ $valor->name }}
            </span>
            <span class="block truncate px-1.5 pb-1 text-center text-[9px] text-slate-600">
                {{ $valor->attribute?->name }}
            </span>
        </a>
    @endforeach
</div>


{{-- ---------- CUADRÍCULA ---------- --}}

<div x-show="vista === 'grid'" class="grid gap-2.5" :class="columnas">
    @foreach ($items as $valor)
        @include('community.partials.tarjeta-catalogo', ['valor' => $valor])
    @endforeach
</div>


{{-- ---------- LISTA ---------- --}}

<div x-show="vista === 'list'" x-cloak
    class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/50">

    @foreach ($items as $valor)
        @php
            $tono = $valor->color ?: ($valor->attribute?->color ?: '#6366f1');
            $miCopia = $valor->clones->first();
        @endphp

        <div class="flex items-center gap-3 border-b border-slate-800/70 px-4 py-2 transition hover:bg-slate-950/50">

            <a href="{{ route('community.catalogs.show', $valor) }}"
                class="h-10 w-10 shrink-0 overflow-hidden rounded-lg border bg-slate-950"
                style="border-color: {{ $valor->image_url ? $tono . '40' : '#f43f5e55' }}">
                @if ($valor->image_url)
                    <img src="{{ $valor->image_url }}" alt="" loading="lazy" class="h-full w-full object-cover">
                @else
                    <span class="flex h-full w-full items-center justify-center"
                        style="color: {{ $tono }}">{{ $valor->icon ?: '◇' }}</span>
                @endif
            </a>

            <div class="min-w-0 flex-1">
                <a href="{{ route('community.catalogs.show', $valor) }}"
                    class="block truncate text-[12px] font-black text-white">{{ $valor->name }}</a>

                <p class="truncate text-[10px]" style="color: {{ $valor->attribute?->color ?: '#a78bfa' }}">
                    {{ $valor->attribute?->name }}
                    @if ($valor->parent)
                        <span class="text-slate-700">·</span>
                        <span class="text-slate-600">dentro de {{ $valor->parent->name }}</span>
                    @endif
                </p>
            </div>

            @include('community.partials.atribucion', [
                'autor' => $valor->user,
                'origen' => $valor->sourceOption?->user,
            ])

            @include('community.partials.copiar', [
                'ruta' => route('community.catalogs.clone', $valor),
                'esMio' => $valor->user_id === auth()->id(),
                'miCopia' => $miCopia,
                'seDeja' => true,
                'rutaMia' => $miCopia ? route('attribute-options.show', $miCopia) : '#',
                'compacto' => true,
            ])
        </div>
    @endforeach
</div>


{{-- ---------- TABLA ---------- --}}

<div x-show="vista === 'table'" x-cloak
    class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/50">

    <div class="overflow-x-auto">
        <table class="w-full min-w-[700px]">
            <thead class="border-b border-slate-800 text-left">
                <tr class="text-[9px] font-black uppercase tracking-wider text-slate-600">
                    <th class="px-4 py-2.5">Valor</th>
                    <th class="px-3 py-2.5">Catálogo</th>
                    <th class="px-3 py-2.5">Cuelga de</th>
                    <th class="px-3 py-2.5">Creador</th>
                    <th class="px-3 py-2.5 text-right">Hijos</th>
                    <th class="px-3 py-2.5"></th>
                </tr>
            </thead>

            <tbody class="divide-y divide-slate-800/70">
                @foreach ($items as $valor)
                    @php
                        $tono = $valor->color ?: ($valor->attribute?->color ?: '#6366f1');
                        $miCopia = $valor->clones->first();
                    @endphp

                    <tr class="transition hover:bg-slate-950/50">
                        <td class="px-4 py-2">
                            <a href="{{ route('community.catalogs.show', $valor) }}" class="flex items-center gap-2">
                                <span class="h-8 w-8 shrink-0 overflow-hidden rounded-lg border bg-slate-950"
                                    style="border-color: {{ $tono }}40">
                                    @if ($valor->image_url)
                                        <img src="{{ $valor->image_url }}" alt="" loading="lazy" class="h-full w-full object-cover">
                                    @else
                                        <span class="flex h-full w-full items-center justify-center text-[11px]"
                                            style="color: {{ $tono }}">{{ $valor->icon ?: '◇' }}</span>
                                    @endif
                                </span>
                                <span class="truncate text-[12px] font-black text-white">{{ $valor->name }}</span>
                            </a>
                        </td>

                        <td class="px-3 py-2 text-[11px]" style="color: {{ $valor->attribute?->color ?: '#a78bfa' }}">
                            {{ $valor->attribute?->name ?? '—' }}
                        </td>

                        <td class="px-3 py-2 text-[11px] text-slate-500">{{ $valor->parent?->name ?? '—' }}</td>

                        <td class="px-3 py-2 text-[11px]">
                            @if ($valor->user)
                                <a href="{{ route('community.creators.show', $valor->user->username) }}"
                                    class="text-violet-300 transition hover:underline">{{ '@' . $valor->user->username }}</a>
                            @else
                                <span class="text-slate-700">—</span>
                            @endif
                        </td>

                        <td class="px-3 py-2 text-right font-mono text-[11px] {{ $valor->children_count > 0 ? 'text-slate-300' : 'text-slate-700' }}">
                            {{ $valor->children_count }}
                        </td>

                        <td class="px-3 py-2 text-right">
                            @include('community.partials.copiar', [
                                'ruta' => route('community.catalogs.clone', $valor),
                                'esMio' => $valor->user_id === auth()->id(),
                                'miCopia' => $miCopia,
                                'seDeja' => true,
                                'rutaMia' => $miCopia ? route('attribute-options.show', $miCopia) : '#',
                                'compacto' => true,
                            ])
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>


{{-- ---------- POR CATÁLOGO ---------- --}}

<div x-show="vista === 'special'" x-cloak class="space-y-2.5">

    <p class="text-[10px] leading-relaxed text-slate-500">
        Un valor suelto no significa nada: «Uzumaki» fuera de «Clan» es una palabra. Aquí van agrupados
        por el catálogo al que pertenecen, que es como se entienden. Se agrupa lo que hay en esta
        página, así que un catálogo puede aparecer con menos valores de los que tiene.
    </p>

    @foreach ($porCatalogo as $idCatalogo => $suyos)
        @php
            $catalogo = $suyos->first()->attribute;
            $tono = $catalogo?->color ?: '#6366f1';
        @endphp

        <section class="overflow-hidden rounded-2xl border bg-slate-900/50" style="border-color: {{ $tono }}40">

            <div class="flex flex-wrap items-center gap-2.5 border-b border-slate-800 px-3 py-2">

                <span class="h-8 w-8 shrink-0 overflow-hidden rounded-lg border bg-slate-950"
                    style="border-color: {{ $tono }}55">
                    @if ($catalogo?->image_url)
                        <img src="{{ $catalogo->image_url }}" alt="" loading="lazy" class="h-full w-full object-cover">
                    @else
                        <span class="flex h-full w-full items-center justify-center"
                            style="color: {{ $tono }}">{{ $catalogo?->icon ?: '◫' }}</span>
                    @endif
                </span>

                <div class="min-w-0 flex-1">
                    @if ($catalogo)
                        <a href="{{ route('community.attributes.show', $catalogo) }}"
                            class="block truncate text-[12px] font-black text-white transition hover:underline">
                            {{ $catalogo->name }}
                        </a>
                    @else
                        <span class="block text-[12px] font-black text-slate-500">Sin catálogo</span>
                    @endif

                    @if ($catalogo?->creator)
                        @include('community.partials.atribucion', [
                            'autor' => $catalogo->creator,
                            'origen' => null,
                        ])
                    @endif
                </div>

                <span class="shrink-0 font-mono text-[12px] font-black" style="color: {{ $tono }}">
                    {{ $suyos->count() }}
                </span>
            </div>

            <div class="grid grid-cols-4 gap-1.5 p-3 sm:grid-cols-8 lg:grid-cols-12">
                @foreach ($suyos as $valor)
                    <a href="{{ route('community.catalogs.show', $valor) }}" title="{{ $valor->name }}"
                        class="group relative block overflow-hidden rounded-lg border border-slate-800 bg-slate-950">
                        <span class="block aspect-square overflow-hidden">
                            @if ($valor->image_url)
                                <img src="{{ $valor->image_url }}" alt="" loading="lazy"
                                    class="h-full w-full object-cover transition duration-300 group-hover:scale-105">
                            @else
                                <span class="flex h-full w-full items-center justify-center text-slate-700">
                                    {{ $valor->icon ?: '◇' }}
                                </span>
                            @endif
                        </span>

                        @if ($valor->clones->isNotEmpty())
                            <span class="absolute right-0.5 top-0.5 rounded bg-emerald-500 px-1 text-[8px] font-black text-emerald-950">✓</span>
                        @endif

                        <span class="block truncate px-1 py-0.5 text-center text-[8px] font-black text-slate-500">
                            {{ $valor->name }}
                        </span>
                    </a>
                @endforeach
            </div>
        </section>
    @endforeach
</div>
