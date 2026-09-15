@php
    /*
     * Por donde seguir.
     *
     * Esta pantalla es la persona; los dos perfiles especializados son para
     * bucear, y por eso se conservan. Aqui se dice QUE ofrece cada uno, porque
     * «ver perfil» dos veces no explica en que se diferencian.
     */
@endphp

<section class="grid gap-2 sm:grid-cols-2">

    <a href="{{ route('community.creators.show', $creador->username) }}"
        class="group flex items-center gap-3 rounded-2xl border border-indigo-500/25 bg-slate-900/50 p-4 transition hover:-translate-y-0.5 hover:border-indigo-500/50">

        <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-indigo-500/15 text-indigo-300">
            <x-omni-icon name="libro" size="h-5 w-5" />
        </span>

        <span class="min-w-0 flex-1">
            <span class="block text-[14px] font-black text-white">Su biblioteca, a fondo</span>
            <span class="block text-[11px] leading-4 text-slate-500">
                Todas sus entidades, colecciones, atributos y catálogos, con filtros, formas de
                ver y copia directa.
            </span>
        </span>

        <span class="shrink-0 text-slate-600 transition group-hover:text-indigo-400">
            <x-omni-icon name="chevron-derecha" size="h-4 w-4" />
        </span>
    </a>


    <a href="{{ route('tournaments.community.creator', $creador) }}"
        class="group flex items-center gap-3 rounded-2xl border border-amber-500/25 bg-slate-900/50 p-4 transition hover:-translate-y-0.5 hover:border-amber-500/50">

        <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-amber-500/15 text-amber-300">
            <x-omni-icon name="trofeo" size="h-5 w-5" />
        </span>

        <span class="min-w-0 flex-1">
            <span class="block text-[14px] font-black text-white">Sus torneos, a fondo</span>
            <span class="block text-[11px] leading-4 text-slate-500">
                Sus plantillas y fases publicadas, con el recorrido de cada una y la opción de
                clonarlas.
            </span>
        </span>

        <span class="shrink-0 text-slate-600 transition group-hover:text-amber-400">
            <x-omni-icon name="chevron-derecha" size="h-4 w-4" />
        </span>
    </a>
</section>
