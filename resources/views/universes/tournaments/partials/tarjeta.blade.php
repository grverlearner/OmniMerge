@php
    /*
     * Un torneo del universo, en formato ficha.
     *
     * Lo que hace falta para decidir si abrirlo: con qué juego se resuelve, qué
     * formato usa, cada cuánto se repite, cuántas veces se ha jugado y quién
     * ganó la última. Un torneo sin ediciones lo dice en rojo: configurar no es
     * lanzar.
     */

    $ultima = $torneo->instances->first();

    /* La ultima edicion que llego a tener un primero. */
    $edicionCampeona = $torneo->instances->first(fn($e) => $e->participants->isNotEmpty());

    $campeon = $edicionCampeona?->participants->first();

    $cara = $campeon?->universeEntity;

    $definicionJuego = $juegos[$torneo->game_key] ?? null;
@endphp

<article class="group flex flex-col overflow-hidden rounded-2xl border bg-slate-900/50 transition duration-300 hover:-translate-y-0.5"
    style="border-color: {{ $torneo->instances_count > 0 ? $tono . '40' : '#1e293b' }}">

    {{-- ============ SU PORTADA ============ --}}

    <a href="{{ route('universes.tournaments.show', [$universe, $torneo]) }}"
        class="relative block aspect-[16/9] overflow-hidden bg-slate-950">

        @if ($torneo->image_url)
            <img src="{{ $torneo->image_url }}" alt="{{ $torneo->name }}" loading="lazy"
                class="h-full w-full object-cover transition duration-500 group-hover:scale-105">
            <span class="absolute inset-x-0 bottom-0 h-2/5 bg-gradient-to-t from-slate-950 to-transparent"></span>
        @else
            <span class="flex h-full w-full items-center justify-center text-4xl"
                style="color: {{ $tono }}66; background: radial-gradient(120% 90% at 50% 0%, {{ $tono }}22, transparent 70%)">
                {{ $definicionJuego['icon'] ?? '🏆' }}
            </span>
        @endif

        <span class="absolute left-2 top-2 flex items-center gap-1 rounded-lg border px-1.5 py-0.5 text-[9px] font-black uppercase tracking-wider"
            style="border-color: {{ $tono }}55; background-color: #020617d9; color: {{ $tono }}">
            {{ $definicionJuego['icon'] ?? '◈' }}
            {{ $definicionJuego['name'] ?? ($torneo->game_key ?: 'Sin juego') }}
        </span>

        <span class="absolute right-2 top-2 rounded px-1.5 py-0.5 text-[9px] font-black uppercase tracking-wider {{ $tonoEstado[$torneo->status] ?? 'bg-slate-800 text-slate-500' }}">
            {{ $torneo->status_label }}
        </span>

        <span class="absolute inset-x-0 bottom-0 p-2">
            <span class="block truncate text-[14px] font-black text-white">{{ $torneo->name }}</span>
        </span>
    </a>


    {{-- ============ QUÉ ES ============ --}}

    <div class="flex-1 space-y-2 p-3">

        <p class="line-clamp-2 text-[10px] leading-relaxed text-slate-500">
            {{ $torneo->description ?: 'Sin descripción.' }}
        </p>

        <div class="flex flex-wrap items-center gap-1">
            <span class="rounded border border-slate-800 px-1.5 py-0.5 text-[9px] font-bold text-slate-400"
                title="La plantilla de torneo que define su recorrido">
                {{ $torneo->tournamentTemplate?->name ?? 'Sin plantilla' }}
            </span>

            <span class="rounded border border-slate-800 px-1.5 py-0.5 text-[9px] font-bold text-slate-500"
                title="Cada cuánto nace una edición nueva">
                {{ $torneo->recurrence_label }}
            </span>

            @if ($torneo->rewards_count > 0)
                <span class="rounded border border-amber-500/30 px-1.5 py-0.5 text-[9px] font-bold text-amber-300"
                    title="Recompensas que reparte">
                    {{ $torneo->rewards_count }} {{ $torneo->rewards_count === 1 ? 'premio' : 'premios' }}
                </span>
            @endif

            @if ($torneo->modifiers_count > 0)
                <span class="rounded border border-slate-800 px-1.5 py-0.5 text-[9px] font-bold text-slate-500"
                    title="Modificadores que cambian cómo se juega">
                    {{ $torneo->modifiers_count }} {{ $torneo->modifiers_count === 1 ? 'modificador' : 'modificadores' }}
                </span>
            @endif
        </div>


        {{-- El campeón de la última edición, que es lo que da vida a la ficha --}}
        @if ($campeon)
            <div class="flex items-center gap-2 rounded-xl border p-1.5"
                style="border-color: {{ $tono }}40; background-color: {{ $tono }}10">

                <span class="shrink-0 text-sm">🏆</span>

                <span class="h-8 w-8 shrink-0 overflow-hidden rounded-lg border border-slate-800 bg-slate-950">
                    @if ($cara?->image_url)
                        <img src="{{ $cara->image_url }}" alt="" loading="lazy" class="h-full w-full object-cover">
                    @else
                        <span class="flex h-full w-full items-center justify-center text-slate-700">◍</span>
                    @endif
                </span>

                <span class="min-w-0 flex-1">
                    <span class="block text-[8px] font-black uppercase tracking-wider text-slate-600">
                        ganó la última
                    </span>
                    <span class="block truncate text-[11px] font-black" style="color: {{ $tono }}">
                        {{ $cara?->display_label ?? $campeon->display_name ?? $campeon->name ?? 'Sin nombre' }}
                    </span>
                </span>
            </div>
        @elseif ($ultima)
            <p class="rounded-xl border border-slate-800 px-2 py-1.5 text-center text-[10px] text-slate-500">
                Su última edición todavía no ha terminado.
            </p>
        @else
            <p class="rounded-xl border border-dashed border-rose-500/30 px-2 py-1.5 text-center text-[10px] text-rose-300/70">
                Configurado, pero nunca lanzado.
            </p>
        @endif
    </div>


    {{-- ============ QUÉ SE PUEDE HACER ============ ---}}

    <div class="flex items-center gap-1 border-t border-slate-800 px-2 py-1.5">

        <a href="{{ route('universes.tournaments.show', [$universe, $torneo]) }}"
            class="rounded-lg px-2 py-1 text-[10px] font-black text-slate-400 transition hover:text-white">
            Ver
        </a>

        @can('update', $universe)
            <a href="{{ route('universes.tournaments.edit', [$universe, $torneo]) }}"
                class="rounded-lg px-2 py-1 text-[10px] font-black text-slate-400 transition hover:text-amber-300">
                ✎ Editar
            </a>

            <a href="{{ route('universes.tournaments.rewards', [$universe, $torneo]) }}"
                class="rounded-lg px-2 py-1 text-[10px] font-black text-slate-400 transition hover:text-amber-300">
                Premios
            </a>
        @endcan

        <span class="ml-auto font-mono text-[10px] font-black"
            style="color: {{ $torneo->instances_count > 0 ? $tono : '#f43f5e' }}"
            title="Ediciones jugadas">
            ×{{ $torneo->instances_count }}
        </span>
    </div>

</article>
