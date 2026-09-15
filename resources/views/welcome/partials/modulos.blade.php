@php
    /*
     * Los cuatro modulos, contados como lo que son.
     *
     * Aqui estaba el problema de fondo de la pagina anterior: Universos,
     * Torneos y Rankings aparecian al final, juntos, bajo el titulo «Futuro» y
     * con la etiqueta «Próximamente». Los tres existen. Ahora van arriba, como
     * parte del producto, con lo que cada uno hace de verdad.
     */
@endphp

<section id="modulos" class="relative px-5 py-14 lg:px-8">

    <div class="mx-auto max-w-7xl">

        <div class="mx-auto max-w-2xl text-center">
            <span class="text-[11px] font-black uppercase tracking-[0.2em] text-indigo-400/70">
                Qué es OmniMerge
            </span>

            <h2 class="mt-2 text-3xl font-black tracking-tight text-white sm:text-4xl">
                Cuatro piezas que encajan
            </h2>

            <p class="mt-3 text-[14px] leading-relaxed text-slate-400">
                Cada una funciona por su cuenta y todas se conectan. Puedes quedarte en la
                biblioteca y no montar un solo torneo, o llegar hasta el final.
            </p>
        </div>


        <div class="mt-9 grid gap-3 lg:grid-cols-2">

            @foreach ($modulos as [$titulo, $icono, $tono, $pregunta, $texto, $puntos])

                <article class="group overflow-hidden rounded-2xl border bg-slate-900/40 p-5 transition hover:-translate-y-0.5"
                    style="border-color: {{ $tono }}33"
                    onmouseover="this.style.borderColor='{{ $tono }}66'"
                    onmouseout="this.style.borderColor='{{ $tono }}33'">

                    <div class="flex items-start gap-3">

                        <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl"
                            style="background-color: {{ $tono }}1f; color: {{ $tono }}">
                            <x-omni-icon :name="$icono" size="h-5 w-5" />
                        </span>

                        <div class="min-w-0 flex-1">
                            <span class="block text-[9px] font-black uppercase tracking-[0.18em]"
                                style="color: {{ $tono }}99">{{ $pregunta }}</span>

                            <h3 class="text-[19px] font-black leading-tight text-white">{{ $titulo }}</h3>
                        </div>
                    </div>

                    <p class="mt-3 text-[13px] leading-relaxed text-slate-400">{{ $texto }}</p>

                    <ul class="mt-3 grid gap-1.5 sm:grid-cols-2">
                        @foreach ($puntos as $punto)
                            <li class="flex items-start gap-1.5 text-[11px] leading-4 text-slate-500">
                                <span class="mt-1.5 h-1 w-1 shrink-0 rounded-full"
                                    style="background-color: {{ $tono }}"></span>
                                {{ $punto }}
                            </li>
                        @endforeach
                    </ul>
                </article>
            @endforeach
        </div>
    </div>
</section>
