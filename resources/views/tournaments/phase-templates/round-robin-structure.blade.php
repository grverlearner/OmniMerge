@php
    /*
     * Estructura de Round Robin.
     *
     * El calendario es deterministico: con N participantes y C ciclos, las
     * jornadas son las que son. Por eso esto no es un editor, es una
     * previsualizacion fiel — con caras prestadas para leerla mejor.
     */

    $rounds = collect($preview['rounds'] ?? []);
    $byCycle = $rounds->groupBy('cycle');
@endphp

<x-app-layout>

    <x-slot name="header">
        Estructura · {{ $phaseTemplate->name }}
    </x-slot>

    @include('tournaments.phase-templates.partials.workspace-navigation', [
        'current' => 'structure',
        'phaseTemplate' => $phaseTemplate,
        'settings' => $settings,
    ])


    <section class="mt-5 rounded-3xl border border-slate-200 bg-white p-6">

        <p class="text-[10px] font-black uppercase tracking-[0.18em] text-cyan-600">Calendario</p>

        <h2 class="mt-2 text-2xl font-black text-slate-900">Todos contra todos, jornada a jornada</h2>

        <p class="mt-2 max-w-3xl text-sm leading-relaxed text-slate-500">
            Aquí no hay nada que dibujar a mano: el calendario sale solo del número de
            participantes y de ciclos. Esto es exactamente lo que se jugará.
        </p>


        {{-- TANTEAR CON OTRO NUMERO --}}

        <form method="GET" class="mt-5 flex flex-wrap items-end gap-3">

            <div>
                <label class="text-[10px] font-black uppercase tracking-wider text-slate-500">
                    Previsualizar con
                </label>

                <input type="number" name="participants" min="2" max="128" value="{{ $participants }}"
                    class="mt-1.5 w-32 rounded-xl border-slate-300 text-sm focus:border-cyan-400 focus:ring-cyan-400">
            </div>

            <button class="rounded-xl bg-slate-950 px-5 py-2.5 text-xs font-black text-white hover:bg-slate-800">
                Recalcular
            </button>

            <p class="text-[10px] text-slate-400">
                Solo cambia lo que ves. No se guarda nada.
            </p>

        </form>

    </section>


    @if (!($preview['valid'] ?? false))

        <section class="mt-5 rounded-3xl border border-red-200 bg-red-50 p-6">
            <h3 class="text-base font-black text-red-800">El calendario no se puede calcular</h3>
            <ul class="mt-2 space-y-1">
                @foreach ($preview['errors'] ?? [] as $error)
                    <li class="text-sm font-bold text-red-700">· {{ $error }}</li>
                @endforeach
            </ul>
        </section>
    @else

        {{-- CIFRAS --}}

        <section class="mt-5 grid grid-cols-2 gap-3 lg:grid-cols-6">

            @foreach ([
                ['Participantes', $preview['participants'], 'cyan'],
                ['Ciclos', $preview['cycles'], 'cyan'],
                ['Jornadas por ciclo', $preview['rounds_per_cycle'], 'slate'],
                ['Jornadas totales', $preview['total_rounds'], 'slate'],
                ['Enfrentamientos', $preview['total_series'], 'indigo'],
                ['Formato', 'BO' . ($preview['default_best_of'] ?? 1), 'indigo'],
            ] as [$label, $value, $tone])

                <div @class([
                    'rounded-2xl border p-4',
                    'border-cyan-200 bg-cyan-50' => $tone === 'cyan',
                    'border-indigo-200 bg-indigo-50' => $tone === 'indigo',
                    'border-slate-200 bg-white' => $tone === 'slate',
                ])>
                    <p class="text-[9px] font-black uppercase tracking-wider text-slate-500">{{ $label }}</p>
                    <p class="mt-1 text-2xl font-black text-slate-900">{{ $value }}</p>
                </div>
            @endforeach

        </section>


        @if ($preview['is_odd'] ?? false)
            <div class="mt-4 rounded-2xl border border-violet-200 bg-violet-50 p-4">
                <p class="text-xs font-bold text-violet-800">
                    Con un número impar de participantes, en cada jornada descansa uno.
                    El calendario ya lo reparte para que a nadie le toque de más.
                </p>
            </div>
        @endif


        {{-- LOS PARTICIPANTES --}}

        <section class="mt-6 rounded-3xl border border-slate-200 bg-white p-6">

            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <p class="text-[10px] font-black uppercase tracking-[0.18em] text-cyan-600">Reparto</p>
                    <h3 class="mt-1.5 text-lg font-black text-slate-900">Quién compite</h3>
                </div>

                <span class="rounded-full bg-amber-100 px-3 py-1 text-[10px] font-black text-amber-700">
                    Caras prestadas · no se inscribe a nadie
                </span>
            </div>

            <div class="mt-5 grid grid-cols-3 gap-2 sm:grid-cols-6 lg:grid-cols-8 xl:grid-cols-10">

                @for ($seed = 1; $seed <= ($preview['participants'] ?? 0); $seed++)

                    @php $member = $castBySeed->get($seed); @endphp

                    <div class="overflow-hidden rounded-xl border border-slate-200 bg-slate-50">

                        <div class="relative aspect-square bg-slate-200">

                            @if ($member['image_url'] ?? null)
                                <img src="{{ $member['image_url'] }}" alt="" class="h-full w-full object-cover">
                            @else
                                <div class="flex h-full w-full items-center justify-center text-lg opacity-30">✦</div>
                            @endif

                            <span class="absolute left-1 top-1 rounded bg-slate-950/80 px-1.5 py-0.5 font-mono text-[9px] font-black text-white">
                                {{ $seed }}
                            </span>

                            <div class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-slate-950 via-slate-950/80 to-transparent px-1.5 pb-1 pt-3">
                                <p class="truncate text-[9px] font-black leading-tight text-white">
                                    {{ $member['name'] ?? ('Seed ' . $seed) }}
                                </p>
                            </div>

                        </div>

                    </div>
                @endfor

            </div>

        </section>


        {{-- CALENDARIO --}}

        @foreach ($byCycle as $cycle => $cycleRounds)

            <section class="mt-6">

                <div class="mb-3 flex items-center gap-3">
                    <span class="rounded-full bg-cyan-600 px-3 py-1 text-[10px] font-black text-white">
                        Ciclo {{ $cycle }}
                    </span>
                    <p class="text-xs text-slate-500">
                        {{ $cycleRounds->count() }} jornadas ·
                        {{ $cycleRounds->sum('series_count') }} enfrentamientos
                    </p>
                </div>

                <div class="grid gap-3 lg:grid-cols-2 2xl:grid-cols-3">

                    @foreach ($cycleRounds as $round)

                        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white">

                            <div class="flex items-center justify-between gap-2 border-b border-slate-100 bg-slate-50 px-4 py-2">

                                <p class="text-[11px] font-black text-slate-900">{{ $round['label'] }}</p>

                                <span class="font-mono text-[9px] text-slate-400">
                                    {{ $round['series_count'] }} enfrentamientos
                                </span>

                            </div>

                            <div class="divide-y divide-slate-100">

                                @foreach ($round['pairings'] as $pairing)

                                    @php
                                        $a = $castBySeed->get($pairing['seed_a']);
                                        $b = $castBySeed->get($pairing['seed_b']);
                                    @endphp

                                    <div class="flex items-center gap-2 px-3 py-2">

                                        {{-- Lado A --}}
                                        <div class="flex min-w-0 flex-1 items-center justify-end gap-1.5">
                                            <span class="min-w-0 truncate text-right text-[10px] font-black text-slate-800">
                                                {{ $a['name'] ?? ('Seed ' . $pairing['seed_a']) }}
                                            </span>

                                            <div class="h-7 w-7 shrink-0 overflow-hidden rounded-lg bg-slate-100 ring-1 ring-slate-200">
                                                @if ($a['image_url'] ?? null)
                                                    <img src="{{ $a['image_url'] }}" alt="" class="h-full w-full object-cover">
                                                @else
                                                    <span class="flex h-full w-full items-center justify-center font-mono text-[9px] font-black text-slate-400">
                                                        {{ $pairing['seed_a'] }}
                                                    </span>
                                                @endif
                                            </div>
                                        </div>

                                        <span class="shrink-0 text-[9px] font-black text-slate-300">vs</span>

                                        {{-- Lado B --}}
                                        <div class="flex min-w-0 flex-1 items-center gap-1.5">
                                            <div class="h-7 w-7 shrink-0 overflow-hidden rounded-lg bg-slate-100 ring-1 ring-slate-200">
                                                @if ($b['image_url'] ?? null)
                                                    <img src="{{ $b['image_url'] }}" alt="" class="h-full w-full object-cover">
                                                @else
                                                    <span class="flex h-full w-full items-center justify-center font-mono text-[9px] font-black text-slate-400">
                                                        {{ $pairing['seed_b'] }}
                                                    </span>
                                                @endif
                                            </div>

                                            <span class="min-w-0 truncate text-[10px] font-black text-slate-800">
                                                {{ $b['name'] ?? ('Seed ' . $pairing['seed_b']) }}
                                            </span>
                                        </div>

                                    </div>
                                @endforeach

                                {{-- Quien descansa --}}
                                @if ($round['rest_seed'] ?? null)

                                    @php $rest = $castBySeed->get($round['rest_seed']); @endphp

                                    <div class="flex items-center gap-2 bg-violet-50 px-3 py-1.5">

                                        <div class="h-5 w-5 shrink-0 overflow-hidden rounded bg-violet-100">
                                            @if ($rest['image_url'] ?? null)
                                                <img src="{{ $rest['image_url'] }}" alt="" class="h-full w-full object-cover">
                                            @endif
                                        </div>

                                        <span class="truncate text-[9px] font-black text-violet-700">
                                            Descansa {{ $rest['name'] ?? ('Seed ' . $round['rest_seed']) }}
                                        </span>

                                    </div>
                                @endif

                            </div>

                        </div>
                    @endforeach

                </div>

            </section>
        @endforeach


        {{-- SALIDAS --}}

        <section class="mt-8 rounded-3xl border border-slate-200 bg-white p-6">

            <div class="flex flex-wrap items-center justify-between gap-3">

                <div>
                    <p class="text-[10px] font-black uppercase tracking-[0.18em] text-violet-600">Contrato</p>
                    <h3 class="mt-1.5 text-lg font-black text-slate-900">Por dónde se sale de aquí</h3>
                </div>

                <a href="{{ route('tournaments.round-robin.io', $phaseTemplate) }}"
                    class="rounded-xl bg-slate-950 px-4 py-2.5 text-xs font-black text-white hover:bg-slate-800">
                    Configurar salidas →
                </a>

            </div>

            @if ($phaseExits->isEmpty())
                <p class="mt-4 text-sm text-slate-500">
                    Todavía no hay ninguna puerta de salida: nadie continuará a otra fase.
                </p>
            @else
                <div class="mt-4 grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($phaseExits as $exit)
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-3">

                            <p class="text-sm font-black text-slate-900">{{ $exit->name }}</p>

                            <div class="mt-2 flex flex-wrap gap-1.5">
                                <span class="rounded-full bg-white px-2 py-0.5 text-[9px] font-black text-slate-600">
                                    {{ $exit->selector_type }}
                                </span>
                                <span class="rounded-full bg-white px-2 py-0.5 text-[9px] font-black text-slate-600">
                                    {{ $exit->exit_timing }}
                                </span>
                                @if ($exit->status !== 'ACTIVE')
                                    <span class="rounded-full bg-slate-200 px-2 py-0.5 text-[9px] font-black text-slate-600">
                                        Inactiva
                                    </span>
                                @endif
                            </div>

                        </div>
                    @endforeach
                </div>
            @endif

        </section>
    @endif

</x-app-layout>
