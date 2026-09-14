@php
    /*
     * Los atributos de la comunidad, de cinco formas.
     *
     * La especial es «valores»: un atributo de catálogo se copia por lo que trae
     * dentro, y un catálogo vacío no sirve de nada. Esa vista lo enseña a lo
     * ancho, con las caras de sus valores, antes de copiarlo.
     */
@endphp

{{-- ---------- GALERÍA ---------- --}}

<div x-show="vista === 'gallery'" x-cloak class="grid gap-2" :class="columnas">
    @foreach ($items as $atributo)
        @php $tono = $atributo->color ?: '#8b5cf6'; @endphp

        <a href="{{ route('community.attributes.show', $atributo) }}" title="{{ $atributo->name }}"
            class="group relative overflow-hidden rounded-xl border bg-slate-950 transition hover:-translate-y-0.5"
            style="border-color: {{ $tono }}40">

            <span class="block aspect-[4/3] overflow-hidden bg-slate-900">
                @if ($atributo->image_url)
                    <img src="{{ $atributo->image_url }}" alt="" loading="lazy"
                        class="h-full w-full object-cover transition duration-300 group-hover:scale-105">
                @else
                    <span class="flex h-full w-full items-center justify-center text-2xl"
                        style="color: {{ $tono }}66">{{ $atributo->icon ?: $atributo->data_type_icon }}</span>
                @endif
            </span>

            @if ($atributo->clones->isNotEmpty())
                <span class="absolute right-1 top-1 rounded bg-emerald-500 px-1 text-[8px] font-black text-emerald-950">✓</span>
            @endif

            <span class="block truncate px-1.5 pt-1 text-center text-[10px] font-black text-slate-300">
                {{ $atributo->name }}
            </span>
            <span class="block truncate px-1.5 pb-1 text-center text-[9px] text-slate-600">
                {{ '@' . ($atributo->creator?->username ?? '?') }}
            </span>
        </a>
    @endforeach
</div>


{{-- ---------- CUADRÍCULA ---------- --}}

<div x-show="vista === 'grid'" class="grid gap-2.5" :class="columnasAnchas">
    @foreach ($items as $atributo)
        @include('community.partials.tarjeta-atributo', ['atributo' => $atributo])
    @endforeach
</div>


{{-- ---------- LISTA ---------- --}}

<div x-show="vista === 'list'" x-cloak
    class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/50">

    @foreach ($items as $atributo)
        @php
            $tono = $atributo->color ?: '#8b5cf6';
            $miCopia = $atributo->clones->first();
        @endphp

        <div class="flex items-center gap-3 border-b border-slate-800/70 px-4 py-2 transition hover:bg-slate-950/50">

            <a href="{{ route('community.attributes.show', $atributo) }}"
                class="h-10 w-10 shrink-0 overflow-hidden rounded-lg border bg-slate-950"
                style="border-color: {{ $tono }}40">
                @if ($atributo->image_url)
                    <img src="{{ $atributo->image_url }}" alt="" loading="lazy" class="h-full w-full object-cover">
                @else
                    <span class="flex h-full w-full items-center justify-center"
                        style="color: {{ $tono }}">{{ $atributo->icon ?: $atributo->data_type_icon }}</span>
                @endif
            </a>

            <div class="min-w-0 flex-1">
                <div class="flex flex-wrap items-center gap-1.5">
                    <a href="{{ route('community.attributes.show', $atributo) }}"
                        class="truncate text-[12px] font-black text-white">{{ $atributo->name }}</a>

                    <span class="rounded border border-slate-800 px-1.5 py-0.5 text-[8px] font-black uppercase tracking-wider"
                        style="color: {{ $tono }}">{{ $atributo->data_type_label }}</span>
                </div>

                @include('community.partials.atribucion', [
                    'autor' => $atributo->creator,
                    'origen' => $atributo->sourceAttribute?->creator,
                ])
            </div>

            @if ($atributo->data_type === 'OPTION')
                <span class="shrink-0 rounded-lg border px-2 py-1 font-mono text-[10px] font-black"
                    style="border-color: {{ $tono }}40; color: {{ $atributo->options_count > 0 ? $tono : '#f43f5e' }}"
                    title="Valores del catálogo">
                    {{ $atributo->options_count }}
                </span>
            @endif

            @include('community.partials.copiar', [
                'ruta' => route('community.attributes.clone', $atributo),
                'esMio' => $atributo->user_id === auth()->id(),
                'miCopia' => $miCopia,
                'seDeja' => (bool) $atributo->allow_cloning,
                'rutaMia' => $miCopia ? route('attributes.show', $miCopia) : '#',
                'compacto' => true,
            ])
        </div>
    @endforeach
