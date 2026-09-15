@php
    /*
     * Lo que viene, ahora que si es lo que viene.
     *
     * Antes esta seccion se llamaba «Futuro» y anunciaba cuatro cosas como
     * proximas: Universos, Torneos, Simulaciones y Rankings. Tres de las cuatro
     * ya estaban hechas. Aqui queda solo lo que de verdad no existe todavia, y
     * se dice con esas palabras.
     *
     * Y se aclara lo que si hay del cuarto punto: los enfrentamientos ya se
     * resuelven, lo que falta es que el resultado dependa de los atributos de
     * cada competidor en vez de un rango.
     */
@endphp

<section class="relative border-y border-white/5 bg-slate-950/40 px-5 py-14 lg:px-8">

    <div class="mx-auto max-w-4xl">

        <div class="text-center">
            <span class="text-[11px] font-black uppercase tracking-[0.2em] text-slate-500">
                Honestamente
            </span>

            <h2 class="mt-2 text-3xl font-black tracking-tight text-white sm:text-4xl">
                Lo que todavía no está
            </h2>

            <p class="mx-auto mt-3 max-w-2xl text-[14px] leading-relaxed text-slate-400">
                Todo lo de arriba existe y se usa. Esto no, y preferimos decirlo aquí antes de
                que lo descubras dentro.
            </p>
        </div>

        <div class="mt-8 grid gap-2.5 sm:grid-cols-2">

            @foreach ([
                [
                    'Simulación por atributos',
                    'matraz',
                    'Hoy un enfrentamiento se decide con el rango de cada competidor. Lo que falta es que se decida con lo que la entidad ES: que su poder, su velocidad o su elemento pesen en el resultado.',
                    'El motor de juegos ya está abierto a recibirlo.',
                ],
                [
                    'Compartir torneos en la comunidad',
                    'trofeo',
                    'Ahora mismo se comparten entidades, colecciones, atributos y catálogos. Las plantillas de torneo y de fase todavía no salen de tu cuenta.',
                    'Los torneos se diseñan y se juegan; solo no se regalan.',
                ],
            ] as [$titulo, $icono, $texto, $matiz])

                <article class="rounded-2xl border border-white/10 bg-slate-900/40 p-4">

                    <div class="flex items-start gap-3">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-slate-800 text-slate-400">
                            <x-omni-icon :name="$icono" size="h-5 w-5" />
                        </span>

                        <div class="min-w-0 flex-1">
                            <h3 class="text-[15px] font-black leading-tight text-white">{{ $titulo }}</h3>
                            <span class="mt-0.5 inline-block rounded bg-slate-800 px-1.5 py-0.5 text-[9px] font-black uppercase tracking-wider text-slate-500">
                                todavía no
                            </span>
                        </div>
                    </div>

                    <p class="mt-3 text-[12px] leading-relaxed text-slate-400">{{ $texto }}</p>

                    <p class="mt-2 border-l-2 border-slate-700 pl-2 text-[11px] leading-4 text-slate-500">
                        {{ $matiz }}
                    </p>
                </article>
            @endforeach
        </div>
    </div>
</section>
