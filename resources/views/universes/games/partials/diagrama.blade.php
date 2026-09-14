@php
    /*
     * Cómo funciona un juego, dibujado.
     *
     * Los juegos viven en código y no tienen imagen que subir, así que su
     * «foto» es este diagrama: se dibuja desde su propia definición —tipo de
     * interacción, número de participantes, si puntúa— y no desde una lista de
     * casos escritos a mano. Un juego nuevo entra aquí sin tocar nada.
     *
     * Los dos que hay hoy son numéricos, así que el dibujo enseña el mecanismo
     * que comparten: cada competidor tiene un rango, saca un número dentro de
     * él, y el más alto gana. El de enteros añade el paso del redondeo.
     *
     *   $definicion  la definición del motor
     *   $tono        el color de acento, ya resuelto
     *   $redondea    si el juego redondea antes de comparar
     */

    $redondea = $redondea ?? str_contains(mb_strtolower($definicion['name'] ?? ''), 'rounded');

    $numerico = ($definicion['type'] ?? '') === 'NUMERIC'
        || str_contains(mb_strtolower($definicion['type_label'] ?? ''), 'numér');
@endphp

<svg viewBox="0 0 320 {{ $redondea ? 132 : 116 }}" class="h-auto w-full" fill="none"
    stroke="{{ $tono }}" stroke-width="1.5" stroke-linecap="round" aria-hidden="true">

    @if ($numerico)

        {{-- ---------- 1 · CADA UNO TIENE SU RANGO ---------- --}}

        <text x="46" y="12" text-anchor="middle" fill="{{ $tono }}" stroke="none"
            font-size="8" font-weight="700">1 · Su rango</text>

        {{-- competidor A: rango estrecho y alto = fiable --}}
        <circle cx="16" cy="30" r="7" opacity=".85" />
        <path d="M28 30h56" opacity=".2" />
        <rect x="52" y="25" width="24" height="10" rx="3" opacity=".9" />
        <text x="90" y="33" fill="{{ $tono }}" stroke="none" font-size="7"
            font-weight="700" opacity=".55">estrecho: fiable</text>

        {{-- competidor B: rango amplio = impredecible --}}
        <circle cx="16" cy="54" r="7" opacity=".85" />
        <path d="M28 54h56" opacity=".2" />
        <rect x="32" y="49" width="50" height="10" rx="3" opacity=".55" stroke-dasharray="4 3" />
        <text x="90" y="57" fill="{{ $tono }}" stroke="none" font-size="7"
            font-weight="700" opacity=".55">amplio: impredecible</text>


        {{-- ---------- 2 · CADA UNO SACA UN NÚMERO ---------- --}}

        <path d="M168 42h18M186 42l-6-4M186 42l-6 4" opacity=".6" />

        <text x="222" y="12" text-anchor="middle" fill="{{ $tono }}" stroke="none"
            font-size="8" font-weight="700">2 · Saca un número</text>

        <rect x="192" y="20" width="42" height="22" rx="5" />
        <text x="213" y="35" text-anchor="middle" fill="{{ $tono }}" stroke="none"
            font-size="11" font-weight="800">{{ $redondea ? '8,6' : '8,4' }}</text>

        <rect x="192" y="48" width="42" height="22" rx="5" opacity=".55" />
        <text x="213" y="63" text-anchor="middle" fill="{{ $tono }}" stroke="none"
            font-size="11" font-weight="800" opacity=".6">{{ $redondea ? '4,2' : '6,1' }}</text>

        @if ($redondea)
            {{-- El paso que distingue a este juego del otro --}}
            <path d="M238 31h14M252 31l-5-3.5M252 31l-5 3.5" opacity=".6" />
            <path d="M238 59h14M252 59l-5-3.5M252 59l-5 3.5" opacity=".45" />

            <rect x="256" y="20" width="34" height="22" rx="5" stroke-width="2" />
            <text x="273" y="35" text-anchor="middle" fill="{{ $tono }}" stroke="none"
                font-size="11" font-weight="800">9</text>

            <rect x="256" y="48" width="34" height="22" rx="5" opacity=".55" />
            <text x="273" y="63" text-anchor="middle" fill="{{ $tono }}" stroke="none"
                font-size="11" font-weight="800" opacity=".6">4</text>

            <text x="273" y="12" text-anchor="middle" fill="{{ $tono }}" stroke="none"
                font-size="8" font-weight="700">3 · Redondea</text>

            <text x="273" y="82" text-anchor="middle" fill="{{ $tono }}" stroke="none"
                font-size="7" font-weight="700" opacity=".55">al entero</text>
        @endif


        {{-- ---------- GANA EL MÁS ALTO ---------- --}}

        @php $baseY = $redondea ? 100 : 88; @endphp

        <path d="M14 {{ $baseY - 12 }}h292" opacity=".15" />

        <circle cx="24" cy="{{ $baseY + 4 }}" r="7" stroke-width="2" />
        <path d="M20 {{ $baseY + 4 }}l3 3 5-6" stroke-width="2" />

        <text x="40" y="{{ $baseY + 7 }}" fill="{{ $tono }}" stroke="none" font-size="8"
            font-weight="700">
            {{ $redondea ? 'Gana el 9. Si empatan, repiten la tirada.' : 'Gana el 8,4. Si empatan, repiten la tirada.' }}
        </text>

        @if ($definicion['tracks_points'] ?? false)
            <text x="40" y="{{ $baseY + 18 }}" fill="{{ $tono }}" stroke="none" font-size="7"
                font-weight="700" opacity=".5">
                y lo que saca cada uno cuenta como puntos, no solo como victoria
            </text>
        @endif

    @else

        {{-- ---------- UN JUEGO QUE TODAVÍA NO ES NUMÉRICO ---------- --}}

        <rect x="10" y="20" width="90" height="60" rx="6" stroke-dasharray="5 4" />
        <path d="M110 50h30M140 50l-6-4M140 50l-6 4" opacity=".6" />
        <rect x="150" y="20" width="90" height="60" rx="6" stroke-dasharray="5 4" />
        <path d="M250 50h30M280 50l-6-4M280 50l-6 4" opacity=".6" />
        <circle cx="300" cy="50" r="10" stroke-width="2" />

        <text x="160" y="100" text-anchor="middle" fill="{{ $tono }}" stroke="none"
            font-size="8" font-weight="700">
            {{ $definicion['win_condition'] ?? 'El motor decide quién gana.' }}
        </text>

    @endif
</svg>
