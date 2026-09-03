@php
    /*
     * La ficha pública de una fase.
     *
     * Lo mismo que la de un torneo, con lo que distingue a una fase: por
     * dónde entra la gente, con qué motor se juega y por dónde sale. Una
     * fase se copia por sus SALIDAS —quién avanza y con qué criterio—, así
     * que eso se enseña entero, no resumido.
     */

    $tono = [
        'amber' => ['borde' => 'border-amber-500/40', 'texto' => 'text-amber-300', 'fondo' => 'bg-amber-500/10'],
        'violet' => ['borde' => 'border-violet-500/40', 'texto' => 'text-violet-300', 'fondo' => 'bg-violet-500/10'],
        'cyan' => ['borde' => 'border-cyan-500/40', 'texto' => 'text-cyan-300', 'fondo' => 'bg-cyan-500/10'],
        'emerald' => ['borde' => 'border-emerald-500/40', 'texto' => 'text-emerald-300', 'fondo' => 'bg-emerald-500/10'],
        'rose' => ['borde' => 'border-rose-500/40', 'texto' => 'text-rose-300', 'fondo' => 'bg-rose-500/10'],
        'sky' => ['borde' => 'border-sky-500/40', 'texto' => 'text-sky-300', 'fondo' => 'bg-sky-500/10'],
        'slate' => ['borde' => 'border-slate-700', 'texto' => 'text-slate-300', 'fondo' => 'bg-slate-800/60'],
    ][$phase->accent];

    $puedeCopiarse = auth()->user()?->can('duplicate', $phase) ?? false;

    $esMia = $phase->user_id === auth()->id();
@endphp

