@php
    /*
     * Cuando no hay ningun mundo todavia.
     *
     * Lo que habia en su sitio era una «hoja de ruta» que prometia resultados y
     * rankings «cuando las competiciones puedan jugarse de verdad» -y llevan
     * jugandose desde hace tiempo-. Una promesa caducada es peor que no decir
     * nada, porque enseña una version del producto que ya no existe.
     *
     * Lo que hay ahora es lo unico util aqui: explicar que ES un universo, con
     * el recorrido dibujado, y llevar a crearlo.
     */
@endphp

<section class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/50">

    <div class="border-b border-slate-800 px-4 py-3">
        <h2 class="text-[15px] font-black text-white">Qué es un universo</h2>
        <p class="mt-0.5 max-w-2xl text-[11px] leading-relaxed text-slate-500">
            Un mundo con su propia gente, su propio calendario y su propia clasificación. La
            misma entidad de tu Biblioteca puede ser la número uno en uno y la última en otro:
            <strong class="text-slate-400">los mundos no se mezclan</strong>.
        </p>
    </div>


    {{-- El recorrido, dibujado --}}
    <div class="overflow-x-auto p-4">

        <svg viewBox="0 0 900 190" class="mx-auto block h-auto w-full max-w-3xl">

            <defs>
                <marker id="flecha" markerWidth="8" markerHeight="8" refX="7" refY="4" orient="auto">
                    <path d="M 0 0 L 8 4 L 0 8 z" fill="#475569" />
                </marker>
            </defs>

            @php
                $pasos = [
                    ['Biblioteca', 'tus entidades', '#818cf8'],
                    ['Universo', 'copias con contexto', '#a78bfa'],
                    ['Temporadas', 'su calendario', '#60a5fa'],
                    ['Torneos', 'sus reglas', '#22d3ee'],
                    ['Competiciones', 'lo que se juega', '#34d399'],
                    ['Clasificación', 'quién manda', '#fbbf24'],
                ];
            @endphp

            @foreach ($pasos as $indice => [$titulo, $ayuda, $tono])
                @php
                    $x = 20 + $indice * 148;
                @endphp

                <g>
                    <rect x="{{ $x }}" y="58" width="128" height="62" rx="12"
                        fill="{{ $tono }}14" stroke="{{ $tono }}55" stroke-width="1.5" />

                    <text x="{{ $x + 64 }}" y="84" text-anchor="middle" font-size="13"
                        font-weight="800" fill="{{ $tono }}">{{ $titulo }}</text>

                    <text x="{{ $x + 64 }}" y="102" text-anchor="middle" font-size="10"
                        fill="#64748b">{{ $ayuda }}</text>
                </g>

                @if ($indice < count($pasos) - 1)
                    <line x1="{{ $x + 130 }}" y1="89" x2="{{ $x + 144 }}" y2="89"
                        stroke="#475569" stroke-width="1.5" marker-end="url(#flecha)" />
                @endif
            @endforeach

            <text x="450" y="152" text-anchor="middle" font-size="11" fill="#64748b">
                Traer una entidad a un universo es copiarla: lo que le pase aquí no toca el original.
            </text>
        </svg>
    </div>


    <div class="border-t border-slate-800 px-4 py-3 text-center">
        @can('create', App\Models\Universe::class)
            <a href="{{ route('universes.create') }}"
                class="inline-flex items-center gap-1.5 rounded-xl bg-violet-500 px-5 py-3 text-[13px] font-black text-white transition hover:bg-violet-400">
                <x-omni-icon name="mas" size="h-4 w-4" />
                Crear el primero
            </a>

            <p class="mx-auto mt-2 max-w-md text-[10px] leading-4 text-slate-600">
                Puedes dejarlo todo montado de una vez —calendario, qué premia, con qué se
                juega y quién lo habita— o crear solo el nombre y seguir después.
            </p>
        @endcan
    </div>
</section>
