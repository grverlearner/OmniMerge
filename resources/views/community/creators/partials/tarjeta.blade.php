@php
    /*
     * Un creador en el directorio.
     *
     * Sus caras públicas de fondo, su tipo dicho con su color, lo que ha
     * publicado de cada lado y los tres sitios a los que ir: su perfil
     * completo, y cada una de sus dos mitades solo si la tiene.
     */

    [$etiquetaTipo, $tonoTipo] = $tipos[$creador->tipo_creador];
    $susCaras = $caras[$creador->id] ?? collect();
@endphp

<article class="group overflow-hidden rounded-2xl border bg-slate-900/50 transition hover:-translate-y-0.5"
    style="border-color: {{ $tonoTipo }}33">

    <a href="{{ route('profiles.show', $creador->username) }}" class="relative block h-24 overflow-hidden bg-slate-950">

        @if ($susCaras->isNotEmpty())
            <span class="absolute inset-0 grid grid-cols-6 opacity-40">
                @foreach ($susCaras as $cara)
                    <span class="block aspect-square overflow-hidden">
                        <img src="{{ $cara->image_url }}" alt="" loading="lazy"
                            class="h-full w-full object-cover transition duration-500 group-hover:scale-105">
                    </span>
                @endforeach
            </span>
        @endif

        <span class="absolute inset-0" style="background: linear-gradient(to top, #020617 10%, {{ $tonoTipo }}1a 100%)"></span>

        <span class="absolute right-2 top-2 rounded-lg px-2 py-0.5 text-[9px] font-black uppercase tracking-wider backdrop-blur"
            style="color: {{ $tonoTipo }}; background-color: {{ $tonoTipo }}26">{{ $etiquetaTipo }}</span>
    </a>

    <div class="relative -mt-8 px-3 pb-3">

        <a href="{{ route('profiles.show', $creador->username) }}"
            class="block h-14 w-14 overflow-hidden rounded-2xl border-2 bg-slate-950"
            style="border-color: {{ $tonoTipo }}88">
            @if ($creador->avatar_url)
                <img src="{{ $creador->avatar_url }}" alt="" loading="lazy" class="h-full w-full object-cover">
            @else
                <span class="flex h-full w-full items-center justify-center text-[16px] font-black text-slate-500">
                    {{ $creador->initials }}
                </span>
            @endif
        </a>

        <a href="{{ route('profiles.show', $creador->username) }}"
            class="mt-1.5 block truncate text-[14px] font-black text-white transition hover:text-emerald-300">
            {{ $creador->name }}
        </a>

        <p class="truncate font-mono text-[10px] text-slate-500">&#64;{{ $creador->username }}</p>

        <x-creator-badge :user="$creador" class="mt-1" />

        @if ($creador->headline)
            <p class="mt-1 line-clamp-2 text-[11px] leading-4 text-slate-400">{{ $creador->headline }}</p>
        @endif

        <div class="mt-2 grid grid-cols-3 gap-1">
            @foreach ([['Biblioteca', $creador->pub_biblioteca, '#818cf8'], ['Torneos', $creador->pub_torneos, '#fbbf24'], ['Copias', $creador->copias_total, '#34d399']] as [$etiqueta, $valor, $tono])
                <span class="rounded-lg border border-slate-800 bg-slate-950 py-1 text-center">
                    <span class="block font-mono text-[13px] font-black"
                        style="color: {{ $valor > 0 ? $tono : '#475569' }}">{{ $valor }}</span>
                    <span class="block text-[8px] font-black uppercase tracking-wider text-slate-600">{{ $etiqueta }}</span>
                </span>
            @endforeach
        </div>

        <div class="mt-2 flex items-center gap-1">
            <a href="{{ route('profiles.show', $creador->username) }}"
                class="flex-1 rounded-lg bg-emerald-500/15 px-2 py-1.5 text-center text-[11px] font-black text-emerald-300 transition hover:bg-emerald-500 hover:text-slate-950">
                Perfil
            </a>

            @if ($creador->pub_biblioteca > 0)
                <a href="{{ route('community.creators.show', $creador->username) }}" title="Su biblioteca, a fondo"
                    class="rounded-lg border border-slate-800 p-1.5 text-slate-500 transition hover:border-indigo-500/50 hover:text-indigo-300">
                    <x-omni-icon name="libro" size="h-3.5 w-3.5" />
                </a>
            @endif

            @if ($creador->pub_torneos > 0)
                <a href="{{ route('tournaments.community.creator', $creador) }}" title="Sus torneos, a fondo"
                    class="rounded-lg border border-slate-800 p-1.5 text-slate-500 transition hover:border-amber-500/50 hover:text-amber-300">
                    <x-omni-icon name="trofeo" size="h-3.5 w-3.5" />
                </a>
            @endif
        </div>
    </div>
</article>
