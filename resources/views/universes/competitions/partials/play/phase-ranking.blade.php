@php
    /*
     * El ranking de UNA fase — una sola lista, compacta y densa.
     *
     * La tira de arriba responde «cómo va la competición» y «cómo va el
     * universo». Esta responde la de en medio, que es la que se mira
     * mientras se juega: «cómo va ESTA fase». Un cuadro de dieciséis y una
     * liga de diez tienen cada uno su propio orden, y no es ninguno de los
     * otros dos.
     *
     * Es UNA. Antes había dos —esta y la lista general de la fase de grupos,
     * una encima de la otra, del mismo orden y ninguna completa—. Se quedó
     * esta porque sirve para todos los motores; lo que decía la otra —con qué
     * criterio está ordenada— se dice aquí, en la etiqueta.
     *
     * Cada ficha lleva lo justo para no tener que mirar abajo:
     *
     *   el puesto      lo que se viene a leer
     *   la cara        se reconoce antes que el nombre
     *   el nombre      pedido explícitamente
     *   los puntos     por qué está ahí
     *   de dónde sale  «B1» — su grupo y su puesto en él, si lo tiene
     *
     * De dónde salen las filas lo decide quien incluye este trozo, porque
     * depende del motor.
     *
     * $rows   [ ['position','name','image_url','points','origin'] ]
     * $note   con qué criterio está ordenada
     */

    $rows = collect($rows ?? [])->values();
@endphp

@if ($rows->isNotEmpty())

    <div class="border-b border-slate-800 bg-slate-950/40 px-5 py-2">

        <div class="mb-1.5 flex flex-wrap items-center gap-2">

            <span class="text-[9px] font-black uppercase tracking-[0.18em] text-slate-500">
                ▤ Cómo va esta fase
            </span>

            @if (! empty($note))
                <span class="rounded bg-slate-800 px-1.5 py-0.5 text-[8px] font-black text-slate-400">
                    {{ $note }}
                </span>
            @endif

            <span class="text-[9px] text-slate-600">
                · {{ $rows->count() }} {{ $rows->count() === 1 ? 'competidor' : 'competidores' }}
            </span>
        </div>

        <div class="arena-scroll overflow-x-auto">

            <div class="flex min-w-max items-center gap-1.5">

                @foreach ($rows as $fila)
                    <span @class([
                        'flex shrink-0 items-center gap-1.5 rounded-lg border py-0.5 pl-1 pr-1.5',
                        'border-amber-400/50 bg-amber-500/10' => $fila['position'] === 1,
                        'border-slate-700 bg-slate-950' => $fila['position'] === 2 || $fila['position'] === 3,
                        'border-slate-800 bg-slate-950' => $fila['position'] === null || $fila['position'] > 3,
                    ])
                        title="{{ $fila['position'] ? $fila['position'] . 'º · ' : '' }}{{ $fila['name'] }}{{ isset($fila['points']) ? ' · ' . $fila['points'] . ' pts' : '' }}{{ ! empty($fila['origin']) ? ' · ' . $fila['origin'] : '' }}">

                        <span @class([
                            'w-4 shrink-0 text-center font-mono text-[9px] font-black',
                            'text-amber-300' => $fila['position'] === 1,
                            'text-slate-300' => $fila['position'] === 2 || $fila['position'] === 3,
                            'text-slate-600' => $fila['position'] === null || $fila['position'] > 3,
                        ])>{{ $fila['position'] ?? '—' }}</span>

                        <span class="h-6 w-6 shrink-0 overflow-hidden rounded bg-slate-900">
                            @if ($fila['image_url'])
                                <img src="{{ $fila['image_url'] }}" alt="" loading="lazy"
                                    class="h-full w-full object-cover">
                            @endif
                        </span>

                        <span class="max-w-[104px] truncate text-[10px] font-bold text-slate-200">
                            {{ $fila['name'] }}
                        </span>

                        {{-- De dónde sale: «B1» es su grupo y su puesto en él --}}
                        @if (! empty($fila['origin']))
                            <span class="shrink-0 rounded bg-slate-900 px-1 font-mono text-[8px] font-black text-slate-500">
                                {{ $fila['origin'] }}
                            </span>
                        @endif

                        {{-- Por qué está ahí --}}
                        @if (isset($fila['points']))
                            <span class="shrink-0 font-mono text-[9px] font-black text-violet-300">
                                {{ $fila['points'] }}
                            </span>
                        @endif
                    </span>
                @endforeach

            </div>

        </div>

    </div>

@endif
