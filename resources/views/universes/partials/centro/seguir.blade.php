@php
    /*
     * Sigue donde lo dejaste.
     *
     * Los mundos ordenados por la ultima vez que se movieron, no por fecha de
     * creacion: al entrar al modulo lo que se quiere es volver a lo de ayer.
     */
@endphp

<section class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/50">

    <header class="flex flex-wrap items-center gap-2 border-b border-slate-800 px-4 py-2.5">

        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-violet-500/15 text-violet-300">
            <x-omni-icon name="brujula" size="h-4 w-4" />
        </span>

        <div class="min-w-0 flex-1">
            <h2 class="text-[13px] font-black text-white">Sigue donde lo dejaste</h2>
            <p class="text-[10px] text-slate-500">Por lo último que se movió en cada uno.</p>
        </div>

        <a href="{{ route('universes.index') }}"
            class="shrink-0 text-[11px] font-black text-violet-300 underline transition hover:text-white">
            Todos
        </a>
    </header>

    <div class="divide-y divide-slate-800/70">

        @foreach ($recientes as $mundo)
            @php [$tonoM, $textoM] = $tonosEstado[$mundo->status] ?? ['#94a3b8', $mundo->status]; @endphp

            <a href="{{ $mundo->home_url }}"
                class="flex items-center gap-2.5 px-3 py-2 transition hover:bg-slate-950/50">

                <span class="h-10 w-10 shrink-0 overflow-hidden rounded-lg border bg-slate-950"
                    style="border-color: {{ $tonoM }}55">
                    @if ($mundo->image_url)
                        <img src="{{ $mundo->image_url }}" alt="" loading="lazy" class="h-full w-full object-cover">
                    @else
                        <span class="flex h-full w-full items-center justify-center text-slate-700">
                            <x-omni-icon name="globo" size="h-4 w-4" />
                        </span>
                    @endif
                </span>

                <span class="min-w-0 flex-1">
                    <span class="block truncate text-[12px] font-black text-white">{{ $mundo->name }}</span>

                    <span class="flex flex-wrap items-center gap-x-1.5 font-mono text-[9px] text-slate-600">
                        <span style="color: {{ $tonoM }}">{{ $textoM }}</span>
                        <span>{{ $mundo->entities_count }} gente</span>
                        <span>{{ $mundo->tournament_instances_count }} jugadas</span>
                    </span>
                </span>

                <span class="shrink-0 text-right">
                    @if ($mundo->vivas_count > 0)
                        <span class="flex items-center justify-end gap-1 text-[10px] font-black text-emerald-300">
                            <span class="h-1.5 w-1.5 animate-pulse rounded-full bg-emerald-400"></span>
                            {{ $mundo->vivas_count }}
                        </span>
                    @endif

                    <span class="block font-mono text-[9px] text-slate-600">
                        @if ($mundo->activities_max_occurred_at)
                            {{ \Illuminate\Support\Carbon::parse($mundo->activities_max_occurred_at)->diffForHumans(null, true) }}
                        @else
                            sin mover
                        @endif
                    </span>
                </span>
            </a>
        @endforeach
    </div>
</section>
