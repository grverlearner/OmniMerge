<x-app-layout>
    <x-slot name="header">
        Editar opción
    </x-slot>

    <div class="mb-6 flex flex-col justify-between gap-4 sm:flex-row sm:items-start">
        <div>
            <p class="text-sm font-semibold uppercase tracking-wider text-indigo-600">
                {{ $attributeOption->attribute->name }}
            </p>

            <h2 class="mt-2 text-2xl font-black text-slate-900">
                Editar {{ $attributeOption->name }}
            </h2>

            <p class="mt-2 text-slate-500">
                Modifica la imagen, descripción, jerarquía y presentación
                de esta opción.
            </p>
        </div>

        <a
            href="{{ route(
                'attribute-options.show',
                $attributeOption
            ) }}"
            class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50"
        >
            Volver al detalle
        </a>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
        <form
            method="POST"
            action="{{ route(
                'attributes.options.update',
                [
                    $attributeOption->attribute,
                    $attributeOption,
                ]
            ) }}"
            enctype="multipart/form-data"
        >
            @csrf
            @method('PUT')

            @include('attribute-options.partials.form')
        </form>
    </div>
</x-app-layout>