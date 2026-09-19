@php
    /*
     * Editar un valor de catálogo.
     *
     * Comparte el formulario con «nuevo». Aquí solo van la cabecera —con su cara
     * y la de su catálogo, para saber cuál se está tocando— y lo que solo existe
     * al editar: el aviso de a cuántos afecta el cambio, y el borrado.
     */

    $acento = $attributeOption->color ?: ($attributeOption->attribute?->color ?: '#6366f1');

    $enUso = $attributeOption->values_count;

    $conHijos = $attributeOption->children_count;
@endphp

<x-app-layout :title="'Editar ' . $attributeOption->name" surface="dark">

    <x-slot name="header">Catálogos</x-slot>

    <div class="space-y-4">

        {{-- ===================================================== --}}
        {{-- CABECERA --}}
        {{-- ===================================================== --}}

        <header class="flex flex-wrap items-center gap-3">

            <a href="{{ route('attribute-options.show', $attributeOption) }}"
                class="h-12 w-12 shrink-0 overflow-hidden rounded-xl border bg-slate-950"
                style="border-color: {{ $acento }}55">
                @if ($attributeOption->image_url)
                    <img src="{{ $attributeOption->image_url }}" alt="" class="h-full w-full object-cover">
                @else
                    <span class="flex h-full w-full items-center justify-center text-lg"
                        style="color: {{ $acento }}">{{ $attributeOption->icon ?: '◇' }}</span>
                @endif
            </a>

            <div class="min-w-0 flex-1">
                <a href="{{ route('attribute-options.show', $attributeOption) }}"
                    class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-600 transition hover:text-violet-400">
                    ← {{ $attributeOption->name }}
                </a>

                <h1 class="mt-0.5 truncate text-xl font-black tracking-tight text-white">
                    Editar este valor
                </h1>

                <p class="flex flex-wrap items-center gap-1.5 font-mono text-[10px] text-slate-600">
                    {{ $attributeOption->code }}
                    <span class="text-slate-700">·</span>
                    <a href="{{ route('attributes.show', $attributeOption->attribute) }}"
                        class="font-sans font-bold transition hover:underline"
                        style="color: {{ $attributeOption->attribute?->color ?: '#a78bfa' }}">
                        {{ $attributeOption->attribute?->name }}
                    </a>
                </p>
            </div>

            <a href="{{ route('attribute-options.show', $attributeOption) }}"
                class="rounded-xl border border-slate-800 bg-slate-900/50 px-3 py-2 text-[11px] font-black text-slate-400 transition hover:border-slate-700 hover:text-white">
                Cancelar
            </a>
        </header>


        @if ($errors->any())
            <div class="rounded-2xl border border-rose-500/30 bg-rose-500/10 px-4 py-3">
                <p class="text-[12px] font-black text-rose-200">Falta algo antes de poder guardar:</p>
                <ul class="mt-1 space-y-0.5 text-[11px] leading-relaxed text-rose-200/80">
                    @foreach ($errors->all() as $error)
                        <li>· {{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif


        {{-- ===================================================== --}}
        {{-- A QUIÉN AFECTA LO QUE TOQUES --}}
        {{-- ===================================================== --}}

        @if ($enUso > 0 || $conHijos > 0)
            <div class="flex flex-wrap items-center gap-3 rounded-2xl border border-amber-500/25 bg-amber-500/5 px-4 py-2.5">

                <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-amber-500/15 text-amber-300">
                    <x-omni-icon name="usuario" size="h-3.5 w-3.5" />
                </span>

                <p class="min-w-0 flex-1 text-[11px] leading-relaxed text-amber-200/80">
                    <strong class="text-amber-200">Esto ya está en uso.</strong>
                    @if ($enUso > 0)
                        Lo llevan <strong class="text-amber-200">{{ $enUso }}</strong>
                        {{ $enUso === 1 ? 'entidad' : 'entidades' }}, así que cambiarle el nombre o la
                        imagen les cambia lo que enseñan.
                    @endif
                    @if ($conHijos > 0)
                        Y <strong class="text-amber-200">{{ $conHijos }}</strong>
                        {{ $conHijos === 1 ? 'valor cuelga' : 'valores cuelgan' }} de él: si lo mueves, se
                        {{ $conHijos === 1 ? 'mueve' : 'mueven' }} con él.
                    @endif
                </p>
            </div>
        @endif


        {{-- ===================================================== --}}
        {{-- EL FORMULARIO --}}
        {{-- ===================================================== --}}

        <form method="POST"
            action="{{ route('attributes.options.update', [$attributeOption->attribute, $attributeOption]) }}"
            enctype="multipart/form-data">
            @csrf
            @method('PUT')

            @include('attribute-options.partials.form')
        </form>


        {{-- ===================================================== --}}
        {{-- ZONA DE PELIGRO --}}
        {{-- ===================================================== --}}

        @can('delete', $attributeOption)
            <section class="rounded-2xl border border-rose-500/25 bg-rose-500/5 p-4">

                <div class="flex flex-wrap items-center gap-3">

                    <div class="min-w-0 flex-1">
                        <h2 class="text-[12px] font-black text-rose-200">Eliminar este valor</h2>

                        <p class="mt-0.5 text-[10px] leading-relaxed text-rose-200/60">
                            @if ($enUso > 0 || $conHijos > 0)
                                @if ($enUso > 0)
                                    Lo llevan {{ $enUso }} {{ $enUso === 1 ? 'entidad' : 'entidades' }}, que
                                    se quedarán sin ese dato.
                                @endif
                                @if ($conHijos > 0)
                                    Y {{ $conHijos }} {{ $conHijos === 1 ? 'valor cuelga' : 'valores cuelgan' }}
                                    de él.
                                @endif
                                Si solo quieres que deje de poder elegirse,
                                <strong class="text-rose-200">archívalo</strong> en «Lo demás»: eso no borra
                                nada.
                            @else
                                No lo lleva ninguna entidad y no cuelga nadie de él, así que borrarlo no
                                rompe nada.
                            @endif
                        </p>
                    </div>

                    <form method="POST" action="{{ route('attribute-options.destroy', $attributeOption) }}"
                        data-omni-confirm data-confirm-variant="danger" data-confirm-title="Eliminar la opción" data-confirm-message="Se elimina del catálogo y deja de poder elegirse." data-confirm-subject="{{ $attributeOption->name }}" data-confirm-detail="No se puede deshacer." data-confirm-action="Sí, eliminarla">
                        @csrf
                        @method('DELETE')

                        <button type="submit"
                            class="rounded-xl border border-rose-500/40 px-4 py-2 text-[11px] font-black text-rose-300 transition hover:bg-rose-500 hover:text-white">
                            Eliminar
                        </button>
                    </form>

                </div>
            </section>
        @endcan

    </div>

</x-app-layout>
