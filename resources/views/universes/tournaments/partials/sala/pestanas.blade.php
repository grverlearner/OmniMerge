{{--
    Los tres paneles de la sala. Los comparten la sala del torneo y la de
    cada edición.
--}}

<div class="grid grid-cols-3 gap-1 rounded-2xl border border-slate-800 bg-slate-900/60 p-1">
    @foreach ([['who', 'Quién entra', 'usuario', 'bg-rose-500 text-slate-950'], ['doors', 'Puertas', 'puerta', 'bg-sky-500 text-slate-950'], ['faces', 'Caras', 'galeria', 'bg-violet-500 text-slate-950']] as [$clave, $texto, $icono, $activo])
        <button type="button" @click="panel = '{{ $clave }}'"
            :class="panel === '{{ $clave }}' ? '{{ $activo }}' : 'text-slate-400 hover:bg-slate-800 hover:text-white'"
            class="flex flex-col items-center gap-0.5 rounded-xl px-2 py-2 transition">
            <x-omni-icon :name="$icono" size="h-4 w-4" />
            <span class="text-[11px] font-black">{{ $texto }}</span>
            <span class="font-mono text-[9px] opacity-70"
                x-text="{
                    who: totalIn + ' dentro',
                    doors: starts.length === 1 ? '1 puerta' : starts.length + ' puertas',
                    faces: withVersions.length + ' con versiones',
                }['{{ $clave }}']"></span>
        </button>
    @endforeach
</div>
