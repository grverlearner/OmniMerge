@php
    /*
     * Tailwind solo genera las clases que encuentra escritas literalmente
     * en las plantillas, así que el acento del juego se traduce aquí a
     * cadenas completas en vez de construirse concatenando.
     */
    $palette = [
        'emerald' => [
            'gradient' => 'from-emerald-500 to-emerald-600',
            'shadow' => 'shadow-emerald-600/20',
            'chip' => 'bg-emerald-100 text-emerald-700',
            'hover' => 'hover:border-emerald-300',
            'hoverText' => 'hover:border-emerald-300 hover:text-emerald-700',
        ],
        'violet' => [
            'gradient' => 'from-violet-500 to-violet-600',
            'shadow' => 'shadow-violet-600/20',
            'chip' => 'bg-violet-100 text-violet-700',
            'hover' => 'hover:border-violet-300',
            'hoverText' => 'hover:border-violet-300 hover:text-violet-700',
        ],
        'amber' => [
            'gradient' => 'from-amber-500 to-amber-600',
            'shadow' => 'shadow-amber-600/20',
            'chip' => 'bg-amber-100 text-amber-700',
            'hover' => 'hover:border-amber-300',
            'hoverText' => 'hover:border-amber-300 hover:text-amber-700',
        ],
    ];
@endphp

<x-universe-layout :universe="$universe">

    <x-slot name="header">Juegos</x-slot>


    <div>
        <p class="text-xs font-black uppercase tracking-wider text-violet-600">
            {{ $universe->name }} · Juegos
        </p>

        <h2 class="mt-2 text-3xl font-black text-slate-900">Cómo se decide una batalla</h2>

        <p class="mt-2 max-w-2xl text-slate-500">
            El torneo decide quiénes se enfrentan. El juego decide quién gana.
            Cada torneo de este Universo puede usar uno distinto.
        </p>
    </div>


    <div class="mt-8 grid gap-5 lg:grid-cols-2">

        @foreach ($games as $game)

            @php
                $definition = $game['definition'];
                $record = $game['record'];
                $skin = $palette[$definition['accent'] ?? 'violet'] ?? $palette['violet'];
            @endphp

            <div
                class="flex flex-col overflow-hidden rounded-3xl border border-slate-200 bg-white transition {{ $skin['hover'] }} hover:shadow-xl hover:shadow-slate-200/60">

                {{-- CABECERA --}}

                <div class="flex items-start gap-4 border-b border-slate-100 p-6">

                    <div
                        class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br {{ $skin['gradient'] }} text-2xl shadow-lg {{ $skin['shadow'] }}">
                        {{ $definition['icon'] ?? '🎲' }}
                    </div>

                    <div class="min-w-0 flex-1">

                        <div class="flex flex-wrap items-center gap-2">

                            <h3 class="text-lg font-black text-slate-900">
                                {{ $definition['name'] }}
                            </h3>

                            @if ($record?->is_default)
                                <span
                                    class="rounded-full {{ $skin['chip'] }} px-2 py-0.5 text-[9px] font-black uppercase tracking-wide">
                                    Por defecto
                                </span>
                            @endif

                        </div>

                        <p class="mt-1.5 text-sm leading-relaxed text-slate-500">
                            {{ $definition['tagline'] }}
                        </p>

                    </div>

                </div>


                {{-- FICHA TÉCNICA --}}

                <div class="grid grid-cols-2 gap-px bg-slate-100 sm:grid-cols-3">

                    <div class="bg-white px-5 py-4">
                        <p class="text-[10px] font-black uppercase tracking-wider text-slate-400">
                            Participantes
                        </p>
                        <p class="mt-1 text-sm font-black text-slate-900">
                            {{ $definition['minimum_participants'] }}{{ $definition['maximum_participants'] ? '–' . $definition['maximum_participants'] : '+' }}
                        </p>
                    </div>

                    <div class="bg-white px-5 py-4">
                        <p class="text-[10px] font-black uppercase tracking-wider text-slate-400">
                            Tipo
                        </p>
                        <p class="mt-1 text-sm font-black text-slate-900">
                            {{ $definition['type_label'] }}
                        </p>
                    </div>

                    <div class="col-span-2 bg-white px-5 py-4 sm:col-span-1">
                        <p class="text-[10px] font-black uppercase tracking-wider text-slate-400">
                            Jugado aquí
                        </p>
                        <p class="mt-1 text-sm font-black text-slate-900">
                            {{ $game['encounters'] }}
                            <span class="text-[10px] font-bold text-slate-400">enfrentamientos</span>
                        </p>
                    </div>

                </div>


                {{-- ACCIONES --}}

                <div class="mt-auto flex items-center gap-2 border-t border-slate-100 p-4">

                    <a href="{{ route('universes.games.show', [$universe, $definition['key']]) }}"
                        class="rounded-xl bg-slate-950 px-4 py-2.5 text-xs font-black text-white transition hover:bg-slate-800">
                        Ver juego
                    </a>

                    @if (!$record?->is_default)
                        <form method="POST" action="{{ route('universes.games.default', $universe) }}">
                            @csrf
                            @method('PUT')

                            <input type="hidden" name="game_key" value="{{ $definition['key'] }}">

                            <button
                                class="rounded-xl border border-slate-200 px-4 py-2.5 text-xs font-black text-slate-600 transition {{ $skin['hoverText'] }}">
                                Usar por defecto
                            </button>
                        </form>
                    @endif

                </div>

            </div>
        @endforeach


        {{-- HUECO PARA LO QUE VIENE --}}

        <div
            class="flex flex-col items-center justify-center rounded-3xl border-2 border-dashed border-slate-200 p-10 text-center">

            <div class="text-4xl opacity-30">🎲</div>

            <h3 class="mt-4 text-base font-black text-slate-500">
                Más juegos, más adelante
            </h3>

            <p class="mx-auto mt-2 max-w-xs text-xs leading-relaxed text-slate-400">
                El motor está construido para que añadir un juego nuevo sea escribir
                sus reglas, no rehacer el sistema. Aparecerá aquí solo.
            </p>

        </div>

    </div>

</x-universe-layout>
