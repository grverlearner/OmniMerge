@php
    /*
     * Lo que va a pasar, antes de que pase.
     *
     * Esta es la pieza que le faltaba a la pantalla. Cambiar el tipo de
     * cuarenta entidades a la vez es una acción que no se deshace, y hasta
     * ahora se hacía a ciegas: se elegía un valor, se pulsaba y se veía el
     * resultado después.
     *
     * Aquí, en cada acción, se ven las entidades afectadas con su cara y con
     * el cambio dibujado —lo que tienen ahora, tachado, y lo que van a tener—.
     * Un mismo componente para las siete acciones: si cada una tuviera el
     * suyo, la mitad acabaría sin enseñar nada.
     *
     *   $antes   expresión JS que devuelve el valor actual de una entidad
     *            (recibe `entity`); null si la acción no sustituye nada
     *   $despues expresión JS con el valor que quedará; null si no aplica
     *   $verbo   qué se va a hacer, en una palabra
     *   $tono    el color de la acción
     */

    $antes = $antes ?? null;
    $despues = $despues ?? null;
    $verbo = $verbo ?? 'Se aplicará a';
    $tono = $tono ?? 'indigo';

    $tonos = [
        'indigo' => ['borde' => 'border-indigo-500/30', 'fondo' => 'bg-indigo-500/5', 'texto' => 'text-indigo-300'],
        'violet' => ['borde' => 'border-violet-500/30', 'fondo' => 'bg-violet-500/5', 'texto' => 'text-violet-300'],
        'emerald' => ['borde' => 'border-emerald-500/30', 'fondo' => 'bg-emerald-500/5', 'texto' => 'text-emerald-300'],
        'cyan' => ['borde' => 'border-cyan-500/30', 'fondo' => 'bg-cyan-500/5', 'texto' => 'text-cyan-300'],
        'sky' => ['borde' => 'border-sky-500/30', 'fondo' => 'bg-sky-500/5', 'texto' => 'text-sky-300'],
        'amber' => ['borde' => 'border-amber-500/30', 'fondo' => 'bg-amber-500/5', 'texto' => 'text-amber-300'],
        'rose' => ['borde' => 'border-rose-500/30', 'fondo' => 'bg-rose-500/5', 'texto' => 'text-rose-300'],
    ];

    $t = $tonos[$tono] ?? $tonos['indigo'];
@endphp

<div class="rounded-xl border {{ $t['borde'] }} {{ $t['fondo'] }} p-3">

    <p class="flex items-center gap-2 text-[9px] font-black uppercase tracking-wider {{ $t['texto'] }}">
        <x-omni-icon name="historial" size="h-3.5 w-3.5" />
        {{ $verbo }} <span class="font-mono" x-text="selectedCount"></span>
        <span x-text="selectedCount === 1 ? 'entidad' : 'entidades'"></span>
    </p>

    {{-- Las diez primeras: enseñar cuarenta caras no informa más que enseñar diez --}}
    <div class="mt-2 max-h-48 space-y-1 overflow-y-auto pr-1">

        <template x-for="entity in selectedEntities.slice(0, 10)" :key="`prev-${entity.id}`">
            <div class="flex items-center gap-2 rounded-lg border border-slate-800 bg-slate-950/70 px-2 py-1.5">

                <span class="h-7 w-7 shrink-0 overflow-hidden rounded-md border border-slate-800 bg-slate-900">
                    <template x-if="entity.image_url">
                        <img :src="entity.image_url" alt="" loading="lazy" class="h-full w-full object-cover">
                    </template>

                    <template x-if="!entity.image_url">
                        <span class="flex h-full w-full items-center justify-center text-[10px] font-black text-slate-600"
                            x-text="entity.name.charAt(0).toUpperCase()"></span>
                    </template>
                </span>

                <span class="min-w-0 flex-1 truncate text-[11px] font-bold text-slate-200" x-text="entity.name"></span>

                @if ($antes !== null || $despues !== null)
                    <span class="flex shrink-0 items-center gap-1.5 text-[10px]">

                        @if ($antes !== null)
                            <span class="max-w-[8rem] truncate text-slate-600 line-through"
                                x-text="({{ $antes }}) || '—'"></span>

                            <span class="{{ $t['texto'] }}">→</span>
                        @endif

                        @if ($despues !== null)
                            <span class="max-w-[8rem] truncate font-black {{ $t['texto'] }}"
                                x-text="({{ $despues }}) || '—'"></span>
                        @endif
                    </span>
                @endif

            </div>
        </template>

        <p x-show="selectedCount > 10" x-cloak class="px-2 pt-1 text-[10px] text-slate-600">
            …y <span x-text="selectedCount - 10"></span> más.
        </p>

        <p x-show="selectedCount === 0" class="px-2 py-3 text-center text-[10px] text-slate-600">
            No has marcado ninguna todavía.
        </p>

    </div>

</div>