</div>


{{-- ---------- TABLA ---------- --}}

<div x-show="vista === 'table'" x-cloak
    class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/50">

    <div class="overflow-x-auto">
        <table class="w-full min-w-[740px]">
            <thead class="border-b border-slate-800 text-left">
                <tr class="text-[9px] font-black uppercase tracking-wider text-slate-600">
                    <th class="px-4 py-2.5">Atributo</th>
                    <th class="px-3 py-2.5">Tipo</th>
                    <th class="px-3 py-2.5">Creador</th>
                    <th class="px-3 py-2.5">Inspirado en</th>
                    <th class="px-3 py-2.5 text-right">Valores</th>
                    <th class="px-3 py-2.5 text-right">Copias</th>
                    <th class="px-3 py-2.5"></th>
                </tr>
            </thead>

            <tbody class="divide-y divide-slate-800/70">
                @foreach ($items as $atributo)
                    @php
                        $tono = $atributo->color ?: '#8b5cf6';
                        $miCopia = $atributo->clones->first();
                        $inspirado = $atributo->sourceAttribute?->creator;
                    @endphp

                    <tr class="transition hover:bg-slate-950/50">
                        <td class="px-4 py-2">
                            <a href="{{ route('community.attributes.show', $atributo) }}" class="flex items-center gap-2">
                                <span class="h-8 w-8 shrink-0 overflow-hidden rounded-lg border bg-slate-950"
                                    style="border-color: {{ $tono }}40">
                                    @if ($atributo->image_url)
                                        <img src="{{ $atributo->image_url }}" alt="" loading="lazy" class="h-full w-full object-cover">
                                    @else
                                        <span class="flex h-full w-full items-center justify-center text-[11px]"
                                            style="color: {{ $tono }}">{{ $atributo->icon ?: $atributo->data_type_icon }}</span>
                                    @endif
                                </span>
                                <span class="truncate text-[12px] font-black text-white">{{ $atributo->name }}</span>
                            </a>
                        </td>

                        <td class="px-3 py-2 text-[11px]" style="color: {{ $tono }}">{{ $atributo->data_type_label }}</td>

                        <td class="px-3 py-2 text-[11px]">
                            <a href="{{ route('community.creators.show', $atributo->creator->username) }}"
                                class="text-violet-300 transition hover:underline">{{ '@' . $atributo->creator->username }}</a>
                        </td>

                        <td class="px-3 py-2 text-[11px]">
                            @if ($inspirado)
                                <a href="{{ route('community.creators.show', $inspirado->username) }}"
                                    class="text-amber-400/80 transition hover:underline">{{ '@' . $inspirado->username }}</a>
                            @else
                                <span class="text-slate-700">original</span>
                            @endif
                        </td>

                        <td class="px-3 py-2 text-right font-mono text-[11px]">
                            @if ($atributo->data_type === 'OPTION')
                                <span style="color: {{ $atributo->options_count > 0 ? $tono : '#f43f5e' }}">
                                    {{ $atributo->options_count }}
                                </span>
                            @else
                                <span class="text-slate-700">—</span>
                            @endif
                        </td>

                        <td class="px-3 py-2 text-right font-mono text-[11px] {{ $atributo->clones_count > 0 ? 'text-violet-300' : 'text-slate-700' }}">
                            {{ $atributo->clones_count }}
                        </td>

                        <td class="px-3 py-2 text-right">
                            @include('community.partials.copiar', [
                                'ruta' => route('community.attributes.clone', $atributo),
                                'esMio' => $atributo->user_id === auth()->id(),
                                'miCopia' => $miCopia,
                                'seDeja' => (bool) $atributo->allow_cloning,
                                'rutaMia' => $miCopia ? route('attributes.show', $miCopia) : '#',
                                'compacto' => true,
                            ])
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>


{{-- ---------- VALORES ---------- --}}

