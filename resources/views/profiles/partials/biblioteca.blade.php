@php
    /*
     * Lo que ha soltado de la Biblioteca.
     *
     * Una muestra, no el catalogo entero: el catalogo entero, con sus filtros y
     * sus formas de ver, esta en su perfil de biblioteca y se enlaza al final.
     * Aqui se enseña lo suficiente para hacerse una idea.
     */
@endphp

@if ($cifras['entidades'] + $cifras['colecciones'] + $cifras['atributos'] + $cifras['catalogos'] > 0)

    <section class="overflow-hidden rounded-2xl border border-indigo-500/25 bg-slate-900/50">

        <header class="flex flex-wrap items-center gap-2 border-b border-indigo-500/15 px-4 py-2.5">

            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-indigo-500/15 text-indigo-300">
                <x-omni-icon name="libro" size="h-4 w-4" />
            </span>

            <div class="min-w-0 flex-1">
                <h2 class="text-[13px] font-black text-white">Su biblioteca</h2>
                <p class="text-[10px] text-slate-500">
                    Lo que ha hecho público. Todo esto se puede copiar a la tuya.
                </p>
            </div>

            <a href="{{ route('community.creators.show', $creador->username) }}"
                class="shrink-0 text-[11px] font-black text-indigo-300 underline transition hover:text-white">
                Verla entera
            </a>
        </header>


        {{-- ---------- ENTIDADES ---------- --}}

        @if ($entidades->isNotEmpty())
            <div class="border-b border-slate-800/70 p-3">

                <p class="mb-2 flex items-baseline gap-1.5 text-[9px] font-black uppercase tracking-wider text-slate-600">
                    Entidades
                    <span class="font-mono text-indigo-400">{{ $cifras['entidades'] }}</span>
                </p>

                <div class="grid gap-2" style="grid-template-columns: repeat(auto-fill, minmax(110px, 1fr))">
                    @foreach ($entidades as $entidad)
                        <a href="{{ route('community.entities.show', $entidad) }}"
                            class="group overflow-hidden rounded-xl border border-slate-800 bg-slate-950 transition hover:-translate-y-0.5 hover:border-indigo-500/50"
                            title="{{ $entidad->display_label ?? $entidad->name }}">

                            <span class="block h-24 overflow-hidden bg-slate-900">
                                @if ($entidad->image_url)
                                    <img src="{{ $entidad->image_url }}" alt="" loading="lazy"
                                        class="h-full w-full object-cover transition duration-300 group-hover:scale-105">
                                @else
                                    <span class="flex h-full w-full items-center justify-center text-2xl text-slate-800">◍</span>
                                @endif
                            </span>

                            <span class="block px-2 py-1.5">
                                <span class="block truncate text-[11px] font-black text-slate-200">
                                    {{ $entidad->display_label ?? $entidad->name }}
                                </span>

                                @if ($entidad->entityType)
                                    <span class="block truncate text-[9px] text-slate-600">
                                        {{ $entidad->entityType->name }}
                                    </span>
                                @endif
                            </span>
                        </a>
                    @endforeach
                </div>
            </div>
        @endif


        {{-- ---------- COLECCIONES Y ATRIBUTOS ---------- --}}

        <div class="grid gap-px bg-slate-800 sm:grid-cols-2">

            @if ($colecciones->isNotEmpty())
                <div class="bg-slate-900/60 p-3">

                    <p class="mb-2 flex items-baseline gap-1.5 text-[9px] font-black uppercase tracking-wider text-slate-600">
                        Colecciones
                        <span class="font-mono text-emerald-400">{{ $cifras['colecciones'] }}</span>
                    </p>

                    <div class="space-y-1.5">
                        @foreach ($colecciones as $coleccion)
                            <a href="{{ route('community.collections.show', $coleccion) }}"
                                class="flex items-center gap-2.5 rounded-xl border border-slate-800 bg-slate-950 p-2 transition hover:border-emerald-500/50">

                                <span class="h-10 w-10 shrink-0 overflow-hidden rounded-lg bg-slate-900">
                                    @if ($coleccion->image_url)
                                        <img src="{{ $coleccion->image_url }}" alt="" loading="lazy" class="h-full w-full object-cover">
                                    @else
                                        <span class="flex h-full w-full items-center justify-center text-emerald-500/40">
                                            <x-omni-icon name="capas" size="h-4 w-4" />
                                        </span>
                                    @endif
                                </span>

                                <span class="min-w-0 flex-1">
                                    <span class="block truncate text-[12px] font-black text-slate-200">{{ $coleccion->name }}</span>
                                    <span class="block font-mono text-[9px] text-slate-600">
                                        {{ $coleccion->entities_count }}
                                        {{ $coleccion->entities_count === 1 ? 'entidad' : 'entidades' }}
                                    </span>
                                </span>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif

            @if ($atributos->isNotEmpty())
                <div class="bg-slate-900/60 p-3">

                    <p class="mb-2 flex items-baseline gap-1.5 text-[9px] font-black uppercase tracking-wider text-slate-600">
                        Atributos
                        <span class="font-mono text-cyan-400">{{ $cifras['atributos'] }}</span>
                    </p>

                    <div class="flex flex-wrap gap-1.5">
                        @foreach ($atributos as $atributo)
                            <a href="{{ route('community.attributes.show', $atributo) }}"
                                class="flex items-center gap-1.5 rounded-xl border border-slate-800 bg-slate-950 px-2 py-1.5 transition hover:border-cyan-500/50">

                                <span class="h-6 w-6 shrink-0 overflow-hidden rounded-md bg-slate-900">
                                    @if ($atributo->image_url)
                                        <img src="{{ $atributo->image_url }}" alt="" loading="lazy" class="h-full w-full object-cover">
                                    @else
                                        <span class="flex h-full w-full items-center justify-center text-cyan-500/40">
                                            <x-omni-icon name="capas" size="h-3 w-3" />
                                        </span>
                                    @endif
                                </span>

                                <span class="text-[11px] font-black text-slate-300">{{ $atributo->name }}</span>

                                @if ($atributo->options_count > 0)
                                    <span class="rounded bg-slate-800 px-1 font-mono text-[9px] font-black text-slate-500">
                                        {{ $atributo->options_count }}
                                    </span>
                                @endif
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>


        {{-- ---------- VALORES DE CATÁLOGO ---------- --}}

        @if ($catalogos->isNotEmpty())
            <div class="border-t border-slate-800/70 p-3">

                <p class="mb-2 flex items-baseline gap-1.5 text-[9px] font-black uppercase tracking-wider text-slate-600">
                    Valores de catálogo
                    <span class="font-mono text-violet-400">{{ $cifras['catalogos'] }}</span>
                </p>

                <div class="flex flex-wrap gap-1.5">
                    @foreach ($catalogos as $valor)
                        <a href="{{ route('community.catalogs.show', $valor) }}"
                            class="flex items-center gap-1.5 rounded-lg border border-slate-800 bg-slate-950 py-1 pl-1 pr-2 transition hover:border-violet-500/50"
                            title="{{ $valor->attribute?->name }} · {{ $valor->name }}">

                            <span class="h-6 w-6 shrink-0 overflow-hidden rounded bg-slate-900">
                                @if ($valor->image_url)
                                    <img src="{{ $valor->image_url }}" alt="" loading="lazy" class="h-full w-full object-cover">
                                @else
                                    <span class="flex h-full w-full items-center justify-center text-[10px] text-slate-700">◍</span>
                                @endif
                            </span>

                            <span class="text-[11px] font-bold text-slate-300">{{ $valor->name }}</span>
                        </a>
                    @endforeach
                </div>
            </div>
        @endif
    </section>
@endif
