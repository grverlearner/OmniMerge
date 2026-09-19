@php
    /*
     * Un creador de la comunidad.
     *
     * «12 entidades» no enseña nada de lo que ha hecho. Con seis de sus caras se
     * decide en un vistazo si vale la pena entrar en su perfil, que es para lo
     * único que sirve esta tarjeta.
     */

    $nombre = $creador->username ?? $creador->name;

    $caras = $carasDeCreador[$creador->id] ?? collect();

    $total =
        $creador->public_entities_count
        + $creador->public_collections_count
        + $creador->public_attributes_count;
@endphp

<article class="group flex flex-col overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/50 transition duration-300 hover:-translate-y-0.5 hover:border-violet-500/40">

    <a href="{{ route('community.creators.show', $creador->username) }}"
        class="flex items-center gap-2.5 p-3">

        <span class="h-11 w-11 shrink-0 overflow-hidden rounded-full border border-slate-700 bg-slate-950">
            @if ($creador->avatar_url)
                <img src="{{ $creador->avatar_url }}" alt="" loading="lazy" class="h-full w-full object-cover">
            @else
                <span class="flex h-full w-full items-center justify-center text-sm font-black text-slate-500">
                    {{ mb_strtoupper(mb_substr($nombre, 0, 1)) }}
                </span>
            @endif
        </span>

        <span class="min-w-0 flex-1">
            <span class="block truncate text-[13px] font-black text-white">{{ $creador->name }}</span>
            <span class="block truncate text-[10px] font-bold text-violet-400">{{ '@' . $nombre }}</span>
            <x-creator-badge :user="$creador" size="xs" class="mt-0.5" />
        </span>

        <span class="shrink-0 text-right">
            <span class="block font-mono text-lg font-black {{ $total > 0 ? 'text-violet-300' : 'text-slate-700' }}">
                {{ $total }}
            </span>
            <span class="block text-[8px] font-black uppercase tracking-wider text-slate-600">públicos</span>
        </span>
    </a>

    @if ($caras->isNotEmpty())
        <div class="grid grid-cols-6 gap-1 px-3 pb-2">
            @foreach ($caras as $suya)
                <a href="{{ route('community.entities.show', $suya) }}" title="{{ $suya->name }}"
                    class="block aspect-square overflow-hidden rounded-lg border border-slate-800 bg-slate-950">
                    <img src="{{ $suya->public_image_url ?: $suya->image_url }}" alt="" loading="lazy"
                        class="h-full w-full object-cover">
                </a>
            @endforeach
        </div>
    @else
        <p class="px-3 pb-2 text-[10px] text-slate-600">
            Todavía no tiene ninguna entidad pública con imagen.
        </p>
    @endif

    <div class="grid grid-cols-3 gap-px border-t border-slate-800 bg-slate-800">
        @foreach ([['Entidades', $creador->public_entities_count], ['Colecciones', $creador->public_collections_count], ['Atributos', $creador->public_attributes_count]] as [$etiqueta, $numero])
            <div class="bg-slate-900/50 px-2 py-1.5 text-center">
                <span class="block font-mono text-[13px] font-black {{ $numero > 0 ? 'text-slate-200' : 'text-slate-700' }}">
                    {{ $numero }}
                </span>
                <span class="block text-[8px] font-black uppercase tracking-wider text-slate-600">{{ $etiqueta }}</span>
            </div>
        @endforeach
    </div>

    <div class="border-t border-slate-800 px-2 py-1.5">
        <a href="{{ route('community.creators.show', $creador->username) }}"
            class="block rounded-lg px-2 py-1 text-center text-[10px] font-black text-slate-400 transition hover:text-violet-300">
            Ver su biblioteca →
        </a>
    </div>

</article>
