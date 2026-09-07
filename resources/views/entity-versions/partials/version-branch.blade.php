@php
    /*
     * Una rama del árbol de versiones de una entidad.
     *
     * Se llama a sí misma para cada hija. Una versión puede colgar de otra
     * —«Modo Sabio» hereda de «Shippuden» en vez de la entidad original— y eso
     * solo se ve aquí: en cuadrícula o en lista todas parecen hermanas.
     *
     * La línea vertical es la que hace legible la sangría: sin ella, tres
     * niveles se leen como tres listas sueltas.
     */

    $nivel = $nivel ?? 0;

    $hijas = $todas->where('parent_entity_version_id', $nodo->id);

    $esBase = (bool) $nodo->baseSetting;
@endphp

<div @if ($nivel > 0) class="relative pl-5" @endif>

    @if ($nivel > 0)
        <span class="absolute left-1.5 top-0 h-full w-px bg-slate-800"></span>
        <span class="absolute left-1.5 top-6 h-px w-3 bg-slate-800"></span>
    @endif

    <div class="flex items-center gap-2.5 rounded-xl border px-2.5 py-2 transition hover:bg-slate-900 {{ $esBase ? 'border-amber-500/40 bg-amber-500/5' : 'border-slate-800 bg-slate-950/60' }}">

        <a href="{{ route('entity-versions.show', [$entity, $nodo]) }}"
            class="h-9 w-9 shrink-0 overflow-hidden rounded-lg border border-slate-800 bg-slate-950">
            @if ($nodo->image_url)
                <img src="{{ $nodo->image_url }}" alt="" loading="lazy" class="h-full w-full object-cover">
            @else
                <span class="flex h-full w-full items-center justify-center text-slate-700">◈</span>
            @endif
        </a>

        <div class="min-w-0 flex-1">
            <div class="flex flex-wrap items-center gap-1.5">
                <a href="{{ route('entity-versions.show', [$entity, $nodo]) }}"
                    class="truncate text-[12px] font-black text-white transition hover:text-violet-300">
                    {{ $nodo->name }}
                </a>

                @if ($esBase)
                    <span class="rounded bg-amber-400 px-1 text-[8px] font-black text-amber-950">★ BASE</span>
                @endif

                @if ($nodo->status !== 'ACTIVE')
                    <span class="rounded px-1.5 py-0.5 text-[8px] font-black uppercase tracking-wider {{ $estadoTono[$nodo->status] ?? 'bg-slate-800 text-slate-500' }}">
                        {{ $nodo->status_label }}
                    </span>
                @endif
            </div>

            <p class="truncate text-[9px] text-violet-400">{{ $nodo->version?->name }}</p>
        </div>

        <span class="hidden shrink-0 items-center gap-1 sm:flex">
            <span class="rounded-lg border px-1.5 py-0.5 font-mono text-[10px] font-black {{ $nodo->version_attributes_count > 0 ? 'border-violet-500/25 text-violet-300' : 'border-slate-800 text-slate-700' }}"
                title="Características propias">{{ $nodo->version_attributes_count }}</span>

            <span class="rounded-lg border px-1.5 py-0.5 font-mono text-[10px] font-black {{ $nodo->images_count > 0 ? 'border-fuchsia-500/25 text-fuchsia-300' : 'border-slate-800 text-slate-700' }}"
                title="Imágenes de galería">{{ $nodo->images_count }}</span>
        </span>

        @can('update', $nodo)
            <a href="{{ route('entity-versions.edit', [$entity, $nodo]) }}"
                class="shrink-0 rounded-lg px-1.5 py-1 text-[10px] font-black text-slate-500 transition hover:text-amber-300">✎</a>
        @endcan
    </div>

    @if ($hijas->isNotEmpty())
        <div class="mt-1.5 space-y-1.5">
            @foreach ($hijas as $hija)
                @include('entity-versions.partials.version-branch', [
                    'nodo' => $hija,
                    'nivel' => $nivel + 1,
                    'todas' => $todas,
                    'entity' => $entity,
                    'estadoTono' => $estadoTono,
                ])
            @endforeach
        </div>
    @endif

</div>
