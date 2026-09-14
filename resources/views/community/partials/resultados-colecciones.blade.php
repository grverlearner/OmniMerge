@php
    /*
     * Las colecciones de la comunidad, de cinco formas.
     *
     * La especial es «contenido»: una colección no se juzga por su portada sino
     * por lo que tiene dentro, así que esa vista enseña las caras de sus
     * miembros a lo ancho.
     */
@endphp

{{-- ---------- GALERÍA ---------- --}}

<div x-show="vista === 'gallery'" x-cloak class="grid gap-2" :class="columnas">
    @foreach ($items as $coleccion)
        @php $tono = $coleccion->color ?: '#6366f1'; @endphp

        <a href="{{ route('community.collections.show', $coleccion) }}" title="{{ $coleccion->name }}"
            class="group relative overflow-hidden rounded-xl border bg-slate-950 transition hover:-translate-y-0.5"
            style="border-color: {{ $tono }}40">

            <span class="block aspect-[4/3] overflow-hidden bg-slate-900">
                @if ($coleccion->image_url)
                    <img src="{{ $coleccion->image_url }}" alt="" loading="lazy"
                        class="h-full w-full object-cover transition duration-300 group-hover:scale-105">
                @else
                    <span class="flex h-full w-full items-center justify-center text-2xl"
                        style="color: {{ $tono }}66">{{ $coleccion->icon ?: '❒' }}</span>
                @endif
            </span>

            <span class="absolute left-1 top-1 rounded bg-slate-950/85 px-1 font-mono text-[9px] font-black"
                style="color: {{ $tono }}">{{ $coleccion->entities_count }}</span>

            @if ($coleccion->clones->isNotEmpty())
                <span class="absolute right-1 top-1 rounded bg-emerald-500 px-1 text-[8px] font-black text-emerald-950">✓</span>
            @endif

            <span class="block truncate px-1.5 pt-1 text-center text-[10px] font-black text-slate-300">
                {{ $coleccion->name }}
            </span>
            <span class="block truncate px-1.5 pb-1 text-center text-[9px] text-slate-600">
                {{ '@' . ($coleccion->creator?->username ?? '?') }}
            </span>
        </a>
    @endforeach
</div>


{{-- ---------- CUADRÍCULA ---------- --}}

<div x-show="vista === 'grid'" class="grid gap-2.5" :class="columnasAnchas">
    @foreach ($items as $coleccion)
        @include('community.partials.tarjeta-coleccion', ['coleccion' => $coleccion])
    @endforeach
</div>


{{-- ---------- LISTA ---------- --}}

<div x-show="vista === 'list'" x-cloak
    class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/50">

    @foreach ($items as $coleccion)
        @php
            $tono = $coleccion->color ?: '#6366f1';
            $miCopia = $coleccion->clones->first();
        @endphp

        <div class="flex items-center gap-3 border-b border-slate-800/70 px-4 py-2 transition hover:bg-slate-950/50">

            <a href="{{ route('community.collections.show', $coleccion) }}"
                class="h-10 w-10 shrink-0 overflow-hidden rounded-lg border bg-slate-950"
                style="border-color: {{ $tono }}40">
                @if ($coleccion->image_url)
                    <img src="{{ $coleccion->image_url }}" alt="" loading="lazy" class="h-full w-full object-cover">
                @else
                    <span class="flex h-full w-full items-center justify-center"
                        style="color: {{ $tono }}">{{ $coleccion->icon ?: '❒' }}</span>
                @endif
            </a>

            <div class="min-w-0 flex-1">
                <a href="{{ route('community.collections.show', $coleccion) }}"
                    class="block truncate text-[12px] font-black text-white">{{ $coleccion->name }}</a>

                @include('community.partials.atribucion', [
                    'autor' => $coleccion->creator,
                    'origen' => $coleccion->sourceCollection?->creator,
                ])
            </div>

            <span class="shrink-0 rounded-lg border px-2 py-1 font-mono text-[10px] font-black"
                style="border-color: {{ $tono }}40; color: {{ $coleccion->entities_count > 0 ? $tono : '#475569' }}"
                title="Entidades dentro">
                {{ $coleccion->entities_count }}
            </span>

            @include('community.partials.copiar', [
                'ruta' => route('community.collections.clone', $coleccion),
                'esMio' => $coleccion->user_id === auth()->id(),
                'miCopia' => $miCopia,
                'seDeja' => (bool) $coleccion->allow_cloning,
                'rutaMia' => $miCopia ? route('collections.show', $miCopia) : '#',
                'compacto' => true,
            ])
        </div>
    @endforeach
