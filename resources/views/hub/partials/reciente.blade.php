@php
    /*
     * Lo ultimo que has tocado, de todo.
     *
     * Con su imagen de verdad y no con un icono generico por tipo: lo que se
     * busca al volver es reconocer la cosa, y una entidad se reconoce por su
     * cara antes que por la palabra «Entidad».
     *
     * El tipo se dice con el color del borde y una etiqueta pequeña, que es
     * suficiente para no confundir una coleccion con un torneo.
     */
@endphp

@if ($reciente->isNotEmpty())

    <section class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/50">

        <header class="flex flex-wrap items-center gap-2 border-b border-slate-800 px-4 py-2.5">

            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-slate-800 text-slate-300">
                <x-omni-icon name="historial" size="h-4 w-4" />
            </span>

            <div class="min-w-0 flex-1">
                <h2 class="text-[13px] font-black text-white">Lo último que has hecho</h2>
                <p class="text-[10px] text-slate-500">De cualquier parte de tu cuenta.</p>
            </div>
        </header>

        <div class="grid gap-2 p-3" style="grid-template-columns: repeat(auto-fill, minmax(150px, 1fr))">

            @foreach ($reciente as $cosa)
                <a href="{{ $cosa['url'] }}"
                    class="group overflow-hidden rounded-xl border bg-slate-950 transition hover:-translate-y-0.5"
                    style="border-color: {{ $cosa['tono'] }}33"
                    title="{{ $cosa['nombre'] }}">

                    <span class="relative block h-20 overflow-hidden bg-slate-900">
                        @if ($cosa['img'])
                            <img src="{{ $cosa['img'] }}" alt="" loading="lazy"
                                class="h-full w-full object-cover transition duration-300 group-hover:scale-105">
                        @else
                            <span class="flex h-full w-full items-center justify-center"
                                style="color: {{ $cosa['tono'] }}33">
                                <x-omni-icon name="chispa" size="h-6 w-6" />
                            </span>
                        @endif

                        <span class="absolute left-1 top-1 rounded px-1.5 py-0.5 text-[8px] font-black uppercase tracking-wider backdrop-blur"
                            style="color: {{ $cosa['tono'] }}; background-color: {{ $cosa['tono'] }}26">
                            {{ $cosa['tipo'] }}
                        </span>
                    </span>

                    <span class="block px-2 py-1.5">
                        <span class="block truncate text-[11px] font-black text-slate-200">
                            {{ $cosa['nombre'] }}
                        </span>

                        <span class="block font-mono text-[9px] text-slate-600">
                            @if ($cosa['cuando'])
                                hace {{ $cosa['cuando']->diffForHumans(null, true) }}
                            @else
                                —
                            @endif
                        </span>
                    </span>
                </a>
            @endforeach
        </div>
    </section>
@endif
