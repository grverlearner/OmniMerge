@php
    /*
     * Un tipo de entidad en formato ficha.
     *
     * La ficha se tiñe de su propio color y enseña, dentro, las caras de sus
     * últimas entidades: un tipo se reconoce por lo que lleva puesto. Si uno
     * llamado «Personaje» está lleno de banderas, se ve aquí sin abrirlo.
     *
     * Un tipo sin nada se marca en vez de quedarse en un cero silencioso:
     * existe, pero todavía no sirve de nada.
     */

    $color = $tipo->color ?: '#6366f1';

    $vacio = $tipo->entities_count === 0;
@endphp

{{-- Un tipo sin nada se queda con el borde gris: el color se lo gana usándose --}}
<article
    style="--tipo: {{ $color }}; --tipo-suave: {{ $color }}55; border-color: {{ $vacio ? '#1e293b' : $color . '44' }}"
    class="group flex flex-col overflow-hidden rounded-2xl border bg-slate-900/50 transition duration-300 hover:-translate-y-0.5 hover:shadow-[0_16px_38px_-14px_var(--tipo-suave)]">

    {{-- ============ QUIÉN ES ============ --}}

    <a href="{{ route('entity-types.show', $tipo) }}" class="relative block p-4">

        {{-- Su color, de fondo --}}
        <span class="pointer-events-none absolute inset-0"
            style="background: radial-gradient(75% 130% at 12% 0%, {{ $color }}20, transparent 62%)"></span>

        <div class="relative flex items-start gap-3">

            <span class="h-14 w-14 shrink-0 overflow-hidden rounded-xl border bg-slate-950"
                style="border-color: {{ $color }}55">
                @if ($tipo->image_url)
                    <img src="{{ $tipo->image_url }}" alt="{{ $tipo->name }}" loading="lazy"
                        class="h-full w-full object-cover transition duration-300 group-hover:scale-110">
                @else
                    <span class="flex h-full w-full items-center justify-center text-2xl"
                        style="color: {{ $color }}">{{ $tipo->icon ?: '◇' }}</span>
                @endif
            </span>

            <div class="min-w-0 flex-1">

                <div class="flex flex-wrap items-center gap-1.5">
                    <span class="truncate text-[14px] font-black text-white transition group-hover:text-[color:var(--tipo)]">
                        {{ $tipo->name }}
                    </span>

                    @if ($tipo->status !== 'ACTIVE')
                        <span class="rounded px-1.5 py-0.5 text-[8px] font-black uppercase tracking-wider {{ $estadoTono[$tipo->status] ?? 'bg-slate-800 text-slate-500' }}">
                            {{ $tipo->status }}
                        </span>
                    @endif
                </div>

                <p class="font-mono text-[9px] text-slate-600">{{ $tipo->code }}</p>

                <p class="mt-1 line-clamp-2 text-[10px] leading-relaxed text-slate-500">
                    {{ $tipo->description ?: 'Sin descripción.' }}
                </p>
            </div>

            {{-- Cuántas lo llevan --}}
            <span class="shrink-0 rounded-xl border px-2.5 py-1.5 text-center"
                style="border-color: {{ $color }}33; background-color: {{ $color }}14">
                <span class="block font-mono text-lg font-black"
                    style="color: {{ $vacio ? '#475569' : $color }}">{{ $tipo->entities_count }}</span>
                <span class="block text-[8px] font-black uppercase tracking-wider text-slate-600">
                    {{ $tipo->entities_count === 1 ? 'entidad' : 'entidades' }}
                </span>
            </span>

        </div>

    </a>


    {{-- ============ LO QUE LO LLEVA PUESTO ============ --}}

    <div class="flex-1 px-4 pb-3">

        @if ($vacio)
            <p class="rounded-xl border border-dashed border-slate-800 px-3 py-3 text-center text-[10px] leading-4 text-slate-600">
                Todavía no lo lleva ninguna entidad.
                @can('create', App\Models\Entity::class)
                    <a href="{{ route('entities.create', ['type' => $tipo->id]) }}"
                        class="font-black transition hover:underline" style="color: {{ $color }}">
                        Crear la primera →
                    </a>
                @endcan
            </p>
        @else
            <div class="flex items-center gap-1.5">
                @foreach ($tipo->entities as $entidad)
                    <a href="{{ route('entities.show', $entidad) }}" title="{{ $entidad->name }}"
                        class="h-9 w-9 shrink-0 overflow-hidden rounded-lg border border-slate-800 bg-slate-950 transition hover:-translate-y-0.5 hover:border-slate-600">
                        @if ($entidad->image_url)
                            <img src="{{ $entidad->image_url }}" alt="" loading="lazy"
                                class="h-full w-full object-cover">
                        @else
                            <span class="flex h-full w-full items-center justify-center text-[10px] font-black text-slate-600">
                                {{ mb_strtoupper(mb_substr($entidad->name, 0, 1)) }}
                            </span>
                        @endif
                    </a>
                @endforeach

                @if ($tipo->entities_count > $tipo->entities->count())
                    <a href="{{ route('entity-types.show', $tipo) }}"
                        class="flex h-9 shrink-0 items-center rounded-lg border border-slate-800 bg-slate-950 px-2 font-mono text-[10px] font-black text-slate-500 transition hover:border-slate-600 hover:text-slate-300">
                        +{{ $tipo->entities_count - $tipo->entities->count() }}
                    </a>
                @endif
            </div>
        @endif

    </div>


    {{-- ============ QUÉ SE PUEDE HACER ============ --}}

    <div class="flex items-center gap-1 border-t border-slate-800 px-2 py-1.5">

        <a href="{{ route('entity-types.show', $tipo) }}"
            class="rounded-lg px-2 py-1 text-[10px] font-black text-slate-400 transition hover:text-white">
            Ver
        </a>

        @can('update', $tipo)
            <a href="{{ route('entity-types.edit', $tipo) }}"
                class="rounded-lg px-2 py-1 text-[10px] font-black text-slate-400 transition hover:text-amber-300">
                ✎ Editar
            </a>
        @endcan

        <a href="{{ route('entities.index', ['type' => $tipo->id]) }}"
            class="rounded-lg px-2 py-1 text-[10px] font-black text-slate-400 transition hover:text-indigo-300">
            Filtrar
        </a>

        @can('create', App\Models\Entity::class)
            <a href="{{ route('entities.create', ['type' => $tipo->id]) }}" title="Nueva entidad de este tipo"
                class="ml-auto rounded-lg px-2 py-1 text-[11px] font-black transition hover:opacity-80"
                style="color: {{ $color }}">
                +
            </a>
        @endcan

    </div>

</article>