<div x-show="vista === 'special'" x-cloak class="space-y-2.5">

    <p class="text-[10px] leading-relaxed text-slate-500">
        Un atributo de catálogo se copia por lo que trae dentro. Aquí se ven sus valores antes de
        copiarlo, que es cuando importa saber si el catálogo está lleno o vacío.
    </p>

    @foreach ($items as $atributo)
        @php
            $tono = $atributo->color ?: '#8b5cf6';
            $miCopia = $atributo->clones->first();
            $valores = $atributo->relationLoaded('options') ? $atributo->options : collect();
            $esCatalogo = $atributo->data_type === 'OPTION';
        @endphp

        <section class="overflow-hidden rounded-2xl border bg-slate-900/50"
            style="border-color: {{ $esCatalogo && $atributo->options_count === 0 ? '#f43f5e40' : $tono . '40' }}">

            <div class="flex flex-wrap items-center gap-2.5 border-b border-slate-800 px-3 py-2">

                <span class="h-9 w-9 shrink-0 overflow-hidden rounded-lg border bg-slate-950"
                    style="border-color: {{ $tono }}55">
                    @if ($atributo->image_url)
                        <img src="{{ $atributo->image_url }}" alt="" loading="lazy" class="h-full w-full object-cover">
                    @else
                        <span class="flex h-full w-full items-center justify-center"
                            style="color: {{ $tono }}">{{ $atributo->icon ?: $atributo->data_type_icon }}</span>
                    @endif
                </span>

                <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-center gap-1.5">
                        <a href="{{ route('community.attributes.show', $atributo) }}"
                            class="truncate text-[12px] font-black text-white transition hover:underline">
                            {{ $atributo->name }}
                        </a>
                        <span class="rounded px-1.5 py-0.5 text-[8px] font-black uppercase tracking-wider"
                            style="background-color: {{ $tono }}22; color: {{ $tono }}">
                            {{ $atributo->data_type_label }}
                        </span>
                    </div>

                    @include('community.partials.atribucion', [
                        'autor' => $atributo->creator,
                        'origen' => $atributo->sourceAttribute?->creator,
                    ])
                </div>

                @include('community.partials.copiar', [
                    'ruta' => route('community.attributes.clone', $atributo),
                    'esMio' => $atributo->user_id === auth()->id(),
                    'miCopia' => $miCopia,
                    'seDeja' => (bool) $atributo->allow_cloning,
                    'rutaMia' => $miCopia ? route('attributes.show', $miCopia) : '#',
                    'compacto' => true,
                ])
            </div>

            @if (! $esCatalogo)
                <p class="px-3 py-3 text-center text-[10px] text-slate-600">
                    No es un catálogo: su valor se escribe a mano en cada entidad, así que no trae ninguna
                    lista dentro.
                </p>
            @elseif ($atributo->options_count === 0)
                <p class="px-3 py-3 text-center text-[10px] text-rose-300/70">
                    Catálogo vacío: copiarlo no trae ningún valor.
                </p>
            @elseif ($valores->isNotEmpty())
                <div class="grid grid-cols-4 gap-1.5 p-3 sm:grid-cols-8 lg:grid-cols-12">
                    @foreach ($valores as $valor)
                        <a href="{{ route('community.catalogs.show', $valor) }}" title="{{ $valor->name }}"
                            class="block overflow-hidden rounded-lg border border-slate-800 bg-slate-950">
                            <span class="block aspect-square overflow-hidden">
                                @if ($valor->image_url)
                                    <img src="{{ $valor->image_url }}" alt="" loading="lazy" class="h-full w-full object-cover">
                                @else
                                    <span class="flex h-full w-full items-center justify-center text-slate-700">◇</span>
                                @endif
                            </span>
                            <span class="block truncate px-1 py-0.5 text-center text-[8px] font-black text-slate-500">
                                {{ $valor->name }}
                            </span>
                        </a>
                    @endforeach

                    @if ($atributo->options_count > $valores->count())
                        <span class="flex aspect-square items-center justify-center rounded-lg border border-slate-800 bg-slate-950 font-mono text-[10px] font-black"
                            style="color: {{ $tono }}">
                            +{{ $atributo->options_count - $valores->count() }}
                        </span>
                    @endif
                </div>
            @endif
        </section>
    @endforeach
</div>
