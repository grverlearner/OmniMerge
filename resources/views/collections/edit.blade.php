@php
    /*
     * Editar una colección.
     *
     * Comparte el formulario con «nueva», así que aquí solo va la cabecera —con
     * su cara y su color, para saber cuál se está tocando— y el borrado, que es
     * lo único que no existe al crearla.
     */

    $acento = $collection->color ?: '#8b5cf6';
@endphp

<x-app-layout :title="'Editar ' . $collection->name" surface="dark">

    <x-slot name="header">Colecciones</x-slot>

    <div class="space-y-4">

        {{-- ===================================================== --}}
        {{-- CABECERA --}}
        {{-- ===================================================== --}}

        <header class="flex flex-wrap items-center gap-3">

            <a href="{{ route('collections.show', $collection) }}"
                class="h-12 w-12 shrink-0 overflow-hidden rounded-xl border bg-slate-950"
                style="border-color: {{ $acento }}55">
                @if ($collection->image_url)
                    <img src="{{ $collection->image_url }}" alt="" class="h-full w-full object-cover">
                @else
                    <span class="flex h-full w-full items-center justify-center text-lg"
                        style="color: {{ $acento }}">{{ $collection->icon ?: '◫' }}</span>
                @endif
            </a>

            <div class="min-w-0 flex-1">
                <a href="{{ route('collections.show', $collection) }}"
                    class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-600 transition hover:text-violet-400">
                    ← {{ $collection->name }}
                </a>

                <h1 class="mt-0.5 truncate text-xl font-black tracking-tight text-white">
                    Editar la colección
                </h1>

                <p class="font-mono text-[10px] text-slate-600">{{ $collection->code }}</p>
            </div>

            <a href="{{ route('collections.show', $collection) }}"
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


        <form method="POST" action="{{ route('collections.update', $collection) }}" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            @include('collections.partials.form')
        </form>


        {{-- ===================================================== --}}
        {{-- ZONA DE PELIGRO --}}
        {{-- ===================================================== --}}

        @can('delete', $collection)
            <section class="rounded-2xl border border-rose-500/25 bg-rose-500/5 p-4">

                <div class="flex flex-wrap items-center gap-3">

                    <div class="min-w-0 flex-1">
                        <h2 class="text-[12px] font-black text-rose-200">Eliminar esta colección</h2>

                        <p class="mt-0.5 text-[10px] leading-relaxed text-rose-200/60">
                            Se borra la agrupación, no las entidades:
                            @if ($collection->entities->isNotEmpty())
                                las <strong>{{ $collection->entities->count() }}</strong> que hay dentro
                                siguen en tu biblioteca exactamente igual.
                            @else
                                está vacía, así que no afecta a nada.
                            @endif
                        </p>
                    </div>

                    <form method="POST" action="{{ route('collections.destroy', $collection) }}"
                        onsubmit="return confirm('Se elimina la colección «{{ $collection->name }}». Las entidades no se tocan. ¿Seguro?')">
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
