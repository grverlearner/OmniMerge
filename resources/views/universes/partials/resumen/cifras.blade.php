{{--
    Las cifras del mundo, con los nombres que este universo usa para sus
    cosas. Es un bloque más del Resumen: se puede esconder o mover desde la
    configuración.
--}}

@php
    $ajustesCifras = $universe->ajustes();

    $cifras = [
        [$ajustesCifras->label('label_entities'), $statistics['entities'], '#a78bfa', route('universes.entities.index', $universe), $statistics['have_competed'] . ' han competido', 'entities'],
        [$ajustesCifras->label('label_seasons'), $statistics['seasons'], '#60a5fa', route('universes.seasons.index', $universe), $activeSeason ? 'la ' . $activeSeason->number . ' en curso' : 'ninguna en curso', 'seasons'],
        [$ajustesCifras->label('label_tournaments'), $statistics['tournaments'], '#22d3ee', route('universes.tournaments.index', $universe), 'definidos en este mundo', 'tournaments'],
        [$ajustesCifras->label('label_competitions'), $statistics['competitions'], '#34d399', route('universes.competitions.index', $universe), $statistics['competitions_done'] . ' terminadas', 'competitions'],
        ['En juego', $statistics['competitions_running'], '#fb7185', route('universes.competitions.index', $universe), 'ahora mismo', 'competitions'],
        ['Trofeos dados', $statistics['trophy_awards'], '#fbbf24', route('universes.trophies.index', $universe), $statistics['trophies'] . ' en la vitrina', 'trophies'],
    ];
@endphp

<section class="grid grid-cols-2 gap-2 sm:grid-cols-3 lg:grid-cols-6">
    @foreach ($cifras as [$etiqueta, $valor, $tono, $destino, $pie, $seccion])
        <a href="{{ $ajustesCifras->navVisible($seccion) ? $destino : '#' }}"
            class="group rounded-xl border border-slate-800 bg-slate-900/50 px-3 py-2 transition hover:-translate-y-0.5"
            onmouseover="this.style.borderColor='{{ $tono }}66'"
            onmouseout="this.style.borderColor=''">

            <span class="block font-mono text-2xl font-black leading-none"
                style="color: {{ $valor > 0 ? $tono : '#475569' }}">{{ $valor }}</span>

            <span class="mt-1 block truncate text-[9px] font-black uppercase tracking-wider text-slate-500">
                {{ $etiqueta }}
            </span>

            <span class="block truncate text-[9px] text-slate-600">{{ $pie }}</span>
        </a>
    @endforeach
</section>