</div>


{{-- ---------- TABLA ---------- --}}

<div x-show="vista === 'table'" x-cloak
    class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/50">

    <div class="overflow-x-auto">
        <table class="w-full min-w-[680px]">
            <thead class="border-b border-slate-800 text-left">
                <tr class="text-[9px] font-black uppercase tracking-wider text-slate-600">
                    <th class="px-4 py-2.5">Colección</th>
                    <th class="px-3 py-2.5">Creador</th>
                    <th class="px-3 py-2.5">Inspirada en</th>
                    <th class="px-3 py-2.5 text-right">Entidades</th>
                    <th class="px-3 py-2.5 text-right">Copias</th>
                    <th class="px-3 py-2.5"></th>
                </tr>
            </thead>

            <tbody class="divide-y divide-slate-800/70">
                @foreach ($items as $coleccion)
                    @php
                        $tono = $coleccion->color ?: '#6366f1';
                        $miCopia = $coleccion->clones->first();
                        $inspirada = $coleccion->sourceCollection?->creator;
                    @endphp

                    <tr class="transition hover:bg-slate-950/50">
                        <td class="px-4 py-2">
                            <a href="{{ route('community.collections.show', $coleccion) }}" class="flex items-center gap-2">
                                <span class="h-8 w-8 shrink-0 overflow-hidden rounded-lg border bg-slate-950"
                                    style="border-color: {{ $tono }}40">
                                    @if ($coleccion->image_url)
                                        <img src="{{ $coleccion->image_url }}" alt="" loading="lazy" class="h-full w-full object-cover">
                                    @else
                                        <span class="flex h-full w-full items-center justify-center text-[11px]"
                                            style="color: {{ $tono }}">{{ $coleccion->icon ?: '❒' }}</span>
                                    @endif
                                </span>
                                <span class="truncate text-[12px] font-black text-white">{{ $coleccion->name }}</span>
                            </a>
                        </td>

                        <td class="px-3 py-2 text-[11px]">
                            <a href="{{ route('community.creators.show', $coleccion->creator->username) }}"
                                class="text-violet-300 transition hover:underline">
                                {{ '@' . $coleccion->creator->username }}
                            </a>
                        </td>

                        <td class="px-3 py-2 text-[11px]">
                            @if ($inspirada)
                                <a href="{{ route('community.creators.show', $inspirada->username) }}"
                                    class="text-amber-400/80 transition hover:underline">{{ '@' . $inspirada->username }}</a>
                            @else
                                <span class="text-slate-700">original</span>
                            @endif
                        </td>

                        <td class="px-3 py-2 text-right font-mono text-[11px]"
                            style="color: {{ $coleccion->entities_count > 0 ? $tono : '#475569' }}">
                            {{ $coleccion->entities_count }}
                        </td>

                        <td class="px-3 py-2 text-right font-mono text-[11px] {{ $coleccion->clones_count > 0 ? 'text-violet-300' : 'text-slate-700' }}">
                            {{ $coleccion->clones_count }}
                        </td>

                        <td class="px-3 py-2 text-right">
                            @include('community.partials.copiar', [
                                'ruta' => route('community.collections.clone', $coleccion),
                                'esMio' => $coleccion->user_id === auth()->id(),
                                'miCopia' => $miCopia,
                                'seDeja' => (bool) $coleccion->allow_cloning,
                                'rutaMia' => $miCopia ? route('collections.show', $miCopia) : '#',
                                'compacto' => true,
                            ])
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>


