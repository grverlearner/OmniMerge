@php
    /*
     * La portada del torneo.
     *
     * Lo primero es QUÉ es: su cara, su nombre, su contrato de
     * participantes y su estado. A la derecha las cifras del recorrido, que
     * son la respuesta corta —«una entrada, tres fases, cinco rutas, dos
     * finales»—.
     *
     * Debajo, si el recorrido tiene problemas, se dicen. Un torneo con un
     * agujero por el que se cae gente parece perfectamente normal en el
     * dibujo, y por eso conviene que lo diga aquí y no solo al simular.
     */
@endphp

<section class="relative mb-4 overflow-hidden rounded-3xl border border-amber-500/30 bg-slate-900/60">

    <div class="pointer-events-none absolute -right-24 -top-24 h-64 w-64 rounded-full bg-amber-500/10 blur-3xl"></div>

    <div class="relative flex flex-col gap-5 p-5 lg:flex-row lg:items-center">

        {{-- ============ LA CARA ============ --}}

        <div class="flex shrink-0 items-center gap-4">

            <div class="h-24 w-24 shrink-0 overflow-hidden rounded-2xl border border-amber-500/30 bg-slate-950 sm:h-28 sm:w-28">
                @if ($tournamentTemplate->image_url)
                    <img src="{{ $tournamentTemplate->image_url }}" alt=""
                        class="h-full w-full object-cover">
                @else
                    <div class="flex h-full w-full items-center justify-center text-4xl text-amber-400">⛯</div>
                @endif
            </div>

            <div class="min-w-0 lg:hidden">
                <p class="text-[10px] font-black uppercase tracking-[0.18em] text-amber-300">Torneo</p>
                <h1 class="mt-1 text-xl font-black leading-tight text-slate-100">
                    {{ $tournamentTemplate->name }}
                </h1>
            </div>

        </div>


        {{-- ============ IDENTIDAD ============ --}}

        <div class="min-w-0 flex-1">

            <div class="hidden lg:block">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="rounded-full bg-amber-500/15 px-2.5 py-1 text-[9px] font-black uppercase tracking-[0.18em] text-amber-300">
                        ⛯ Plantilla de torneo
                    </span>

                    <span class="font-mono text-[10px] font-bold text-slate-600">
                        {{ $tournamentTemplate->code }}
                    </span>
                </div>

                <h1 class="mt-1.5 text-2xl font-black leading-tight text-slate-100">
                    {{ $tournamentTemplate->name }}
                </h1>
            </div>

            @if ($tournamentTemplate->description)
                <p class="mt-2 max-w-2xl text-xs leading-relaxed text-slate-400">
                    {{ $tournamentTemplate->description }}
                </p>
            @endif

            <div class="mt-3 flex flex-wrap items-center gap-1.5">

                <span class="rounded-full px-2.5 py-1 text-[9px] font-black uppercase tracking-wider
                    {{ match ($tournamentTemplate->status) {
                        'ACTIVE' => 'bg-emerald-500/15 text-emerald-300',
                        'DRAFT' => 'bg-amber-500/15 text-amber-300',
                        default => 'bg-slate-700/50 text-slate-400',
                    } }}">
                    {{ $tournamentTemplate->status }}
                </span>

                <span class="rounded-full px-2.5 py-1 text-[9px] font-black uppercase tracking-wider
                    {{ match ($tournamentTemplate->visibility) {
                        'PUBLIC' => 'bg-violet-500/15 text-violet-300',
                        'UNLISTED' => 'bg-cyan-500/15 text-cyan-300',
                        default => 'bg-slate-700/50 text-slate-400',
                    } }}">
                    {{ $tournamentTemplate->visibility }}
                </span>

                <span class="rounded-full bg-slate-800/70 px-2.5 py-1 text-[9px] font-black uppercase tracking-wider text-slate-300">
                    @if ($tournamentTemplate->min_participants === $tournamentTemplate->max_participants)
                        {{ $tournamentTemplate->min_participants }} participantes
                    @else
                        {{ $tournamentTemplate->min_participants ?? '?' }}–{{ $tournamentTemplate->max_participants ?? '∞' }} participantes
                    @endif
                </span>

                @if ($tournamentTemplate->allow_byes)
                    <span class="rounded-full bg-slate-800/70 px-2.5 py-1 text-[9px] font-black uppercase tracking-wider text-slate-400">
                        admite descansos
                    </span>
                @endif

            </div>

        </div>


        {{-- ============ LAS CIFRAS DEL RECORRIDO ============ --}}

        <div class="grid shrink-0 grid-cols-2 gap-1.5 sm:grid-cols-4 lg:grid-cols-2 xl:grid-cols-4">

            @foreach ([
                ['Entradas', 'starts', 'text-emerald-300'],
                ['Fases', 'nodes', 'text-sky-300'],
                ['Rutas', 'connections', 'text-violet-300'],
                ['Finales', 'terminals', 'text-rose-300'],
            ] as [$etiqueta, $clave, $color])
                <div class="rounded-xl border border-slate-800 bg-slate-950/60 px-3 py-2 lg:min-w-[86px]">
                    <p class="text-[8px] font-black uppercase tracking-wider text-slate-500">{{ $etiqueta }}</p>
                    <p class="font-mono text-xl font-black {{ $color }}" x-text="stats.{{ $clave }} ?? 0"></p>
                </div>
            @endforeach

        </div>

    </div>


    {{-- ============ ESTADO DEL RECORRIDO ============ --}}

    <div class="relative flex flex-wrap items-center gap-2 border-t border-slate-800 bg-slate-950/40 px-5 py-2.5">

        <span class="rounded-full px-2.5 py-1 text-[9px] font-black uppercase tracking-wider"
            :class="validation.valid
                ? 'bg-emerald-500/15 text-emerald-300'
                : 'bg-rose-500/15 text-rose-300'"
            x-text="validation.valid
                ? '✓ el recorrido está completo'
                : (stats.errors ?? 0) + ' problemas en el recorrido'"></span>

        <template x-if="(stats.warnings ?? 0) > 0">
            <span class="rounded-full bg-amber-500/15 px-2.5 py-1 text-[9px] font-black uppercase tracking-wider text-amber-300">
                <span x-text="stats.warnings"></span> avisos
            </span>
        </template>

        <p class="mr-auto text-[10px] leading-relaxed text-slate-500">
            Esta ficha es de <span class="font-bold text-slate-400">solo lectura</span>.
            El recorrido se cambia en la Super Edición.
        </p>

        @can('update', $tournamentTemplate)
            <a href="{{ route('tournaments.super.show', $tournamentTemplate) }}"
                class="rounded-lg bg-amber-500 px-3 py-1.5 text-[11px] font-black text-slate-950 transition hover:bg-amber-400">
                ✎ Super Edición
            </a>
        @endcan

    </div>


    {{-- Los problemas, con su nombre --}}

    <template x-if="validation.errors.length || validation.warnings.length">
        <div class="relative grid gap-1 border-t border-slate-800 bg-slate-950/60 px-5 py-2 lg:grid-cols-2">

            <template x-for="(problema, i) in validation.errors" :key="'ce' + i">
                <p class="flex items-start gap-1.5 text-[9px] leading-relaxed text-rose-300">
                    <span class="shrink-0">✕</span>
                    <span x-text="problema.message"></span>
                </p>
            </template>

            <template x-for="(problema, i) in validation.warnings" :key="'cw' + i">
                <p class="flex items-start gap-1.5 text-[9px] leading-relaxed text-amber-300/80">
                    <span class="shrink-0">!</span>
                    <span x-text="problema.message"></span>
                </p>
            </template>

        </div>
    </template>

</section>
