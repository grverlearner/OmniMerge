{{--
    La gente, partida por lo que publica. Los tres montones del mapa del
    directorio, en pequeño: los cuatro que más han publicado de cada tipo.
--}}

<section class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/50">

    <div class="flex flex-wrap items-center gap-2 border-b border-slate-800 px-3 py-2.5">
        <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-500/15 text-emerald-300">
            <x-omni-icon name="usuario" size="h-4 w-4" />
        </span>
        <div class="mr-auto">
            <h2 class="text-[14px] font-black text-white">Quién lo hace</h2>
            <p class="text-[10px] text-slate-500">Creadores completos, de biblioteca y de torneos</p>
        </div>
        <a href="{{ route('community.creators.index') }}"
            class="flex items-center gap-1.5 rounded-xl border border-emerald-500/40 px-3 py-1.5 text-[11px] font-black text-emerald-300 transition hover:bg-emerald-500 hover:text-slate-950">
            <x-omni-icon name="orbita" size="h-3.5 w-3.5" />
            Ver el directorio
        </a>
    </div>

    <div class="grid gap-px bg-slate-800 lg:grid-cols-3">
        @foreach (['completo', 'biblioteca', 'torneos'] as $clave)
            @php
                [$etiquetaTipo, $tonoTipo, $ayudaTipo] = $tiposCreador[$clave];
                $grupo = $creadoresPorTipo[$clave];
                $cuantos = $creadores->where('tipo_creador', $clave)->count();
            @endphp

            <div class="bg-slate-900 p-3">
                <a href="{{ route('community.creators.index', ['tipo' => $clave]) }}" class="group flex items-center gap-2">
                    <span class="h-6 w-1 rounded-full" style="background-color: {{ $tonoTipo }}"></span>
                    <span class="min-w-0 flex-1">
                        <span class="block text-[12px] font-black" style="color: {{ $tonoTipo }}">{{ $etiquetaTipo }}</span>
                        <span class="block truncate text-[9px] text-slate-500">{{ $ayudaTipo }}</span>
                    </span>
                    <span class="font-mono text-lg font-black" style="color: {{ $cuantos > 0 ? $tonoTipo : '#475569' }}">{{ $cuantos }}</span>
                    <span class="text-slate-600 transition group-hover:text-slate-300"><x-omni-icon name="chevron-derecha" size="h-3.5 w-3.5" /></span>
                </a>

                @if ($grupo->isEmpty())
                    <p class="mt-3 rounded-xl border border-dashed border-slate-800 py-5 text-center text-[10px] text-slate-600">Nadie de este tipo todavía</p>
                @else
                    <div class="mt-2.5 space-y-1.5">
                        @foreach ($grupo as $persona)
                            @php $susCaras = $carasCreadores[$persona->id] ?? collect(); @endphp
                            <a href="{{ route('profiles.show', $persona->username) }}"
                                class="flex items-center gap-2 rounded-xl border border-slate-800 bg-slate-950/60 p-1.5 transition hover:border-slate-600">
                                <span class="h-10 w-10 shrink-0 overflow-hidden rounded-xl border-2 bg-slate-950" style="border-color: {{ $tonoTipo }}77">
                                    @if ($persona->avatar_url)
                                        <img src="{{ $persona->avatar_url }}" alt="" loading="lazy" class="h-full w-full object-cover">
                                    @else
                                        <span class="flex h-full w-full items-center justify-center text-[12px] font-black text-slate-500">{{ $persona->initials }}</span>
                                    @endif
                                </span>
                                <span class="min-w-0 flex-1">
                                    <span class="block truncate text-[12px] font-black text-slate-200">{{ $persona->name }}</span>
                                    <span class="block font-mono text-[9px] text-slate-500">
                                        <span style="color: #818cf8">{{ $persona->pub_biblioteca }}</span> biblio ·
                                        <span style="color: #fbbf24">{{ $persona->pub_torneos }}</span> torneos
                                    </span>
                                </span>
                                <span class="hidden shrink-0 -space-x-1.5 sm:flex">
                                    @foreach ($susCaras->take(3) as $cara)
                                        <span class="h-6 w-6 overflow-hidden rounded-full border border-slate-900 bg-slate-900">
                                            <img src="{{ $cara->image_url }}" alt="" loading="lazy" class="h-full w-full object-cover">
                                        </span>
                                    @endforeach
                                </span>
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>
        @endforeach
    </div>
</section>
