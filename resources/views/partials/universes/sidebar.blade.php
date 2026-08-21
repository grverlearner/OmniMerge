@php

    /*
     * El sidebar es contextual: si estamos dentro de un Universo
     * concreto muestra su navegación interna; si no, la del módulo.
     */
    $currentUniverse = $universe ?? null;

    if ($currentUniverse) {
        $currentUniverse->loadCount([
            'competitors',
            'seasons',
            'universeTournaments',
        ]);

        $currentSeason = $currentUniverse->activeSeason();
    }

    $itemBase = 'flex items-center gap-3 rounded-xl px-3 py-3 text-sm font-semibold transition';

    $itemActive = 'bg-violet-500 text-white shadow-lg shadow-violet-950/30';

    $itemIdle = 'text-slate-300 hover:bg-slate-900 hover:text-white';

@endphp


{{-- OVERLAY MÓVIL --}}

<div x-show="sidebarOpen" x-transition.opacity @click="sidebarOpen = false"
    class="fixed inset-0 z-40 bg-slate-950/60 backdrop-blur-sm lg:hidden">
</div>


{{-- SIDEBAR --}}

<aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
    class="fixed inset-y-0 left-0 z-50 flex w-72 flex-col bg-slate-950 text-slate-100 transition-transform duration-300 lg:translate-x-0">

    {{-- CABECERA --}}

    <div class="border-b border-slate-800 px-5 pb-5 pt-5">

        @if ($currentUniverse)

            <a href="{{ route('universes.index') }}"
                class="mb-4 flex items-center gap-2 rounded-xl border border-slate-800 bg-slate-900/70 px-3 py-2.5 text-xs font-bold text-slate-400 transition hover:border-violet-500/30 hover:bg-violet-500/10 hover:text-violet-300">
                ← Todos los Universos
            </a>


            <a href="{{ route('universes.show', $currentUniverse) }}"
                class="flex items-center gap-3 rounded-xl px-1 py-1">

                <div
                    class="flex h-11 w-11 shrink-0 items-center justify-center overflow-hidden rounded-xl bg-gradient-to-br from-violet-500 to-indigo-600 text-xl shadow-lg shadow-violet-950/30">

                    @if ($currentUniverse->image_url)
                        <img src="{{ $currentUniverse->image_url }}" alt="{{ $currentUniverse->name }}"
                            class="h-full w-full object-cover">
                    @else
                        🌌
                    @endif

                </div>


                <div class="min-w-0">
                    <p class="truncate text-base font-black tracking-tight text-white">
                        {{ $currentUniverse->name }}
                    </p>

                    <div class="mt-0.5 flex items-center gap-2">
                        <span class="font-mono text-[10px] text-slate-500">
                            {{ $currentUniverse->code }}
                        </span>

                        @if ($currentSeason)
                            <span class="h-1 w-1 rounded-full bg-slate-600"></span>

                            <span class="truncate text-[10px] font-bold text-violet-400">
                                Temporada {{ $currentSeason->number }}
                            </span>
                        @endif
                    </div>
                </div>

            </a>
        @else

            <a href="{{ route('hub') }}"
                class="mb-4 flex items-center gap-2 rounded-xl border border-slate-800 bg-slate-900/70 px-3 py-2.5 text-xs font-bold text-slate-400 transition hover:border-violet-500/30 hover:bg-violet-500/10 hover:text-violet-300">
                ← Centro OmniMerge
            </a>


            <a href="{{ route('universes.dashboard') }}" class="flex items-center gap-3 rounded-xl px-1 py-1">

                <div
                    class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-violet-500 to-indigo-600 text-xl shadow-lg shadow-violet-950/30">
                    🌌
                </div>

                <div class="min-w-0">
                    <p class="truncate text-lg font-black tracking-tight text-white">
                        OmniMerge
                    </p>

                    <div class="mt-0.5 flex items-center gap-2">
                        <span class="text-xs font-bold uppercase tracking-wider text-violet-400">
                            Universos
                        </span>
                    </div>
                </div>

            </a>
        @endif

    </div>


    {{-- NAVEGACIÓN --}}

    <nav class="flex-1 space-y-6 overflow-y-auto px-4 py-6">

        @if ($currentUniverse)

            {{-- ============================================ --}}
            {{-- NAVEGACIÓN DENTRO DE UN UNIVERSO --}}
            {{-- ============================================ --}}

            <div>
                <p class="mb-2 px-3 text-xs font-semibold uppercase tracking-wider text-slate-500">
                    Universo
                </p>

                <a href="{{ route('universes.show', $currentUniverse) }}"
                    class="{{ request()->routeIs('universes.show') ? $itemActive : $itemIdle }} {{ $itemBase }}">

                    <span class="text-lg">▦</span>

                    Resumen
                </a>
            </div>


            <div>
                <p class="mb-2 px-3 text-xs font-semibold uppercase tracking-wider text-slate-500">
                    Contenido
                </p>

                <div class="space-y-1">

                    <a href="{{ route('universes.competitors.index', $currentUniverse) }}"
                        class="{{ request()->routeIs('universes.competitors.*') ? $itemActive : $itemIdle }} {{ $itemBase }}">

                        <span class="text-lg">✦</span>

                        <span class="flex-1">
                            Competidores
                        </span>

                        <span
                            class="{{ request()->routeIs('universes.competitors.*') ? 'bg-white/20 text-white' : 'bg-slate-800 text-slate-400' }} rounded-full px-2 py-0.5 text-[10px] font-black">
                            {{ $currentUniverse->competitors_count }}
                        </span>
                    </a>


                    <a href="{{ route('universes.seasons.index', $currentUniverse) }}"
                        class="{{ request()->routeIs('universes.seasons.*') ? $itemActive : $itemIdle }} {{ $itemBase }}">

                        <span class="text-lg">◷</span>

                        <span class="flex-1">
                            Temporadas
                        </span>

                        <span
                            class="{{ request()->routeIs('universes.seasons.*') ? 'bg-white/20 text-white' : 'bg-slate-800 text-slate-400' }} rounded-full px-2 py-0.5 text-[10px] font-black">
                            {{ $currentUniverse->seasons_count }}
                        </span>
                    </a>


                    <a href="{{ route('universes.tournaments.index', $currentUniverse) }}"
                        class="{{ request()->routeIs('universes.tournaments.*') ? $itemActive : $itemIdle }} {{ $itemBase }}">

                        <span class="text-lg">🏆</span>

                        <span class="flex-1">
                            Torneos
                        </span>

                        <span
                            class="{{ request()->routeIs('universes.tournaments.*') ? 'bg-white/20 text-white' : 'bg-slate-800 text-slate-400' }} rounded-full px-2 py-0.5 text-[10px] font-black">
                            {{ $currentUniverse->universe_tournaments_count }}
                        </span>
                    </a>

                </div>
            </div>


            {{-- Dependen de competiciones jugadas: Fase 6 en adelante. --}}

            <div>
                <p class="mb-2 px-3 text-xs font-semibold uppercase tracking-wider text-slate-500">
                    Historia
                </p>

                <div class="space-y-1">

                    <div class="flex items-center gap-3 rounded-xl px-3 py-3 text-sm font-semibold text-slate-600">
                        <span class="text-lg">◇</span>

                        <span class="flex-1">
                            Recompensas
                        </span>

                        <span class="rounded-full bg-violet-500/10 px-2 py-1 text-[8px] font-black uppercase text-violet-500">
                            Próximo
                        </span>
                    </div>


                    <div class="flex items-center gap-3 rounded-xl px-3 py-3 text-sm font-semibold text-slate-600">
                        <span class="text-lg">📊</span>

                        <span class="flex-1">
                            Rankings
                        </span>

                        <span class="rounded-full bg-violet-500/10 px-2 py-1 text-[8px] font-black uppercase text-violet-500">
                            Próximo
                        </span>
                    </div>

                </div>
            </div>


            <div>
                <p class="mb-2 px-3 text-xs font-semibold uppercase tracking-wider text-slate-500">
                    Ajustes
                </p>

                <a href="{{ route('universes.edit', $currentUniverse) }}"
                    class="{{ request()->routeIs('universes.edit') ? $itemActive : $itemIdle }} {{ $itemBase }}">

                    <span class="text-lg">⚙</span>

                    Configuración
                </a>
            </div>
        @else

            {{-- ============================================ --}}
            {{-- NAVEGACIÓN DEL MÓDULO --}}
            {{-- ============================================ --}}

            <div>
                <p class="mb-2 px-3 text-xs font-semibold uppercase tracking-wider text-slate-500">
                    Principal
                </p>

                <div class="space-y-1">

                    <a href="{{ route('universes.dashboard') }}"
                        class="{{ request()->routeIs('universes.dashboard') ? $itemActive : $itemIdle }} {{ $itemBase }}">

                        <span class="text-lg">▦</span>

                        Dashboard
                    </a>


                    <a href="{{ route('universes.index') }}"
                        class="{{ request()->routeIs('universes.index') ? $itemActive : $itemIdle }} {{ $itemBase }}">

                        <span class="text-lg">🌌</span>

                        Mis Universos
                    </a>


                    <a href="{{ route('universes.create') }}"
                        class="{{ request()->routeIs('universes.create') ? $itemActive : $itemIdle }} {{ $itemBase }}">

                        <span class="text-lg">+</span>

                        Nuevo Universo
                    </a>

                </div>
            </div>
        @endif

    </nav>


    {{-- USUARIO --}}

    <div class="border-t border-slate-800 p-4">

        <a href="{{ route('profile.edit') }}"
            class="flex items-center gap-3 rounded-xl p-3 transition hover:bg-slate-900">

            <x-user-avatar :user="auth()->user()" size="md" />

            <div class="min-w-0 flex-1">
                <p class="truncate text-sm font-semibold text-white">
                    {{ auth()->user()->name }}
                </p>

                <p class="truncate text-xs text-slate-400">
                    {{ '@' . auth()->user()->username }}
                </p>
            </div>

            <span class="text-xs text-slate-600">→</span>
        </a>


        <div class="mt-2 space-y-1">

            <a href="{{ route('tournaments.dashboard') }}"
                class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-xs font-semibold text-slate-500 transition hover:bg-slate-900 hover:text-amber-300">
                🏆 Ir a Torneos
            </a>

            <a href="{{ route('dashboard') }}"
                class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-xs font-semibold text-slate-500 transition hover:bg-slate-900 hover:text-indigo-300">
                📚 Ir a Biblioteca
            </a>

        </div>
    </div>

</aside>
