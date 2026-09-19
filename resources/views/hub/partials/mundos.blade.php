{{--
    Tus mundos, con su portada. Es lo que más se reconoce de un vistazo, así
    que va en grande: habitantes, temporadas, lo jugado y lo que está vivo.
--}}

<section>
    <div class="mb-3 flex items-end justify-between gap-3">
        <div>
            <h2 class="flex items-center gap-2 text-lg font-black text-white">
                <x-omni-icon name="orbita" size="h-5 w-5" class="text-violet-300" /> Tus mundos
            </h2>
            <p class="text-xs text-slate-500">Los que has movido últimamente.</p>
        </div>
        <a href="{{ route('universes.index') }}" class="text-xs font-black text-violet-300 hover:text-violet-200">
            Ver los {{ $statistics['universes'] }}
        </a>
    </div>

    <div class="grid grid-cols-2 gap-3 md:grid-cols-3 xl:grid-cols-6">
        @foreach ($carasUniversos as $mundo)
            <a href="{{ $mundo->home_url }}"
                class="group relative overflow-hidden rounded-2xl border border-violet-500/20 bg-slate-900 transition hover:-translate-y-0.5 hover:border-violet-500/60">
                <span class="relative block aspect-[4/5] overflow-hidden">
                    @if ($mundo->image_url)
                        <img src="{{ $mundo->image_url }}" alt="" loading="lazy" class="h-full w-full object-cover transition duration-500 group-hover:scale-105">
                    @else
                        <span class="flex h-full w-full items-center justify-center text-violet-500/30"
                            style="background: radial-gradient(circle at 50% 30%, #8b5cf633, transparent 70%);">
                            <x-omni-icon name="orbita" size="h-14 w-14" />
                        </span>
                    @endif
                    <span class="absolute inset-0 bg-gradient-to-t from-slate-950 via-slate-950/40 to-transparent"></span>

                    @if ($mundo->vivas_count > 0)
                        <span class="absolute right-2 top-2 inline-flex items-center gap-1 rounded-full bg-emerald-500/25 px-2 py-0.5 text-[10px] font-black text-emerald-200 backdrop-blur">
                            <span class="h-1.5 w-1.5 animate-pulse rounded-full bg-emerald-400"></span> {{ $mundo->vivas_count }} en juego
                        </span>
                    @endif

                    <span class="absolute inset-x-0 bottom-0 p-3">
                        <span class="block truncate text-[15px] font-black text-white">{{ $mundo->name }}</span>
                        <span class="mt-1.5 flex flex-wrap gap-1 text-[10px] font-bold">
                            <span class="rounded-md bg-white/10 px-1.5 py-0.5 text-slate-200" title="Habitantes">{{ $mundo->entities_count }} hab.</span>
                            <span class="rounded-md bg-white/10 px-1.5 py-0.5 text-slate-200" title="Temporadas">{{ $mundo->seasons_count }} temp.</span>
                            <span class="rounded-md bg-white/10 px-1.5 py-0.5 text-slate-200" title="Competiciones jugadas">{{ $mundo->jugadas_count }} jugadas</span>
                        </span>
                    </span>
                </span>
            </a>
        @endforeach

        <a href="{{ route('universes.create') }}"
            class="flex aspect-[4/5] flex-col items-center justify-center gap-2 rounded-2xl border border-dashed border-violet-500/30 bg-violet-500/5 text-center text-violet-300 transition hover:border-violet-500/70 hover:bg-violet-500/10">
            <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-violet-500/15"><x-omni-icon name="mas" size="h-6 w-6" /></span>
            <span class="text-sm font-black">Nuevo mundo</span>
            <span class="px-4 text-[11px] text-slate-500">Dale gente, un calendario y torneos</span>
        </a>
    </div>
</section>
