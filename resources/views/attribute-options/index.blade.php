<x-app-layout>
    <x-slot name="header">
        Catálogos
    </x-slot>

    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
        <div>
            <h2 class="text-2xl font-black text-slate-900">
                Catálogos
            </h2>

            <p class="mt-2 max-w-2xl text-slate-500">
                Administra los valores seleccionables de atributos como
                Anime, Elemento, País, Tipo de chakra o Rareza.
            </p>
        </div>

        <a
            href="{{ route('attribute-options.create') }}"
            class="rounded-xl bg-indigo-600 px-5 py-3 text-center text-sm font-bold text-white shadow-lg shadow-indigo-600/20 hover:bg-indigo-700"
        >
            + Nuevo elemento
        </a>
    </div>

    <form
        method="GET"
        class="mt-6 grid gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:grid-cols-[1fr_260px_auto]"
    >
        <input
            type="text"
            name="search"
            value="{{ $search }}"
            placeholder="Buscar Naruto, Fuego, Perú..."
            class="rounded-xl border-slate-300 focus:border-indigo-500 focus:ring-indigo-500"
        >

        <select
            name="attribute"
            class="rounded-xl border-slate-300 focus:border-indigo-500 focus:ring-indigo-500"
        >
            <option value="">
                Todos los atributos
            </option>

            @foreach ($attributes as $attribute)
                <option
                    value="{{ $attribute->id }}"
                    @selected(
                        $attributeId == $attribute->id
                    )
                >
                    {{ $attribute->name }}
                </option>
            @endforeach
        </select>

        <button
            class="rounded-xl bg-slate-900 px-5 py-3 text-sm font-semibold text-white"
        >
            Buscar
        </button>
    </form>

    <div class="mt-6 grid gap-6 sm:grid-cols-2 xl:grid-cols-3">
        @forelse ($options as $option)
            <article class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm transition hover:-translate-y-1 hover:shadow-xl">
                <div
                    class="relative aspect-[16/9]"
                    style="
                        background: linear-gradient(
                            135deg,
                            {{ $option->color ?? '#EEF2FF' }}35,
                            #F8FAFC
                        );
                    "
                >
                    @if ($option->image_url)
                        <img
                            src="{{ $option->image_url }}"
                            alt="{{ $option->name }}"
                            class="h-full w-full object-cover"
                        >

                        <div class="absolute inset-0 bg-gradient-to-t from-slate-950/60 to-transparent"></div>
                    @else
                        <div class="flex h-full items-center justify-center text-6xl">
                            {{ $option->icon ?: '◆' }}
                        </div>
                    @endif

                    <div class="absolute left-4 top-4 rounded-full bg-white/90 px-3 py-1 text-xs font-bold text-indigo-700 backdrop-blur">
                        {{ $option->attribute->name }}
                    </div>
                </div>

                <div class="p-6">
                    <h3 class="text-xl font-black text-slate-900">
                        {{ $option->name }}
                    </h3>

                    <p class="mt-1 text-xs font-semibold uppercase tracking-wide text-slate-400">
                        {{ $option->code }}
                    </p>

                    <p class="mt-4 line-clamp-3 text-sm leading-6 text-slate-500">
                        {{ $option->description ?: 'Sin descripción.' }}
                    </p>

                    @if ($option->parent)
                        <p class="mt-3 text-xs text-slate-500">
                            Depende de:
                            <strong>
                                {{ $option->parent->name }}
                            </strong>
                        </p>
                    @endif

                    <div class="mt-5 flex items-center justify-between border-t border-slate-100 pt-4">
                        <span class="text-xs text-slate-400">
                            {{ $option->children_count }}
                            subopción(es)
                        </span>

                        <a
                            href="{{ route(
                                'attribute-options.show',
                                $option
                            ) }}"
                            class="text-sm font-bold text-indigo-600"
                        >
                            Abrir →
                        </a>
                    </div>
                </div>
            </article>
        @empty
            <div class="sm:col-span-2 xl:col-span-3 rounded-2xl border border-dashed border-slate-300 bg-white py-20 text-center">
                <p class="font-bold text-slate-700">
                    Todavía no existen elementos de catálogo
                </p>

                <p class="mt-2 text-sm text-slate-500">
                    Primero crea un atributo seleccionable y luego añade sus valores.
                </p>
            </div>
        @endforelse
    </div>

    <div class="mt-8">
        {{ $options->links() }}
    </div>
</x-app-layout>