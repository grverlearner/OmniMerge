@php
    /*
     * La cuadricula: solo la cara y el pulso minimo.
     *
     * Para cuando hay muchos mundos y lo que se quiere es abarcarlos de un
     * vistazo. Lo unico que sobrevive al recorte es lo que decide si entras:
     * como se llama, si algo se juega ahi y si algo esta atascado.
     */
@endphp

<section x-show="vista === 'cuadricula'" x-cloak class="grid gap-2" :class="rejillaCompacta">

    @foreach ($universes as $mundo)
        @php
            [$tono, $textoEstado] = $tonosEstado[$mundo->status] ?? ['#94a3b8', $mundo->status];
            $atascado = $mundo->atascadas_count > 0;
        @endphp

        <a href="{{ $mundo->home_url }}"
            class="group overflow-hidden rounded-xl border bg-slate-900/50 transition hover:-translate-y-0.5"
            style="border-color: {{ $atascado ? '#fb718566' : $tono . '44' }}">

            <span class="relative block aspect-[4/3] overflow-hidden bg-slate-950">
                @if ($mundo->image_url)
                    <img src="{{ $mundo->image_url }}" alt="" loading="lazy"
                        class="h-full w-full object-cover transition duration-500 group-hover:scale-105">
                @else
                    <span class="flex h-full w-full items-center justify-center text-slate-800">
                        <x-omni-icon name="globo" size="h-8 w-8" />
                    </span>
                @endif

                <span class="absolute inset-x-0 bottom-0 h-12"
                    style="background: linear-gradient(to top, #020617, transparent)"></span>

                <span class="absolute left-1.5 top-1.5 h-2 w-2 rounded-full" style="background-color: {{ $tono }}"
                    title="{{ $textoEstado }}"></span>

                @if ($mundo->vivas_count > 0)
                    <span class="absolute right-1.5 top-1.5 flex items-center gap-1 rounded bg-emerald-500/30 px-1.5 py-0.5 font-mono text-[9px] font-black text-emerald-100 backdrop-blur">
                        <span class="h-1 w-1 animate-pulse rounded-full bg-emerald-300"></span>
                        {{ $mundo->vivas_count }}
                    </span>
                @endif

                @if ($atascado)
                    <span class="absolute bottom-1.5 right-1.5 rounded bg-rose-500/40 px-1.5 py-0.5 font-mono text-[9px] font-black text-rose-100 backdrop-blur"
                        title="{{ $mundo->atascadas_count }} esperan una decisión">
                        {{ $mundo->atascadas_count }} ⏸
                    </span>
                @endif
            </span>

            <span class="block p-2">
                <span class="block truncate text-[12px] font-black text-white">{{ $mundo->name }}</span>

                <span class="flex items-center gap-2 font-mono text-[9px] text-slate-600">
                    <span style="color: {{ $mundo->entities_count > 0 ? '#a78bfa' : '#475569' }}">
                        {{ $mundo->entities_count }} gente
                    </span>
                    <span style="color: {{ $mundo->tournament_instances_count > 0 ? '#34d399' : '#475569' }}">
                        {{ $mundo->tournament_instances_count }} jugadas
                    </span>
                </span>
            </span>
        </a>
    @endforeach
</section>
