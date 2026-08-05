<x-app-layout>
    <x-slot name="header">
        Crear opción
    </x-slot>

    <div class="mb-6">
        <h2 class="text-2xl font-black text-slate-900">
            Nueva opción seleccionable
        </h2>

        <p class="mt-2 max-w-2xl text-slate-500">
            Crea un valor para un atributo de catálogo, como Naruto
            para Anime, Fuego para Elemento o Perú para País.
        </p>
    </div>

    @if ($attributes->isEmpty())
        <div class="rounded-2xl border border-amber-200 bg-amber-50 p-6 text-amber-900">
            <h3 class="font-bold">
                No tienes atributos seleccionables
            </h3>

            <p class="mt-2 text-sm">
                Primero debes crear un atributo de tipo
                <strong>OPTION</strong> o con origen
                <strong>CATALOG</strong>.
            </p>

            <a
                href="{{ route('attributes.create') }}"
                class="mt-4 inline-flex rounded-xl bg-amber-600 px-5 py-3 text-sm font-bold text-white hover:bg-amber-700"
            >
                Crear atributo
            </a>
        </div>
    @else
        <div class="mb-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <label
                for="attribute_selector"
                class="mb-2 block text-sm font-semibold text-slate-700"
            >
                Selecciona el atributo al que pertenecerá la opción
            </label>

            <select
                id="attribute_selector"
                onchange="
                    if (this.value) {
                        window.location.href =
                            '{{ route('attribute-options.create') }}'
                            + '?attribute=' + this.value;
                    }
                "
                class="w-full rounded-xl border-slate-300 focus:border-indigo-500 focus:ring-indigo-500"
            >
                <option value="">
                    Seleccionar atributo
                </option>

                @foreach ($attributes as $attribute)
                    <option
                        value="{{ $attribute->id }}"
                        @selected(
                            $selectedAttribute?->id === $attribute->id
                        )
                    >
                        {{ $attribute->name }}
                        —
                        {{ $attribute->allows_multiple
                            ? 'Selección múltiple'
                            : 'Selección única' }}
                    </option>
                @endforeach
            </select>
        </div>

        @if ($selectedAttribute)
            <div class="mb-6 rounded-2xl border border-indigo-200 bg-indigo-50 p-5">
                <div class="flex items-start gap-4">
                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-indigo-100 text-xl text-indigo-700">
                        {{ $selectedAttribute->icon ?: '☷' }}
                    </div>

                    <div>
                        <p class="text-sm font-semibold text-indigo-700">
                            Atributo seleccionado
                        </p>

                        <h3 class="mt-1 text-lg font-black text-indigo-950">
                            {{ $selectedAttribute->name }}
                        </h3>

                        <p class="mt-1 text-sm text-indigo-700">
                            {{ $selectedAttribute->allows_multiple
                                ? 'Este atributo permite seleccionar varias opciones.'
                                : 'Este atributo permite seleccionar una sola opción.' }}
                        </p>
                    </div>
                </div>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
                <form
                    method="POST"
                    action="{{ route(
                        'attributes.options.store',
                        $selectedAttribute
                    ) }}"
                    enctype="multipart/form-data"
                >
                    @csrf

                    @include('attribute-options.partials.form')
                </form>
            </div>
        @else
            <div class="rounded-2xl border border-dashed border-slate-300 bg-white py-16 text-center">
                <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-indigo-50 text-2xl text-indigo-600">
                    ◆
                </div>

                <h3 class="mt-5 font-bold text-slate-800">
                    Selecciona un atributo
                </h3>

                <p class="mt-2 text-sm text-slate-500">
                    Luego podrás crear su nueva opción.
                </p>
            </div>
        @endif
    @endif
</x-app-layout>