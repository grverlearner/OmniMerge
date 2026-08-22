@php
    $palette = [
        'emerald' => [
            'gradient' => 'from-emerald-500 to-emerald-600',
            'shadow' => 'shadow-emerald-600/20',
            'chip' => 'bg-emerald-100 text-emerald-700',
            'soft' => 'bg-emerald-50 border-emerald-200',
            'text' => 'text-emerald-700',
            'dot' => 'bg-emerald-500',
        ],
        'violet' => [
            'gradient' => 'from-violet-500 to-violet-600',
            'shadow' => 'shadow-violet-600/20',
            'chip' => 'bg-violet-100 text-violet-700',
            'soft' => 'bg-violet-50 border-violet-200',
            'text' => 'text-violet-700',
            'dot' => 'bg-violet-500',
        ],
        'amber' => [
            'gradient' => 'from-amber-500 to-amber-600',
            'shadow' => 'shadow-amber-600/20',
            'chip' => 'bg-amber-100 text-amber-700',
            'soft' => 'bg-amber-50 border-amber-200',
            'text' => 'text-amber-700',
            'dot' => 'bg-amber-500',
        ],
    ];

    $skin = $palette[$definition['accent'] ?? 'violet'] ?? $palette['violet'];
@endphp

<x-universe-layout :universe="$universe">

    <x-slot name="header">{{ $definition['name'] }}</x-slot>


    <a href="{{ route('universes.games.index', $universe) }}"
        class="text-xs font-black text-slate-400 hover:text-violet-600">
        ← Juegos
    </a>


    {{-- PORTADA --}}

    <div class="mt-5 overflow-hidden rounded-3xl border border-slate-200 bg-white">

        <div class="flex flex-col gap-5 p-7 sm:flex-row sm:items-start">

            <div
                class="flex h-20 w-20 shrink-0 items-center justify-center rounded-3xl bg-gradient-to-br {{ $skin['gradient'] }} text-4xl shadow-xl {{ $skin['shadow'] }}">
                {{ $definition['icon'] ?? '🎲' }}
            </div>

            <div class="min-w-0 flex-1">

                <div class="flex flex-wrap items-center gap-2">

                    <h2 class="text-3xl font-black text-slate-900">
                        {{ $definition['name'] }}
                    </h2>

                    @if ($record?->is_default)
                        <span
                            class="rounded-full {{ $skin['chip'] }} px-2.5 py-1 text-[9px] font-black uppercase tracking-wide">
                            Juego por defecto
                        </span>
                    @endif

                </div>

                <p class="mt-3 max-w-2xl leading-relaxed text-slate-600">
                    {{ $definition['description'] }}
                </p>

            </div>

        </div>


        {{-- FICHA TÉCNICA --}}

        <div class="grid gap-px border-t border-slate-100 bg-slate-100 sm:grid-cols-2 lg:grid-cols-4">

            <div class="bg-white px-6 py-5">
                <p class="text-[10px] font-black uppercase tracking-wider text-slate-400">Participantes</p>
                <p class="mt-1 text-lg font-black text-slate-900">
                    {{ $definition['minimum_participants'] }}{{ $definition['maximum_participants'] ? ' – ' . $definition['maximum_participants'] : ' o más' }}
                </p>
            </div>

            <div class="bg-white px-6 py-5">
                <p class="text-[10px] font-black uppercase tracking-wider text-slate-400">Tipo</p>
                <p class="mt-1 text-lg font-black text-slate-900">{{ $definition['type_label'] }}</p>
            </div>

            <div class="bg-white px-6 py-5">
                <p class="text-[10px] font-black uppercase tracking-wider text-slate-400">Formato</p>
                <p class="mt-1 text-sm font-black leading-snug text-slate-900">
                    {{ $definition['interaction_label'] }}
                </p>
            </div>

            <div class="bg-white px-6 py-5">
                <p class="text-[10px] font-black uppercase tracking-wider text-slate-400">Empates</p>
                <p class="mt-1 text-lg font-black text-slate-900">
                    {{ $definition['allows_draws'] ? 'Permitidos' : 'No' }}
                </p>
            </div>

        </div>

    </div>


    <div class="mt-6 grid gap-6 lg:grid-cols-3">

        {{-- REGLAS --}}

        <div class="lg:col-span-2">

            <div class="rounded-3xl border border-slate-200 bg-white p-6">

                <h3 class="text-lg font-black text-slate-900">Reglas</h3>

                <ol class="mt-4 space-y-3">

                    @foreach ($definition['rules'] as $index => $rule)
                        <li class="flex gap-3">

                            <span
                                class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full {{ $skin['chip'] }} text-[10px] font-black">
                                {{ $index + 1 }}
                            </span>

                            <span class="text-sm leading-relaxed text-slate-600">{{ $rule }}</span>

                        </li>
                    @endforeach

                </ol>


                <div class="mt-6 grid gap-3 sm:grid-cols-2">

                    <div class="rounded-2xl border {{ $skin['soft'] }} p-4">
                        <p class="text-[10px] font-black uppercase tracking-wider {{ $skin['text'] }}">
                            Cómo se gana
                        </p>
                        <p class="mt-1.5 text-sm font-semibold leading-snug text-slate-700">
                            {{ $definition['win_condition'] }}
                        </p>
                    </div>

                    @if (!empty($definition['tiebreak']))
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                            <p class="text-[10px] font-black uppercase tracking-wider text-slate-500">
                                Desempate
                            </p>
                            <p class="mt-1.5 text-sm font-semibold leading-snug text-slate-700">
                                {{ $definition['tiebreak'] }}
                            </p>
                        </div>
                    @endif

                </div>

            </div>


            {{-- ÚLTIMOS ENFRENTAMIENTOS --}}

            <div class="mt-6 rounded-3xl border border-slate-200 bg-white p-6">

                <h3 class="text-lg font-black text-slate-900">Últimos enfrentamientos</h3>

                @if ($recentEncounters->isEmpty())

                    <p class="mt-3 text-sm text-slate-500">
                        Todavía no se ha jugado ningún enfrentamiento con este juego
                        en {{ $universe->name }}.
                    </p>
                @else

                    <div class="mt-4 space-y-2">

                        @foreach ($recentEncounters as $encounter)

                            <div class="rounded-2xl border border-slate-100 bg-slate-50/60 p-4">

                                <div class="flex flex-wrap items-center gap-2 text-[10px] font-bold text-slate-400">

                                    <span>{{ $encounter->tournamentInstance?->name ?? 'Competición borrada' }}</span>

                                    @if ($encounter->phase_name)
                                        <span class="h-1 w-1 rounded-full bg-slate-300"></span>
                                        <span>{{ $encounter->phase_name }}</span>
                                    @endif

                                    <span class="h-1 w-1 rounded-full bg-slate-300"></span>
                                    <span>Enfrentamiento {{ $encounter->encounter_number }}</span>

                                </div>


                                <div class="mt-2.5 flex flex-wrap items-center gap-2">

                                    @foreach ($encounter->participants as $participant)

                                        <div
                                            class="flex items-center gap-2 rounded-xl border px-3 py-1.5 {{ $participant->is_winner ? $skin['soft'] : 'border-slate-200 bg-white' }}">

                                            <span
                                                class="text-xs font-black {{ $participant->is_winner ? 'text-slate-900' : 'text-slate-500' }}">
                                                {{ $participant->name }}
                                            </span>

                                            <span
                                                class="font-mono text-xs font-black {{ $participant->is_winner ? $skin['text'] : 'text-slate-400' }}">
                                                {{ $participant->display_value }}
                                            </span>

                                            @if ($participant->is_winner)
                                                <span class="text-[10px]">🏆</span>
                                            @endif

                                        </div>
                                    @endforeach

                                </div>

                            </div>
                        @endforeach

                    </div>
                @endif

            </div>

        </div>


        {{-- LATERAL --}}

        <div class="space-y-6">

            {{-- ESTADÍSTICAS QUE USA --}}

            <div class="rounded-3xl border border-slate-200 bg-white p-6">

                <h3 class="text-base font-black text-slate-900">Estadísticas que usa</h3>

                <p class="mt-1.5 text-xs leading-relaxed text-slate-500">
                    Cada competidor del Universo tiene las suyas. No son atributos de
                    la Biblioteca: viven aquí.
                </p>

                <div class="mt-4 space-y-2.5">

                    @foreach ($definition['stats'] as $stat)

                        <div class="rounded-2xl border border-slate-100 bg-slate-50/60 px-4 py-3">

                            <div class="flex items-center gap-2">
                                <span class="h-1.5 w-1.5 rounded-full {{ $skin['dot'] }}"></span>
                                <p class="text-sm font-black text-slate-900">{{ $stat['label'] }}</p>
                            </div>

                            @if (!empty($stat['help']))
                                <p class="mt-1 pl-3.5 text-xs text-slate-500">{{ $stat['help'] }}</p>
                            @endif

                        </div>
                    @endforeach

                </div>

            </div>


            {{-- QUIÉN DESTACA --}}

            <div class="rounded-3xl border border-slate-200 bg-white p-6">

                <h3 class="text-base font-black text-slate-900">Quién destaca</h3>

                @if ($leaders->isEmpty())

                    <p class="mt-2 text-xs text-slate-500">
                        Nadie ha jugado todavía a este juego aquí.
                    </p>
                @else

                    <div class="mt-4 space-y-1">

                        @foreach ($leaders as $index => $leader)

                            <a href="{{ route('universes.entities.show', [$universe, $leader]) }}"
                                class="flex items-center gap-3 rounded-xl px-2 py-2 transition hover:bg-slate-50">

                                <span class="w-4 text-center text-[10px] font-black text-slate-400">
                                    {{ $index + 1 }}
                                </span>

                                <div
                                    class="h-8 w-8 shrink-0 overflow-hidden rounded-lg bg-slate-100 ring-1 ring-slate-200">
                                    @if ($leader->image_url)
                                        <img src="{{ $leader->image_url }}" alt="" class="h-full w-full object-cover">
                                    @endif
                                </div>

                                <p class="min-w-0 flex-1 truncate text-xs font-black text-slate-900">
                                    {{ $leader->display_label }}
                                </p>

                                <span class="shrink-0 font-mono text-[10px] font-black {{ $skin['text'] }}">
                                    {{ $leader->encounters_won }}/{{ $leader->encounters_played }}
                                </span>

                            </a>
                        @endforeach

                    </div>


                    <p class="mt-3 text-[10px] leading-relaxed text-slate-400">
                        Enfrentamientos ganados sobre jugados, solo en este juego.
                    </p>
                @endif

            </div>

        </div>

    </div>

</x-universe-layout>
