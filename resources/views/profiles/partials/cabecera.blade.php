@php
    /*
     * Quien es.
     *
     * El mosaico de sus caras publicas detras, porque a un creador se le
     * reconoce por lo que ha hecho antes que por su nombre.
     *
     * Y las cifras que importan de una persona, no de un catalogo: cuanto ha
     * soltado, y -la unica que no depende de el- cuanto se han llevado de aqui.
     */
@endphp

<section class="relative overflow-hidden rounded-2xl border border-violet-500/25 bg-slate-900/50">

    @if ($mosaico->isNotEmpty())
        <div class="pointer-events-none absolute inset-0 grid grid-cols-6 opacity-[0.15] sm:grid-cols-9 lg:grid-cols-[repeat(18,minmax(0,1fr))]">
            @foreach ($mosaico as $cara)
                <span class="block aspect-square overflow-hidden">
                    <img src="{{ $cara->image_url }}" alt="" loading="lazy" class="h-full w-full object-cover">
                </span>
            @endforeach
        </div>

        <div class="pointer-events-none absolute inset-0"
            style="background: linear-gradient(105deg, #020617 24%, #020617dd 56%, #020617aa 100%)"></div>
    @endif


    <div class="relative flex flex-wrap items-center gap-4 p-4">

        <span class="h-24 w-24 shrink-0 overflow-hidden rounded-2xl border-2 border-violet-500/50 bg-slate-950">
            @if ($creador->avatar_url)
                <img src="{{ $creador->avatar_url }}" alt="" class="h-full w-full object-cover">
            @else
                <span class="flex h-full w-full items-center justify-center text-[26px] font-black text-violet-300">
                    {{ $creador->initials }}
                </span>
            @endif
        </span>

        <div class="min-w-0 flex-1">

            <div class="flex flex-wrap items-center gap-1.5">
                <span class="font-mono text-[11px] font-black text-violet-400">&#64;{{ $creador->username }}</span>

                @if ($esMio)
                    <span class="rounded-lg bg-violet-500/15 px-2 py-0.5 text-[9px] font-black uppercase tracking-wider text-violet-300">
                        eres tú
                    </span>
                @endif

                @if (! $creador->isPublicProfile())
                    {{--
                        Solo lo ve el dueño, y conviene que lo vea: esta mirando
                        una pagina que nadie mas puede abrir.
                    --}}
                    <span class="rounded-lg bg-amber-500/15 px-2 py-0.5 text-[9px] font-black uppercase tracking-wider text-amber-300"
                        title="Nadie más puede ver esta página">
                        perfil privado
                    </span>
                @endif
            </div>

            <h1 class="mt-0.5 text-2xl font-black leading-tight tracking-tight text-white">
                {{ $creador->name }}
            </h1>

            @if ($creador->headline)
                <p class="mt-0.5 text-[12px] font-bold text-slate-300">{{ $creador->headline }}</p>
            @endif

            @if ($creador->bio)
                <p class="mt-1 max-w-2xl text-[11px] leading-relaxed text-slate-400">{{ $creador->bio }}</p>
            @endif

            <div class="mt-1.5 flex flex-wrap items-center gap-x-3 gap-y-1">

                @if ($creador->location)
                    <span class="flex items-center gap-1 text-[10px] text-slate-500">
                        <x-omni-icon name="brujula" size="h-3 w-3" />
                        {{ $creador->location }}
                    </span>
                @endif

                @if ($creador->website)
                    <a href="{{ $creador->website }}" target="_blank" rel="noopener nofollow"
                        class="flex items-center gap-1 text-[10px] font-bold text-violet-400 transition hover:text-violet-200">
                        <x-omni-icon name="globo" size="h-3 w-3" />
                        {{ preg_replace('#^https?://(www\.)?#', '', $creador->website) }}
                    </a>
                @endif

                <span class="flex items-center gap-1 text-[10px] text-slate-600">
                    <x-omni-icon name="calendario" size="h-3 w-3" />
                    aquí desde {{ $creador->created_at->format('m/Y') }}
                </span>
            </div>
        </div>


        <div class="flex shrink-0 flex-wrap gap-1.5">
            @if ($esMio)
                <a href="{{ route('profile.edit') }}"
                    class="flex items-center gap-1.5 rounded-xl bg-violet-500 px-3 py-2 text-[11px] font-black text-white transition hover:bg-violet-400">
                    <x-omni-icon name="engranaje" size="h-3.5 w-3.5" />
                    Editar mi perfil
                </a>
            @else
                <a href="{{ route('community.creators.show', $creador->username) }}"
                    class="flex items-center gap-1.5 rounded-xl border border-slate-700 bg-slate-950/70 px-3 py-2 text-[11px] font-black text-slate-300 transition hover:border-violet-500 hover:text-violet-300">
                    <x-omni-icon name="libro" size="h-3.5 w-3.5" />
                    Su biblioteca
                </a>
            @endif
        </div>
    </div>


    {{-- ---------- LO QUE HA SOLTADO ---------- --}}

    <div class="relative grid grid-cols-2 gap-px border-t border-slate-800 bg-slate-800 sm:grid-cols-4 lg:grid-cols-8">

        @php
            $cuadros = [
                ['Entidades', $cifras['entidades'], '#818cf8'],
                ['Colecciones', $cifras['colecciones'], '#34d399'],
                ['Atributos', $cifras['atributos'], '#22d3ee'],
                ['Catálogo', $cifras['catalogos'], '#a78bfa'],
                ['Torneos', $cifras['torneos'], '#fbbf24'],
                ['Fases', $cifras['fases'], '#f472b6'],
                ['Le han copiado', $cifras['copiado'], '#fb7185'],
                ['Visitas', $cifras['vistas'], '#94a3b8'],
            ];
        @endphp

        @foreach ($cuadros as [$etiqueta, $valor, $tono])
            <span class="bg-slate-900/80 px-2.5 py-2">
                <span class="block font-mono text-xl font-black leading-none"
                    style="color: {{ $valor > 0 ? $tono : '#475569' }}">{{ $valor }}</span>
                <span class="mt-1 block truncate text-[9px] font-black uppercase tracking-wider text-slate-600">
                    {{ $etiqueta }}
                </span>
            </span>
        @endforeach
    </div>
</section>
