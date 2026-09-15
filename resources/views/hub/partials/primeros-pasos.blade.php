@php
    /*
     * Cuenta vacia: los primeros pasos.
     *
     * En el sitio donde estaba el saludo que prometia universos y torneos «mas
     * adelante» hay ahora lo unico util con la cuenta a cero: que ES esto y por
     * donde se empieza, con el recorrido dibujado.
     *
     * Los cuatro pasos son los de verdad, cada uno con su boton. No es un
     * tutorial: es el camino, y se puede entrar por donde se quiera.
     */
@endphp

<section class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/50">

    <div class="border-b border-slate-800 px-4 py-3">
        <h2 class="text-[15px] font-black text-white">Cómo funciona OmniMerge</h2>
        <p class="mt-0.5 max-w-2xl text-[11px] leading-relaxed text-slate-500">
            Creas cosas, las describes, las metes en un mundo y las haces competir. Cada paso
            se puede hacer por separado y ninguno es obligatorio para empezar el siguiente.
        </p>
    </div>


    {{-- El recorrido --}}
    <div class="overflow-x-auto p-4">

        <svg viewBox="0 0 920 170" class="mx-auto block h-auto w-full max-w-3xl">

            <defs>
                <marker id="pasoFlecha" markerWidth="8" markerHeight="8" refX="7" refY="4" orient="auto">
                    <path d="M 0 0 L 8 4 L 0 8 z" fill="#475569" />
                </marker>
            </defs>

            @php
                $pasos = [
                    ['Entidades', 'lo que existe', '#818cf8'],
                    ['Atributos', 'cómo se describen', '#22d3ee'],
                    ['Universos', 'dónde viven', '#a78bfa'],
                    ['Torneos', 'cómo compiten', '#fbbf24'],
                    ['Clasificación', 'quién manda', '#34d399'],
                ];
            @endphp

            @foreach ($pasos as $indice => [$titulo, $ayuda, $tono])
                @php $x = 20 + $indice * 180; @endphp

                <g>
                    <rect x="{{ $x }}" y="46" width="158" height="66" rx="14"
                        fill="{{ $tono }}14" stroke="{{ $tono }}55" stroke-width="1.5" />

                    <circle cx="{{ $x + 22 }}" cy="68" r="11" fill="{{ $tono }}26" />
                    <text x="{{ $x + 22 }}" y="72" text-anchor="middle" font-size="11"
                        font-weight="800" fill="{{ $tono }}">{{ $indice + 1 }}</text>

                    <text x="{{ $x + 40 }}" y="72" font-size="13" font-weight="800"
                        fill="{{ $tono }}">{{ $titulo }}</text>

                    <text x="{{ $x + 22 }}" y="96" font-size="10" fill="#64748b">{{ $ayuda }}</text>
                </g>

                @if ($indice < count($pasos) - 1)
                    <line x1="{{ $x + 160 }}" y1="79" x2="{{ $x + 176 }}" y2="79"
                        stroke="#475569" stroke-width="1.5" marker-end="url(#pasoFlecha)" />
                @endif
            @endforeach

            <text x="460" y="140" text-anchor="middle" font-size="11" fill="#64748b">
                También puedes empezar copiando algo de la comunidad: llega como copia tuya y evoluciona por su cuenta.
            </text>
        </svg>
    </div>


    {{-- Por dónde empezar --}}
    <div class="grid gap-2 border-t border-slate-800 p-3 sm:grid-cols-2 xl:grid-cols-4">

        @foreach ([['Crear tu primera entidad', 'Un personaje, un país, lo que sea.', '#818cf8', 'libro', route('entities.create')], ['Describirla con atributos', 'Aldea, poder, año… lo que la distinga.', '#22d3ee', 'capas', route('attributes.create')], ['Montar un mundo', 'Con su calendario y su clasificación.', '#a78bfa', 'globo', route('universes.create')], ['Copiar de la comunidad', 'Empezar de lo que otros ya hicieron.', '#34d399', 'orbita', route('community.index')]] as [$titulo, $ayuda, $tono, $icono, $destino])

            <a href="{{ $destino }}"
                class="group rounded-xl border bg-slate-950 p-3 transition hover:-translate-y-0.5"
                style="border-color: {{ $tono }}33">

                <span class="flex h-9 w-9 items-center justify-center rounded-lg"
                    style="background-color: {{ $tono }}1f; color: {{ $tono }}">
                    <x-omni-icon :name="$icono" size="h-4 w-4" />
                </span>

                <span class="mt-2 block text-[12px] font-black leading-tight text-white">{{ $titulo }}</span>
                <span class="mt-0.5 block text-[10px] leading-3 text-slate-500">{{ $ayuda }}</span>
            </a>
        @endforeach
    </div>
</section>
