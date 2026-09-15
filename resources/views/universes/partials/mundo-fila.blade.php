@php
    /*
     * Un universo en una linea.
     *
     * Para cuando hay muchos y lo que se busca es uno concreto: cara, nombre,
     * lo que tiene y si pide algo, sin gastar una pantalla por mundo.
     */

    $caras = $carasPorUniverso[$mundo->id] ?? collect();
    $temporada = $temporadaPorUniverso[$mundo->id] ?? null;

    [$tono, $textoEstado] = $tonosEstado[$mundo->status] ?? ['#94a3b8', $mundo->status];

    $atascado = $mundo->atascadas_count > 0;
@endphp

<div class="flex flex-wrap items-center gap-3 border-b border-slate-800/70 px-3 py-2 transition hover:bg-slate-950/50">

    <span class="h-1.5 w-1.5 shrink-0 rounded-full" style="background-color: {{ $tono }}"
        title="{{ $textoEstado }}"></span>

    <a href="{{ $mundo->home_url }}"
        class="h-11 w-11 shrink-0 overflow-hidden rounded-xl border bg-slate-950"
        style="border-color: {{ $mundo->accent }}">
        @if ($mundo->image_url)
            <img src="{{ $mundo->image_url }}" alt="" loading="lazy" class="h-full w-full object-cover" style="object-position: {{ $mundo->ajustes()->coverPosition() }}">
        @else
            <span class="flex h-full w-full items-center justify-center" style="color: {{ $mundo->accent }}; background-color: {{ $mundo->accent }}1a">
                <x-omni-icon :name="$mundo->ajustes()->icon()" size="h-4 w-4" />
            </span>
        @endif
    </a>

    <div class="min-w-0 flex-1">
        <div class="flex flex-wrap items-center gap-1.5">
            <a href="{{ $mundo->home_url }}"
                class="truncate text-[13px] font-black text-white transition hover:text-violet-300">
                {{ $mundo->name }}
            </a>

            <span class="font-mono text-[9px] text-slate-600">{{ $mundo->code }}</span>

            @if ($temporada)
                <span class="rounded bg-violet-500/15 px-1.5 py-0.5 font-mono text-[9px] font-black text-violet-300">
                    T{{ $temporada->number }}
                </span>
            @endif

            @if ($mundo->vivas_count > 0)
                <span class="flex items-center gap-1 rounded bg-emerald-500/15 px-1.5 py-0.5 text-[9px] font-black text-emerald-300">
                    <span class="h-1 w-1 animate-pulse rounded-full bg-emerald-400"></span>
                    {{ $mundo->vivas_count }}
                </span>
            @endif

            @if ($atascado)
                <a href="{{ route('universes.competitions.index', $mundo) }}"
                    class="rounded bg-rose-500/15 px-1.5 py-0.5 text-[9px] font-black text-rose-300 transition hover:bg-rose-500 hover:text-white"
                    title="Competiciones paradas esperando una decisión">
                    {{ $mundo->atascadas_count }} atascada{{ $mundo->atascadas_count === 1 ? '' : 's' }}
                </a>
            @endif
        </div>

        {{-- Su gente, en pequeño: el mundo se reconoce por las caras --}}
        @if ($caras->isNotEmpty())
            <div class="mt-1 flex -space-x-1.5">
                @foreach ($caras->take(8) as $cara)
                    <span class="h-5 w-5 shrink-0 overflow-hidden rounded-full border border-slate-900 bg-slate-950">
                        <img src="{{ $cara->image_url }}" alt="" loading="lazy" class="h-full w-full object-cover">
                    </span>
                @endforeach

                @if ($mundo->entities_count > 8)
                    <span class="flex h-5 items-center rounded-full border border-slate-800 bg-slate-950 px-1.5 font-mono text-[8px] font-black text-slate-500">
                        +{{ $mundo->entities_count - 8 }}
                    </span>
                @endif
            </div>
        @endif
    </div>

    <span class="hidden shrink-0 items-center gap-3 font-mono text-[10px] text-slate-500 sm:flex">
        @foreach ([[$mundo->entities_count, 'gente', '#a78bfa'], [$mundo->universe_tournaments_count, 'torneos', '#22d3ee'], [$mundo->tournament_instances_count, 'jugadas', '#34d399']] as [$valor, $etiqueta, $tonoC])
            <span class="text-center">
                <span class="block font-black" style="color: {{ $valor > 0 ? $tonoC : '#475569' }}">{{ $valor }}</span>
                <span class="block text-[8px] uppercase tracking-wider text-slate-700">{{ $etiqueta }}</span>
            </span>
        @endforeach
    </span>

    <span class="hidden w-20 shrink-0 text-right font-mono text-[9px] text-slate-600 lg:block">
        @if ($mundo->activities_max_occurred_at)
            {{ \Illuminate\Support\Carbon::parse($mundo->activities_max_occurred_at)->diffForHumans(null, true) }}
        @else
            sin mover
        @endif
    </span>

    <a href="{{ $mundo->home_url }}"
        class="shrink-0 rounded-lg border border-slate-800 px-2.5 py-1.5 text-[10px] font-black text-slate-400 transition hover:border-violet-500 hover:text-violet-300">
        Entrar
    </a>
</div>
