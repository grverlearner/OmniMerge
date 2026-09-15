@php
    /*
     * Que se ve de lo tuyo.
     *
     * La visibilidad no se decide aqui: se decide pieza a pieza, en la ficha de
     * cada entidad, coleccion, atributo o plantilla. Eso esta bien —publicar
     * debe ser un acto consciente— y tenia una consecuencia mala: se podia
     * poner el perfil en publico sin tener ni idea de que quedaba a la vista, o
     * al reves, publicar veinte cosas con el perfil cerrado y que no las viera
     * nadie.
     *
     * Esta tabla lo dice de un vistazo y lleva a donde se cambia.
     */
@endphp

<section class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/50">

    <header class="flex flex-wrap items-center gap-2 border-b border-slate-800 px-4 py-2.5">

        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-slate-800 text-slate-300">
            <x-omni-icon name="orbita" size="h-4 w-4" />
        </span>

        <div class="min-w-0 flex-1">
            <h2 class="text-[13px] font-black text-white">Qué se ve de lo tuyo</h2>
            <p class="text-[10px] text-slate-500">
                Cada cosa se publica por su cuenta, desde su propia ficha. Aquí está la cuenta.
            </p>
        </div>

        <span class="shrink-0 font-mono text-[11px] font-black"
            style="color: {{ $totalPublicas > 0 ? '#34d399' : '#475569' }}">
            {{ $totalPublicas }} de {{ $totalCosas }}
        </span>
    </header>


    {{--
        El aviso que faltaba: publicar cosas con el perfil cerrado no las
        enseña, y tener el perfil abierto sin nada publicado tampoco.
    --}}
    @if ($totalPublicas > 0 && ! $usuario->isPublicProfile())
        <p class="border-b border-amber-500/20 bg-amber-500/5 px-4 py-2.5 text-[11px] leading-4 text-amber-200/80">
            Tienes <strong class="text-amber-200">{{ $totalPublicas }}</strong>
            {{ $totalPublicas === 1 ? 'cosa publicada' : 'cosas publicadas' }} y el perfil
            cerrado. Siguen apareciendo en la comunidad, pero tu página no se puede abrir: quien
            las vea no podrá llegar a lo demás que has hecho.
        </p>
    @elseif ($totalPublicas === 0 && $usuario->isPublicProfile())
        <p class="border-b border-slate-800 bg-slate-950/50 px-4 py-2.5 text-[11px] leading-4 text-slate-500">
            Tu perfil es público pero está vacío: no has publicado nada todavía. Lo que crees
            es privado hasta que tú lo marques.
        </p>
    @endif


    <div class="grid gap-px bg-slate-800 sm:grid-cols-2 lg:grid-cols-5">

        @foreach ($loQueSeVe as $fila)
            @php
                $cuota = $fila['total'] > 0
                    ? (int) round($fila['publicas'] / $fila['total'] * 100)
                    : 0;
            @endphp

            <a href="{{ $fila['url'] }}"
                class="group bg-slate-900/60 p-3 transition hover:bg-slate-800/60">

                <span class="flex items-center gap-1.5">
                    <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-lg"
                        style="background-color: {{ $fila['tono'] }}1f; color: {{ $fila['tono'] }}">
                        <x-omni-icon :name="$fila['icono']" size="h-3.5 w-3.5" />
                    </span>

                    <span class="text-[11px] font-black text-slate-300">{{ $fila['etiqueta'] }}</span>
                </span>

                <span class="mt-2 flex items-baseline gap-1">
                    <span class="font-mono text-[19px] font-black leading-none"
                        style="color: {{ $fila['publicas'] > 0 ? $fila['tono'] : '#475569' }}">
                        {{ $fila['publicas'] }}
                    </span>
                    <span class="font-mono text-[11px] text-slate-600">/ {{ $fila['total'] }}</span>
                </span>

                <span class="mt-1.5 block h-1.5 overflow-hidden rounded-full bg-slate-950">
                    <span class="block h-full rounded-full transition-all"
                        style="width: {{ $cuota }}%; background-color: {{ $fila['tono'] }}"></span>
                </span>

                <span class="mt-1 block text-[9px] leading-3 text-slate-600">
                    @if ($fila['total'] === 0)
                        no tienes ninguna
                    @elseif ($fila['publicas'] === 0)
                        todas privadas
                    @elseif ($fila['publicas'] === $fila['total'])
                        todas públicas
                    @else
                        {{ $fila['total'] - $fila['publicas'] }} sin publicar
                    @endif
                </span>
            </a>
        @endforeach
    </div>


    @if ($privados > 0)
        <p class="border-t border-slate-800 px-4 py-2 text-[10px] leading-4 text-slate-600">
            Tus {{ $privados }} {{ $privados === 1 ? 'universo' : 'universos' }} no aparecen en
            esta lista porque <strong class="text-slate-500">no se publican</strong>: un mundo,
            su gente y su historia son tuyos y solo tuyos.
        </p>
    @endif
</section>
