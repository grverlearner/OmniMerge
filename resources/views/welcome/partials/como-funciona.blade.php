@php
    /*
     * El recorrido completo, dibujado.
     *
     * Cinco pasos y las flechas entre ellos. Debajo, cada paso explicado con
     * una frase y un ejemplo concreto: «Naruto» dice mas que «una entidad».
     */
@endphp

<section id="como-funciona" class="relative border-y border-white/5 bg-slate-950/40 px-5 py-14 lg:px-8">

    <div class="mx-auto max-w-7xl">

        <div class="mx-auto max-w-2xl text-center">
            <span class="text-[11px] font-black uppercase tracking-[0.2em] text-violet-400/70">
                De principio a fin
            </span>

            <h2 class="mt-2 text-3xl font-black tracking-tight text-white sm:text-4xl">
                Cómo funciona
            </h2>

            <p class="mt-3 text-[14px] leading-relaxed text-slate-400">
                Ninguno de estos pasos es obligatorio para empezar el siguiente, y se puede
                volver atrás siempre.
            </p>
        </div>


        {{-- El recorrido --}}
        <div class="mt-9 overflow-x-auto">

            <svg viewBox="0 0 1000 150" class="mx-auto block h-auto w-full max-w-4xl">

                <defs>
                    <marker id="flechaPaso" markerWidth="9" markerHeight="9" refX="8" refY="4.5" orient="auto">
                        <path d="M 0 0 L 9 4.5 L 0 9 z" fill="#475569" />
                    </marker>
                </defs>

                @foreach ($pasos as $indice => [$titulo, $ayuda, $tono])
                    @php $x = 15 + $indice * 197; @endphp

                    <g>
                        <rect x="{{ $x }}" y="36" width="172" height="74" rx="16"
                            fill="{{ $tono }}12" stroke="{{ $tono }}55" stroke-width="1.5" />

                        <circle cx="{{ $x + 26 }}" cy="62" r="13" fill="{{ $tono }}26" />
                        <text x="{{ $x + 26 }}" y="67" text-anchor="middle" font-size="12"
                            font-weight="800" fill="{{ $tono }}">{{ $indice + 1 }}</text>

                        <text x="{{ $x + 48 }}" y="67" font-size="15" font-weight="800"
                            fill="{{ $tono }}">{{ $titulo }}</text>

                        <text x="{{ $x + 26 }}" y="92" font-size="11" fill="#64748b">{{ $ayuda }}</text>
                    </g>

                    @if ($indice < count($pasos) - 1)
                        <line x1="{{ $x + 175 }}" y1="73" x2="{{ $x + 192 }}" y2="73"
                            stroke="#475569" stroke-width="1.5" marker-end="url(#flechaPaso)" />
                    @endif
                @endforeach

                <text x="500" y="132" text-anchor="middle" font-size="11" fill="#64748b">
                    La clasificación se recalcula sola: cambiar qué premia el mundo no obliga a volver a jugar nada.
                </text>
            </svg>
        </div>


        {{-- Cada paso, con un ejemplo --}}
        <div class="mt-8 grid gap-2.5 sm:grid-cols-2 lg:grid-cols-5">

            @foreach ([
                ['Entidades', '#818cf8', 'Creas lo que quieras representar. No hay categorías puestas de fábrica.', 'Un ninja, un país, una criatura, una idea.'],
                ['Atributos', '#22d3ee', 'Defines cómo se describen: texto, número, o un catálogo de valores que tú creas.', 'Aldea: Hoja · Arena · Niebla.'],
                ['Universos', '#a78bfa', 'Traes copias a un mundo con su propio calendario. La copia evoluciona por su cuenta.', 'Temporada 1, Temporada 2, Era 3…'],
                ['Torneos', '#fbbf24', 'Diseñas la forma: fases, cruces, salidas y a dónde va cada una.', 'Grupos → los dos primeros → final.'],
                ['Clasificación', '#34d399', 'Se calcula sola con lo jugado, y tú decides qué premia ese mundo.', 'Título 10 · victoria 3 · participar 1.'],
            ] as $indice => [$titulo, $tono, $texto, $ejemplo])

                <div class="rounded-2xl border bg-slate-900/40 p-4"
                    style="border-color: {{ $tono }}2e">

                    <span class="flex h-7 w-7 items-center justify-center rounded-lg font-mono text-[12px] font-black"
                        style="background-color: {{ $tono }}1f; color: {{ $tono }}">{{ $indice + 1 }}</span>

                    <h3 class="mt-2 text-[14px] font-black text-white">{{ $titulo }}</h3>

                    <p class="mt-1 text-[11px] leading-relaxed text-slate-500">{{ $texto }}</p>

                    <p class="mt-2 rounded-lg border border-white/5 bg-slate-950/60 px-2 py-1.5 font-mono text-[10px] leading-4"
                        style="color: {{ $tono }}cc">{{ $ejemplo }}</p>
                </div>
            @endforeach
        </div>
    </div>
</section>
