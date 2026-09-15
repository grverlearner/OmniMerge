@php
    /*
     * Como se decide quien gana.
     *
     * Es la pregunta que la pagina anterior no contestaba en ninguna parte, y
     * es la que decide si esto le interesa a alguien: un gestor de fichas no
     * resuelve nada, y aqui los enfrentamientos se resuelven solos.
     *
     * Se explica el motor que existe -cada competidor tiene un rango, saca un
     * numero dentro de el, gana el mas alto- porque es honesto y porque de paso
     * explica la idea: un rango alto y estrecho es fiable, uno amplio es
     * impredecible. Eso es una decision de diseño del competidor, no azar puro.
     */
@endphp

<section id="enfrentamiento" class="relative px-5 py-14 lg:px-8">

    <div class="mx-auto max-w-7xl">

        <div class="mx-auto max-w-2xl text-center">
            <span class="text-[11px] font-black uppercase tracking-[0.2em] text-emerald-400/70">
                El motor
            </span>

            <h2 class="mt-2 text-3xl font-black tracking-tight text-white sm:text-4xl">
                Cómo se decide quién gana
            </h2>

            <p class="mt-3 text-[14px] leading-relaxed text-slate-400">
                Los enfrentamientos se resuelven solos. Cada competidor tiene un
                <strong class="text-slate-200">rango</strong> que dice de qué es capaz, saca un
                número dentro de él, y gana el más alto.
            </p>
        </div>


        <div class="mt-9 grid gap-3 lg:grid-cols-[minmax(0,1fr)_320px]">

            {{-- El dibujo --}}
            <div class="overflow-hidden rounded-2xl border border-emerald-500/20 bg-slate-900/40 p-4">

                <div class="overflow-x-auto">
                    <svg viewBox="0 0 700 260" class="mx-auto block h-auto w-full">

                        @php
                            /* Dos competidores de ejemplo, con rangos distintos */
                            $barras = [
                                ['A', 40, 70, 62, '#34d399', 'fiable: rango estrecho y alto'],
                                ['B', 10, 95, 81, '#a78bfa', 'impredecible: rango amplio'],
                            ];
                        @endphp

                        {{-- Eje --}}
                        <line x1="90" y1="205" x2="660" y2="205" stroke="#1e293b" stroke-width="1.5" />

                        @foreach ([0, 25, 50, 75, 100] as $marca)
                            @php $mx = 90 + ($marca / 100) * 570; @endphp
                            <line x1="{{ $mx }}" y1="200" x2="{{ $mx }}" y2="210" stroke="#334155" stroke-width="1.5" />
                            <text x="{{ $mx }}" y="226" text-anchor="middle" font-size="10" fill="#475569">{{ $marca }}</text>
                        @endforeach

                        @foreach ($barras as $indice => [$quien, $min, $max, $saco, $tono, $nota])
                            @php
                                $y = 46 + $indice * 74;
                                $x1 = 90 + ($min / 100) * 570;
                                $x2 = 90 + ($max / 100) * 570;
                                $xs = 90 + ($saco / 100) * 570;
                            @endphp

                            {{-- Quién --}}
                            <circle cx="52" cy="{{ $y + 16 }}" r="17" fill="{{ $tono }}1f" stroke="{{ $tono }}66" stroke-width="1.5" />
                            <text x="52" y="{{ $y + 21 }}" text-anchor="middle" font-size="14"
                                font-weight="800" fill="{{ $tono }}">{{ $quien }}</text>

                            {{-- Su rango --}}
                            <rect x="{{ $x1 }}" y="{{ $y + 6 }}" width="{{ $x2 - $x1 }}" height="21" rx="10"
                                fill="{{ $tono }}22" stroke="{{ $tono }}55" stroke-width="1.5" />

                            <text x="{{ $x1 + 6 }}" y="{{ $y + 21 }}" font-size="10" fill="{{ $tono }}99">{{ $min }}</text>
                            <text x="{{ $x2 - 6 }}" y="{{ $y + 21 }}" text-anchor="end" font-size="10" fill="{{ $tono }}99">{{ $max }}</text>

                            {{-- Lo que sacó --}}
                            <line x1="{{ $xs }}" y1="{{ $y }}" x2="{{ $xs }}" y2="{{ $y + 33 }}"
                                stroke="{{ $tono }}" stroke-width="2.5" />
                            <circle cx="{{ $xs }}" cy="{{ $y }}" r="12" fill="{{ $tono }}" />
                            <text x="{{ $xs }}" y="{{ $y + 4 }}" text-anchor="middle" font-size="11"
                                font-weight="800" fill="#020617">{{ $saco }}</text>

                            <text x="90" y="{{ $y + 46 }}" font-size="10" fill="#64748b">{{ $nota }}</text>
                        @endforeach

                        {{-- El resultado --}}
                        <text x="375" y="252" text-anchor="middle" font-size="12" font-weight="700" fill="#cbd5e1">
                            B saca 81 y A saca 62 · gana B este enfrentamiento
                        </text>
                    </svg>
                </div>
            </div>


            {{-- Las reglas, en corto --}}
            <aside class="rounded-2xl border border-white/10 bg-slate-900/40 p-4">

                <h3 class="text-[13px] font-black text-white">Lo que hay que saber</h3>

                <div class="mt-3 space-y-2.5">
                    @foreach ([
                        ['El rango es una decisión', 'Uno estrecho y alto gana casi siempre lo justo. Uno amplio puede humillar o hundirse.', '#34d399'],
                        ['Los empates se repiten', 'Si dos sacan el mismo número más alto, vuelven a tirar solo entre ellos.', '#22d3ee'],
                        ['El formato lo decide el torneo', 'A una, al mejor de tres, al mejor de cinco: eso lo pone la competición, no el motor.', '#a78bfa'],
                        ['Habrá más motores', 'El registro de juegos está abierto: cuando entre uno nuevo, aparece solo en todos los universos.', '#fbbf24'],
                    ] as [$titulo, $texto, $tono])

                        <div class="rounded-xl border border-white/5 bg-slate-950/50 p-2.5">
                            <p class="flex items-center gap-1.5 text-[12px] font-black"
                                style="color: {{ $tono }}">
                                <span class="h-1.5 w-1.5 rounded-full" style="background-color: {{ $tono }}"></span>
                                {{ $titulo }}
                            </p>
                            <p class="mt-0.5 text-[11px] leading-4 text-slate-500">{{ $texto }}</p>
                        </div>
                    @endforeach
                </div>
            </aside>
        </div>
    </div>
</section>