{{-- ---------- CONTENIDO ---------- --}}

<div x-show="vista === 'special'" x-cloak class="space-y-2.5">

    <p class="text-[10px] leading-relaxed text-slate-500">
        Una colección no se juzga por su portada sino por lo que tiene dentro. Aquí se ven sus miembros
        antes de copiarla.
    </p>

    @foreach ($items as $coleccion)
        @php
            $tono = $coleccion->color ?: '#6366f1';
            $miCopia = $coleccion->clones->first();
            $dentro = $coleccion->relationLoaded('entities') ? $coleccion->entities : collect();
        @endphp

        <section class="overflow-hidden rounded-2xl border bg-slate-900/50" style="border-color: {{ $tono }}40">

            <div class="flex flex-wrap items-center gap-2.5 border-b border-slate-800 px-3 py-2">

                <span class="h-9 w-9 shrink-0 overflow-hidden rounded-lg border bg-slate-950"
                    style="border-color: {{ $tono }}55">
                    @if ($coleccion->image_url)
                        <img src="{{ $coleccion->image_url }}" alt="" loading="lazy" class="h-full w-full object-cover">
                    @else
                        <span class="flex h-full w-full items-center justify-center"
                            style="color: {{ $tono }}">{{ $coleccion->icon ?: '❒' }}</span>
                    @endif
                </span>

                <div class="min-w-0 flex-1">
                    <a href="{{ route('community.collections.show', $coleccion) }}"
                        class="block truncate text-[12px] font-black text-white transition hover:underline">
                        {{ $coleccion->name }}
                    </a>
                    @include('community.partials.atribucion', [
                        'autor' => $coleccion->creator,
                        'origen' => $coleccion->sourceCollection?->creator,
                    ])
                </div>

                <span class="shrink-0 font-mono text-[12px] font-black" style="color: {{ $tono }}">
                    {{ $coleccion->entities_count }}
                </span>

                @include('community.partials.copiar', [
                    'ruta' => route('community.collections.clone', $coleccion),
                    'esMio' => $coleccion->user_id === auth()->id(),
                    'miCopia' => $miCopia,
                    'seDeja' => (bool) $coleccion->allow_cloning,
                    'rutaMia' => $miCopia ? route('collections.show', $miCopia) : '#',
                    'compacto' => true,
                ])
            </div>

            @if ($coleccion->entities_count > 0 && $dentro->isNotEmpty())
                <div class="grid grid-cols-4 gap-1.5 p-3 sm:grid-cols-8 lg:grid-cols-12">
                    @foreach ($dentro->take(12) as $miembro)
                        <a href="{{ route('community.entities.show', $miembro) }}" title="{{ $miembro->name }}"
                            class="block aspect-square overflow-hidden rounded-lg border border-slate-800 bg-slate-950">
                            @if ($miembro->public_image_url ?: $miembro->image_url)
                                <img src="{{ $miembro->public_image_url ?: $miembro->image_url }}" alt=""
                                    loading="lazy" class="h-full w-full object-cover">
                            @else
                                <span class="flex h-full w-full items-center justify-center text-slate-800">◍</span>
                            @endif
                        </a>
                    @endforeach
                </div>
            @elseif ($coleccion->entities_count === 0)
                <p class="px-3 py-4 text-center text-[10px] text-slate-600">
                    Está vacía: copiarla no trae ninguna entidad.
                </p>
            @else
                <p class="px-3 py-4 text-center text-[10px] text-slate-600">
                    Tiene {{ $coleccion->entities_count }}
                    {{ $coleccion->entities_count === 1 ? 'entidad' : 'entidades' }}, pero ninguna es
                    pública, así que no se pueden enseñar aquí.
                </p>
            @endif
        </section>
    @endforeach
</div>