<x-tournament-layout surface="dark">

    <x-slot name="header">{{ $phase->name }}</x-slot>

    <div class="space-y-4">

        <header class="overflow-hidden rounded-2xl border {{ $tono['borde'] }} bg-slate-900/50">

            <div class="relative aspect-[21/7] overflow-hidden bg-slate-950">
                @if ($phase->image_url)
                    <img src="{{ $phase->image_url }}" alt="{{ $phase->name }}" class="h-full w-full object-cover">
                    <span class="absolute inset-0 bg-gradient-to-t from-slate-950 via-slate-950/40 to-transparent"></span>
                @else
                    <span class="flex h-full w-full items-center justify-center text-7xl opacity-10">
                        {{ $phase->display_icon }}
                    </span>
                @endif

                <a href="{{ route('tournaments.community.index', ['kind' => 'phases']) }}"
                    class="absolute left-4 top-4 rounded-lg border border-slate-700 bg-slate-950/80 px-3 py-1.5 text-[10px] font-black text-slate-300 backdrop-blur transition hover:border-violet-500 hover:text-violet-300">
                    ← Comunidad
                </a>
            </div>

            <div class="flex flex-wrap items-end gap-5 p-5">

                <div class="min-w-0 flex-1">

                    <div class="flex flex-wrap items-center gap-2">
                        <span class="flex items-center gap-1.5 rounded-lg border {{ $tono['borde'] }} {{ $tono['fondo'] }} px-2 py-1">
                            <span class="text-[11px]">{{ $phase->display_icon }}</span>
                            <span class="text-[9px] font-black uppercase tracking-wider {{ $tono['texto'] }}">
                                {{ $phase->type_label }}
                            </span>
                        </span>

                        <span class="font-mono text-[10px] text-slate-600">{{ $phase->code }}</span>

                        @if ($esMia)
                            <span class="rounded bg-amber-500/20 px-1.5 py-0.5 text-[8px] font-black uppercase tracking-wider text-amber-300">
                                es tuya
                            </span>
                        @endif
                    </div>

                    <h1 class="mt-2 text-2xl font-black tracking-tight text-white">{{ $phase->name }}</h1>

                    @if ($phase->summary)
                        <p class="mt-1 text-[13px] text-slate-400">{{ $phase->summary }}</p>
                    @endif

                    <a href="{{ route('tournaments.community.creator', $phase->user) }}"
                        class="mt-3 inline-flex items-center gap-2.5 rounded-xl border border-slate-800 bg-slate-950 px-3 py-2 transition hover:border-violet-500/50">
                        <x-user-avatar :user="$phase->user" size="sm" />

                        <span>
                            <span class="block text-[12px] font-black text-slate-200">{{ $phase->user->name }}</span>
                            <span class="block text-[10px] text-slate-500">
                                {{ $phase->user->headline ?: '@' . $phase->user->username }}
                            </span>
                        </span>
                    </a>

                </div>

                <div class="w-full max-w-xs shrink-0 space-y-2">

                    @if ($puedeCopiarse)
                        <form method="POST" action="{{ route('tournaments.phase-templates.duplicate', $phase) }}">
                            @csrf
                            <button type="submit"
                                class="flex w-full items-center justify-center gap-2 rounded-xl bg-violet-500 px-4 py-3 text-xs font-black text-white shadow-lg shadow-violet-950/40 transition hover:bg-violet-400">
                                <x-omni-icon name="capas" size="h-4 w-4" />
                                {{ $esMia ? 'Duplicar en mi espacio' : 'Llevármela a mi espacio' }}
                            </button>
                        </form>

                        <p class="text-center text-[10px] leading-4 text-slate-600">
                            Se copia con sus puertas y sus salidas, como borrador privado.
                        </p>
                    @else
                        <div class="rounded-xl border border-slate-800 bg-slate-950 px-4 py-3 text-center">
                            <p class="text-xs font-black text-slate-400">Solo para mirar</p>
                            <p class="mt-1 text-[10px] leading-4 text-slate-600">
                                Quien la publicó no ha permitido que se copie.
                            </p>
                        </div>
                    @endif

                    <div class="grid grid-cols-2 gap-2">
                        <span class="rounded-xl border border-slate-800 bg-slate-950 px-3 py-2 text-center">
                            <span class="block font-mono text-lg font-black text-slate-300">{{ $phase->views_count }}</span>
                            <span class="block text-[8px] font-black uppercase tracking-wider text-slate-600">Vistas</span>
                        </span>

                        <span class="rounded-xl border border-slate-800 bg-slate-950 px-3 py-2 text-center">
                            <span class="block font-mono text-lg font-black text-emerald-300">{{ $phase->clones_count }}</span>
                            <span class="block text-[8px] font-black uppercase tracking-wider text-slate-600">Copias</span>
                        </span>
                    </div>

                </div>

            </div>

        </header>


        <div class="grid gap-4 xl:grid-cols-[minmax(0,1fr)_320px] xl:items-start">

            <div class="space-y-4">

                <section class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/50">

                    <header class="flex items-center gap-3 border-b border-slate-800 px-5 py-3">
                        <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-cyan-500/15 text-cyan-300">
                            <x-omni-icon name="grafo" size="h-4 w-4" />
                        </span>

                        <div>
                            <h2 class="text-sm font-black text-white">Cómo funciona por dentro</h2>
                            <p class="text-[10px] text-slate-500">Por dónde entra, qué se juega y por dónde sale.</p>
                        </div>
                    </header>

                    <div class="space-y-4 p-5">

                        <div>
                            <p class="text-[9px] font-black uppercase tracking-wider text-cyan-400">⇥ Entra por</p>

                            @if ($phase->inputGates->isEmpty())
                                <p class="mt-1 text-[11px] text-slate-600">
                                    Sin puertas de entrada: ningún torneo puede conectarle gente todavía.
                                </p>
                            @else
                                <div class="mt-1.5 space-y-1.5">
                                    @foreach ($phase->inputGates as $puerta)
                                        <div class="flex items-center gap-3 rounded-xl border border-cyan-500/20 bg-cyan-500/5 px-3 py-2">
                                            <span class="min-w-0 flex-1">
                                                <span class="block truncate text-[12px] font-black text-slate-200">
                                                    {{ $puerta->name }}
                                                </span>
                                                @if ($puerta->description)
                                                    <span class="block truncate text-[10px] text-slate-500">
                                                        {{ $puerta->description }}
                                                    </span>
                                                @endif
                                            </span>

                                            @if ($puerta->exact_participants)
                                                <span class="shrink-0 rounded-lg bg-cyan-500/15 px-2 py-1 font-mono text-[11px] font-black text-cyan-300">
                                                    {{ $puerta->exact_participants }}
                                                </span>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>

                        <div class="flex items-center gap-2">
                            <span class="h-px flex-1 bg-slate-800"></span>
                            <span class="flex items-center gap-1.5 rounded-lg border {{ $tono['borde'] }} {{ $tono['fondo'] }} px-3 py-1.5">
                                <span class="text-sm">{{ $phase->display_icon }}</span>
                                <span class="text-[11px] font-black {{ $tono['texto'] }}">{{ $phase->type_label }}</span>
                            </span>
                            <span class="h-px flex-1 bg-slate-800"></span>
                        </div>

                        <div>
                            <p class="text-[9px] font-black uppercase tracking-wider text-violet-400">▲ Sale por</p>

                            @if ($phase->exits->isEmpty())
                                <p class="mt-1 text-[11px] text-slate-600">
                                    Sin salidas: desde aquí no avanza nadie a ninguna parte.
                                </p>
                            @else
                                <div class="mt-1.5 space-y-1.5">
                                    @foreach ($phase->exits as $salida)
                                        <div class="flex items-center gap-3 rounded-xl border border-violet-500/20 bg-violet-500/5 px-3 py-2">
                                            <span class="min-w-0 flex-1">
                                                <span class="block truncate text-[12px] font-black text-slate-200">
                                                    {{ $salida->name }}
                                                </span>
                                                @if ($salida->description)
                                                    <span class="block truncate text-[10px] text-slate-500">
                                                        {{ $salida->description }}
                                                    </span>
                                                @endif
                                            </span>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>

                    </div>

                </section>


                @if ($phase->description)
                    <section class="rounded-2xl border border-slate-800 bg-slate-900/50 p-5">
                        <p class="text-[9px] font-black uppercase tracking-wider text-slate-500">
                            Lo que cuenta quien la montó
                        </p>

                        <p class="mt-2 whitespace-pre-line text-[12px] leading-relaxed text-slate-400">
                            {{ $phase->description }}
                        </p>
                    </section>
                @endif

            </div>


            <aside class="space-y-4">

                <section class="rounded-2xl border border-slate-800 bg-slate-900/50 p-4">

                    <p class="text-[9px] font-black uppercase tracking-wider text-slate-500">De un vistazo</p>

                    <dl class="mt-2.5 space-y-2 text-[11px]">
                        @foreach ([['Motor', $phase->type_label], ['Compiten', $phase->participant_mode_label], ['Capacidad', $phase->exact_participants ? $phase->exact_participants . ' exactos' : ($phase->max_participants ? $phase->min_participants . '–' . $phase->max_participants : $phase->min_participants . '+')], ['Entradas', $phase->input_gates_count], ['Salidas', $phase->exits_count], ['La usan', $phase->tournament_phase_nodes_count . ' torneos'], ['BYE', $phase->allow_byes ? 'Permitido' : 'No'], ['Publicada', $phase->published_at?->locale('es')->diffForHumans() ?? '—']] as [$etiqueta, $valor])
                            <div class="flex items-baseline justify-between gap-3">
                                <dt class="text-slate-600">{{ $etiqueta }}</dt>
                                <dd class="font-black text-slate-200">{{ $valor }}</dd>
                            </div>
                        @endforeach
                    </dl>

                </section>


                @if ($more->isNotEmpty())
                    <section class="rounded-2xl border border-slate-800 bg-slate-900/50 p-4">

                        <p class="text-[9px] font-black uppercase tracking-wider text-slate-500">
                            Más de {{ $phase->user->name }}
                        </p>

                        <ul class="mt-2.5 space-y-1">
                            @foreach ($more as $otra)
                                <li>
                                    <a href="{{ route('tournaments.community.phase', $otra) }}"
                                        class="flex items-center gap-2 rounded-lg border border-slate-800 bg-slate-950/60 px-2 py-1.5 transition hover:border-slate-700 hover:bg-slate-900">

                                        <span class="text-[13px]">{{ $otra->display_icon }}</span>

                                        <span class="min-w-0 flex-1">
                                            <span class="block truncate text-[11px] font-black text-slate-200">
                                                {{ $otra->name }}
                                            </span>
                                            <span class="block truncate text-[9px] text-slate-600">
                                                {{ $otra->type_label }} · {{ $otra->clones_count }} copias
                                            </span>
                                        </span>
                                    </a>
                                </li>
                            @endforeach
                        </ul>

                        <a href="{{ route('tournaments.community.creator', $phase->user) }}"
                            class="mt-2.5 block rounded-xl border border-slate-800 px-3 py-2 text-center text-[11px] font-black text-slate-400 transition hover:border-violet-500 hover:text-violet-300">
                            Ver todo lo suyo →
                        </a>

                    </section>
                @endif

            </aside>

        </div>

    </div>

</x-tournament-layout>
