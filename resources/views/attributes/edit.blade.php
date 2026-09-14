@php
    /*
     * Editar un atributo.
     *
     * Comparte el formulario con «nuevo». Aquí solo van la cabecera —con su
     * cara y su color, para saber cuál se está tocando— y lo que solo existe al
     * editar: el aviso de que el tipo está bloqueado si ya tiene datos, y el
     * borrado.
     */

    $acento = $attribute->color ?: '#6366f1';
@endphp

<x-app-layout :title="'Editar ' . $attribute->name" surface="dark">

    <x-slot name="header">Atributos</x-slot>

    <div class="space-y-4">

        {{-- ===================================================== --}}
        {{-- CABECERA --}}
        {{-- ===================================================== --}}

        <header class="flex flex-wrap items-center gap-3">

            <a href="{{ route('attributes.show', $attribute) }}"
                class="h-12 w-12 shrink-0 overflow-hidden rounded-xl border bg-slate-950"
                style="border-color: {{ $acento }}55">
                @if ($attribute->image_url)
                    <img src="{{ $attribute->image_url }}" alt="" class="h-full w-full object-cover">
                @else
                    <span class="flex h-full w-full items-center justify-center text-lg"
                        style="color: {{ $acento }}">{{ $attribute->icon ?: $attribute->data_type_icon }}</span>
                @endif
            </a>

            <div class="min-w-0 flex-1">
                <a href="{{ route('attributes.show', $attribute) }}"
                    class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-600 transition hover:text-violet-400">
                    ← {{ $attribute->name }}
                </a>

                <h1 class="mt-0.5 truncate text-xl font-black tracking-tight text-white">
                    Editar el atributo
                </h1>

                <p class="font-mono text-[10px] text-slate-600">
                    {{ $attribute->code }}
                    <span class="text-slate-700">·</span>
                    {{ $attribute->data_type_label }}
                </p>
            </div>

            <a href="{{ route('attributes.show', $attribute) }}"
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
        {{-- POR QUÉ EL TIPO ESTÁ BLOQUEADO --}}
        {{-- ===================================================== --}}

        @if ($typeLocked)
            <div class="flex flex-wrap items-center gap-3 rounded-2xl border border-amber-500/25 bg-amber-500/5 px-4 py-2.5">
                <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-amber-500/15 text-amber-300">
                    <x-omni-icon name="controles" size="h-3.5 w-3.5" />
                </span>

                <p class="min-w-0 flex-1 text-[11px] leading-relaxed text-amber-200/80">
                    <strong class="text-amber-200">El tipo no se puede cambiar</strong> porque ya tiene datos:
                    {{ $attribute->options_count }}
                    {{ $attribute->options_count === 1 ? 'valor' : 'valores' }}
                    y lo usan {{ $attribute->entity_attributes_count }}
                    {{ $attribute->entity_attributes_count === 1 ? 'entidad' : 'entidades' }}.
                    Cambiarlo dejaría esos datos sin sentido. Todo lo demás sí se puede tocar.
                </p>
            </div>
        @endif


        <form method="POST" action="{{ route('attributes.update', $attribute) }}" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            @include('attributes.partials.form')
        </form>


        {{-- ===================================================== --}}
        {{-- ZONA DE PELIGRO --}}
        {{-- ===================================================== --}}

        @can('delete', $attribute)
            <section class="rounded-2xl border border-rose-500/25 bg-rose-500/5 p-4">

                <div class="flex flex-wrap items-center gap-3">

                    <div class="min-w-0 flex-1">
                        <h2 class="text-[12px] font-black text-rose-200">Eliminar este atributo</h2>

                        <p class="mt-0.5 text-[10px] leading-relaxed text-rose-200/60">
                            @if ($attribute->entity_attributes_count > 0)
                                Lo usan <strong>{{ $attribute->entity_attributes_count }}</strong>
                                {{ $attribute->entity_attributes_count === 1 ? 'entidad' : 'entidades' }}
                                @if ($attribute->options_count > 0)
                                    y tiene <strong>{{ $attribute->options_count }}</strong>
                                    {{ $attribute->options_count === 1 ? 'valor' : 'valores' }}
                                @endif
                                . Borrarlo se lleva por delante todo eso.
                            @elseif ($attribute->options_count > 0)
                                Tiene <strong>{{ $attribute->options_count }}</strong>
                                {{ $attribute->options_count === 1 ? 'valor' : 'valores' }}, que también se
                                borran. Ninguna entidad lo usa todavía.
                            @else
                                No lo usa nadie y no tiene valores, así que borrarlo no rompe nada.
                            @endif
                        </p>
                    </div>

                    <form method="POST" action="{{ route('attributes.destroy', $attribute) }}"
                        onsubmit="return confirm('Se elimina el atributo «{{ $attribute->name }}» y todos sus valores. ¿Seguro?')">
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
