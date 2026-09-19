@php
    /*
     * Una entidad de la comunidad, en formato ficha.
     *
     * Lo que hace falta para decidir si copiarla: su cara, de quién es, de quién
     * la copió esa persona, cuánto la han copiado los demás, y si tú ya la
     * tienes.
     */

    $cara = $entidad->public_image_url ?: $entidad->image_url;

    $miCopia = $entidad->clones->first();

    $esMio = $entidad->user_id === auth()->id();
@endphp

<article class="group flex flex-col overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/50 transition duration-300 hover:-translate-y-0.5 hover:border-violet-500/40">

    <a href="{{ route('community.entities.show', $entidad) }}"
        class="relative block aspect-[4/5] overflow-hidden bg-slate-950">

        @if ($cara)
            <img src="{{ $cara }}" alt="{{ $entidad->name }}" loading="lazy"
                class="h-full w-full object-cover transition duration-500 group-hover:scale-105">
            <span class="absolute inset-x-0 bottom-0 h-2/5 bg-gradient-to-t from-slate-950 to-transparent"></span>
        @else
            <span class="flex h-full w-full items-center justify-center text-4xl text-slate-800">◍</span>
        @endif

        @if ($entidad->entityType)
            <span class="absolute left-2 top-2 rounded-lg border border-slate-700 bg-slate-950/85 px-1.5 py-0.5 text-[9px] font-black uppercase tracking-wider text-slate-300">
                {{ $entidad->entityType->name }}
            </span>
        @endif

        @if ($miCopia)
            <span class="absolute right-2 top-2 rounded-lg bg-emerald-500 px-1.5 py-0.5 text-[9px] font-black text-emerald-950">
                ✓ tuya
            </span>
        @elseif ($entidad->clones_count > 0)
            <span class="absolute right-2 top-2 rounded-lg bg-slate-950/85 px-1.5 py-0.5 font-mono text-[9px] font-black text-violet-300"
                title="Copiada {{ $entidad->clones_count }} veces">
                ↺{{ $entidad->clones_count }}
            </span>
        @endif

        <span class="absolute inset-x-0 bottom-0 p-2">
            <x-content-badges type="entity" :id="$entidad->id" size="xs" wrap="mb-1 flex flex-wrap gap-1" />
            <span class="block truncate text-[13px] font-black text-white">{{ $entidad->name }}</span>
        </span>
    </a>

    <div class="flex-1 space-y-1.5 p-2.5">

        @include('community.partials.atribucion', [
            'autor' => $entidad->creator,
            'origen' => $entidad->sourceEntity?->creator,
        ])

        <p class="line-clamp-2 text-[10px] leading-relaxed text-slate-500">
            {{ $entidad->description ?: 'Sin descripción.' }}
        </p>

        <div class="flex flex-wrap items-center gap-1">
            <span class="rounded border border-slate-800 px-1.5 py-0.5 font-mono text-[9px] text-slate-500"
                title="Atributos rellenados">
                {{ $entidad->entity_attributes_count }} atr.
            </span>

            @if ($entidad->collections_count > 0)
                <span class="rounded border border-slate-800 px-1.5 py-0.5 font-mono text-[9px] text-slate-500"
                    title="Colecciones a las que pertenece">
                    {{ $entidad->collections_count }} col.
                </span>
            @endif
        </div>
    </div>

    <div class="flex items-center gap-1 border-t border-slate-800 px-2 py-1.5">

        <a href="{{ route('community.entities.show', $entidad) }}"
            class="rounded-lg px-2 py-1 text-[10px] font-black text-slate-400 transition hover:text-white">
            Ver
        </a>

        <span class="ml-auto flex items-center gap-1">
            @include('community.partials.copiar', [
                'ruta' => route('community.entities.clone', $entidad),
                'esMio' => $esMio,
                'miCopia' => $miCopia,
                'seDeja' => (bool) $entidad->allow_cloning,
                'rutaMia' => $miCopia ? route('entities.show', $miCopia) : '#',
                'compacto' => true,
            ])
        </span>
    </div>

</article>
