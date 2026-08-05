<x-app-layout>
    <x-slot name="header">
        Atributos
    </x-slot>

    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
        <div>
            <h2 class="text-2xl font-black text-slate-900">
                Atributos
            </h2>

            <p class="mt-2 text-slate-500">
                Crea características reutilizables para tus entidades.
            </p>
        </div>

        <a
            href="{{ route('attributes.create') }}"
            class="inline-flex items-center justify-center rounded-xl bg-indigo-600 px-5 py-3 text-sm font-bold text-white shadow-lg shadow-indigo-600/20 hover:bg-indigo-700"
        >
            + Nuevo atributo
        </a>
    </div>

    <form
        method="GET"
        action="{{ route('attributes.index') }}"
        class="mt-6 grid gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:grid-cols-[1fr_220px_auto]"
    >
        <input
            type="text"
            name="search"
            value="{{ $search }}"
            placeholder="Buscar por nombre o código..."
            class="rounded-xl border-slate-300 focus:border-indigo-500 focus:ring-indigo-500"
        >

        <select
            name="data_type"
            class="rounded-xl border-slate-300 focus:border-indigo-500 focus:ring-indigo-500"
        >
            <option value="">Todos los tipos</option>

            @foreach ([
                'TEXT' => 'Texto corto',
                'LONG_TEXT' => 'Texto largo',
                'INTEGER' => 'Número entero',
                'DECIMAL' => 'Número decimal',
                'BOOLEAN' => 'Sí o no',
                'DATE' => 'Fecha',
                'COLOR' => 'Color',
                'OPTION' => 'Catálogo de opciones',
            ] as $value => $label)
                <option
                    value="{{ $value }}"
                    @selected($dataType === $value)
                >
                    {{ $label }}
                </option>
            @endforeach
        </select>

        <button
            type="submit"
            class="rounded-xl bg-slate-900 px-5 py-3 text-sm font-semibold text-white hover:bg-slate-800"
        >
            Buscar
        </button>
    </form>

    <div class="mt-6 grid gap-5 sm:grid-cols-2 xl:grid-cols-3">
        @forelse ($attributes as $attribute)
            <article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm transition hover:-translate-y-1 hover:shadow-lg">
                <div class="flex items-start justify-between gap-4">
                    <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-indigo-50 text-xl font-black text-indigo-600">
                        @switch($attribute->data_type)
                            @case('OPTION')
                                ☷
                                @break

                            @case('INTEGER')
                            @case('DECIMAL')
                                #
                                @break

                            @case('BOOLEAN')
                                ✓
                                @break

                            @case('DATE')
                                ◫
                                @break

                            @case('COLOR')
                                ◉
                                @break

                            @default
                                T
                        @endswitch
                    </div>

                    <x-status-badge :status="$attribute->status" />
                </div>

                <p class="mt-5 text-xs font-semibold uppercase tracking-wider text-indigo-600">
                    {{ str_replace('_', ' ', $attribute->data_type) }}
                </p>

                <h3 class="mt-2 text-xl font-black text-slate-900">
                    {{ $attribute->name }}
                </h3>

                <p class="mt-1 text-xs font-semibold text-slate-400">
                    {{ $attribute->code }}
                </p>

                <p class="mt-4 line-clamp-3 text-sm leading-6 text-slate-500">
                    {{ $attribute->description ?: 'Sin descripción.' }}
                </p>

                <div class="mt-5 grid grid-cols-2 gap-3">
                    <div class="rounded-xl bg-slate-50 p-3">
                        <p class="text-xs text-slate-500">
                            Opciones
                        </p>

                        <p class="mt-1 font-black text-slate-900">
                            {{ $attribute->options_count }}
                        </p>
                    </div>

                    <div class="rounded-xl bg-slate-50 p-3">
                        <p class="text-xs text-slate-500">
                            Usos
                        </p>

                        <p class="mt-1 font-black text-slate-900">
                            {{ $attribute->entity_attributes_count }}
                        </p>
                    </div>
                </div>

                <div class="mt-5 flex items-center justify-between border-t border-slate-100 pt-4">
                    <div class="flex flex-wrap gap-1">
                        @if ($attribute->allows_multiple)
                            <span class="rounded-full bg-violet-100 px-2 py-1 text-[10px] font-bold text-violet-700">
                                Múltiple
                            </span>
                        @endif

                        @if ($attribute->is_required)
                            <span class="rounded-full bg-red-100 px-2 py-1 text-[10px] font-bold text-red-700">
                                Obligatorio
                            </span>
                        @endif
                    </div>

                    <a
                        href="{{ route('attributes.show', $attribute) }}"
                        class="text-sm font-bold text-indigo-600 hover:text-indigo-800"
                    >
                        Abrir →
                    </a>
                </div>
            </article>
        @empty
            <div class="sm:col-span-2 xl:col-span-3 rounded-2xl border border-dashed border-slate-300 bg-white py-20 text-center">
                <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-indigo-50 text-2xl text-indigo-600">
                    ☷
                </div>

                <h3 class="mt-5 font-bold text-slate-800">
                    Todavía no tienes atributos
                </h3>

                <p class="mt-2 text-sm text-slate-500">
                    Crea atributos como Elemento, Poder, País, Anime o Habilidades.
                </p>

                <a
                    href="{{ route('attributes.create') }}"
                    class="mt-5 inline-block rounded-xl bg-indigo-600 px-5 py-3 text-sm font-bold text-white"
                >
                    Crear primer atributo
                </a>
            </div>
        @endforelse
    </div>

    <div class="mt-8">
        {{ $attributes->links() }}
    </div>
</x-app-layout>