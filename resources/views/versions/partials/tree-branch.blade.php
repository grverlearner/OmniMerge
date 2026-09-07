@php
    /*
     * Una rama del árbol de definiciones.
     *
     * Se llama a sí misma para cada hija. La sangría dice la profundidad y la
     * línea vertical la ata a su padre: sin esa línea, tres niveles de
     * sangría se leen como tres listas sueltas.
     *
     * El controlador carga `children.children`, así que el árbol llega hasta
     * el tercer nivel sin más consultas; más allá, la rama enlaza a la ficha.
     */

    $nivel = $nivel ?? 0;

    $hijas = $nodo->relationLoaded('children') ? $nodo->children : collect();

    $sinUsar = ($nodo->entity_versions_count ?? 0) === 0;
@endphp

<div @if ($nivel > 0) class="relative pl-5" @endif>

    @if ($nivel > 0)
        {{-- La línea que la ata a su padre --}}
        <span class="absolute left-1.5 top-0 h-full w-px bg-slate-800"></span>
        <span class="absolute left-1.5 top-4 h-px w-3 bg-slate-800"></span>
    @endif

    <a href="{{ route('versions.show', $nodo) }}"
        class="flex items-center gap-2.5 rounded-xl border bg-slate-950/60 px-2.5 py-2 transition hover:bg-slate-900 {{ $sinUsar ? 'border-slate-800' : 'border-violet-500/25' }}">

        <span class="h-8 w-8 shrink-0 overflow-hidden rounded-lg border border-slate-800 bg-slate-950">
            @if ($nodo->image_url)
                <img src="{{ $nodo->image_url }}" alt="" loading="lazy" class="h-full w-full object-cover">
            @else
                <span class="flex h-full w-full items-center justify-center text-[11px] text-violet-400">◈</span>
            @endif
        </span>

        <span class="min-w-0 flex-1">
            <span class="flex flex-wrap items-center gap-1.5">
                <span class="truncate text-[11px] font-black text-white">{{ $nodo->name }}</span>

                @if ($nodo->status !== 'ACTIVE')
                    <span class="rounded px-1.5 py-0.5 text-[8px] font-black uppercase tracking-wider {{ $estadoTono[$nodo->status] ?? 'bg-slate-800 text-slate-500' }}">
                        {{ $nodo->status_label }}
                    </span>
                @endif
            </span>

            <span class="block truncate text-[9px] text-slate-600">
                {{ $nodo->kind_label }} · {{ $nodo->scope_label }}
            </span>
        </span>

        <span class="shrink-0 rounded-lg border px-2 py-1 font-mono text-[10px] font-black {{ $sinUsar ? 'border-slate-800 text-slate-700' : 'border-violet-500/25 bg-violet-500/5 text-violet-300' }}">
            {{ $nodo->entity_versions_count ?? 0 }}
        </span>
    </a>

    @if ($hijas->isNotEmpty())
        <div class="mt-1.5 space-y-1.5">
            @foreach ($hijas as $hija)
                @include('versions.partials.tree-branch', [
                    'nodo' => $hija,
                    'nivel' => $nivel + 1,
                    'estadoTono' => $estadoTono,
                ])
            @endforeach
        </div>
    @endif

</div>
