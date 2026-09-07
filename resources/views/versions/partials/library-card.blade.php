@php
    /*
     * Una definición de versión, en formato ficha.
     *
     * Lo importante de un molde no es cómo se llama: es **en cuántas
     * entidades se ha aplicado**. Un molde que nadie usa existe, pero no hace
     * nada, y eso se marca en vez de dejarlo en un cero silencioso.
     */

    $sinUsar = $definicion->entity_versions_count === 0;
@endphp

<article
    class="group flex flex-col overflow-hidden rounded-2xl border bg-slate-900/50 transition duration-300 hover:-translate-y-0.5 {{ $sinUsar ? 'border-slate-800' : 'border-violet-500/30 hover:shadow-[0_16px_38px_-14px_rgba(139,92,246,0.35)]' }}">

    {{-- ============ SU CARA ============ --}}

    <a href="{{ route('versions.show', $definicion) }}"
        class="relative block aspect-[16/9] overflow-hidden bg-slate-950">

        @if ($definicion->image_url)
            <img src="{{ $definicion->image_url }}" alt="{{ $definicion->name }}" loading="lazy"
                class="h-full w-full object-cover transition duration-300 group-hover:scale-105">

            <span class="absolute inset-x-0 bottom-0 h-2/5 bg-gradient-to-t from-slate-950 to-transparent"></span>
        @else
            <span class="flex h-full w-full items-center justify-center text-4xl text-violet-500/20"
                style="background: radial-gradient(120% 90% at 50% 0%, rgba(139,92,246,0.14), transparent 70%)">
                ◈
            </span>
        @endif

        {{-- Qué clase de molde es --}}
        <span class="absolute left-2 top-2 rounded-lg border border-violet-500/30 bg-slate-950/85 px-2 py-1 text-[9px] font-black uppercase tracking-wider text-violet-300">
            {{ $definicion->kind_label }}
        </span>

        <span class="absolute right-2 top-2 flex flex-col items-end gap-1">
            @if ($definicion->status !== 'ACTIVE')
                <span class="rounded px-1.5 py-0.5 text-[8px] font-black uppercase tracking-wider {{ $estadoTono[$definicion->status] ?? 'bg-slate-800 text-slate-500' }}">
                    {{ $definicion->status_label }}
                </span>
            @endif

            @if ($definicion->scope === 'EXCLUSIVE')
                <span class="rounded bg-sky-500/80 px-1.5 py-0.5 text-[8px] font-black uppercase tracking-wider text-slate-950">
                    exclusiva
                </span>
            @endif
        </span>
    </a>


    {{-- ============ QUÉ ES ============ --}}

    <div class="flex-1 p-3">

        <a href="{{ route('versions.show', $definicion) }}"
            class="block truncate text-[13px] font-black text-white transition hover:text-violet-300">
            {{ $definicion->name }}
        </a>

        <p class="font-mono text-[9px] text-slate-600">{{ $definicion->code }}</p>

        <p class="mt-1.5 line-clamp-2 text-[10px] leading-relaxed text-slate-500">
            {{ $definicion->description ?: 'Sin descripción.' }}
        </p>


        {{-- Cómo se comporta --}}
        <div class="mt-2.5 flex flex-wrap gap-1.5 text-[9px]">
            <span class="rounded-lg border border-slate-800 bg-slate-950 px-2 py-1 font-bold text-slate-400">
                {{ $definicion->scope_label }}
            </span>

            <span class="rounded-lg border border-slate-800 bg-slate-950 px-2 py-1 font-bold text-slate-400">
                {{ $definicion->activation_label }}
            </span>

            @if ($definicion->catalog_links_count > 0)
                <span class="rounded-lg border border-cyan-500/25 bg-cyan-500/5 px-2 py-1 font-bold text-cyan-300">
                    {{ $definicion->catalog_links_count }} catálogos
                </span>
            @endif

            @if ($definicion->children_count > 0)
                <span class="rounded-lg border border-slate-800 bg-slate-950 px-2 py-1 font-bold text-slate-400">
                    {{ $definicion->children_count }} hijas
                </span>
            @endif
        </div>


        {{-- En cuántas se ha aplicado: lo que de verdad importa --}}
        <div class="mt-2.5">
            @if ($sinUsar)
                <p class="rounded-xl border border-dashed border-slate-800 px-3 py-2.5 text-center text-[10px] leading-4 text-slate-600">
                    Todavía no se ha aplicado a ninguna entidad.
                </p>
            @else
                <a href="{{ route('versions.entities.index', ['version' => $definicion->id]) }}"
                    class="flex items-center gap-2 rounded-xl border border-violet-500/25 bg-violet-500/5 px-3 py-2 transition hover:bg-violet-500/10">
                    <span class="font-mono text-lg font-black text-violet-300">
                        {{ $definicion->entity_versions_count }}
                    </span>
                    <span class="text-[10px] leading-3 text-slate-400">
                        {{ $definicion->entity_versions_count === 1 ? 'entidad la aplica' : 'entidades la aplican' }}
                        <span class="block text-[9px] text-slate-600">ver cuáles →</span>
                    </span>
                </a>
            @endif
        </div>

    </div>


    {{-- ============ QUÉ SE PUEDE HACER ============ --}}

    <div class="flex items-center gap-1 border-t border-slate-800 px-2 py-1.5">

        <a href="{{ route('versions.show', $definicion) }}"
            class="rounded-lg px-2 py-1 text-[10px] font-black text-slate-400 transition hover:text-white">
            Ver
        </a>

        @can('update', $definicion)
            <a href="{{ route('versions.edit', $definicion) }}"
                class="rounded-lg px-2 py-1 text-[10px] font-black text-slate-400 transition hover:text-amber-300">
                ✎ Editar
            </a>
        @endcan

        @can('create', App\Models\Version::class)
            <a href="{{ route('versions.entities.bulk.create', $definicion) }}"
                title="Aplicarla a varias entidades a la vez"
                class="ml-auto rounded-lg bg-violet-500/15 px-2.5 py-1 text-[10px] font-black text-violet-300 transition hover:bg-violet-500 hover:text-white">
                Aplicar en lote
            </a>
        @endcan

    </div>

</article>
