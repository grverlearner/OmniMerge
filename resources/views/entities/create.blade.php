<x-app-layout>
    <x-slot name="header">
        Crear entidad
    </x-slot>

    <div class="mb-6">
        <h2 class="text-2xl font-black text-slate-900">
            Nueva entidad
        </h2>

        <p class="mt-2 text-slate-500">
            Crea un personaje, país, animal, objeto, concepto
            o cualquier elemento que imagines.
        </p>
    </div>

    @if ($entityTypes->isEmpty())
        <div class="mb-6 rounded-2xl border border-amber-200 bg-amber-50 p-5 text-amber-900">
            <p class="font-semibold">
                Todavía no tienes tipos de entidad
            </p>

            <p class="mt-1 text-sm">
                Puedes crear la entidad sin tipo o configurar uno primero.
            </p>

            <a
                href="{{ route('entity-types.create') }}"
                class="mt-3 inline-block text-sm font-bold underline"
            >
                Crear un tipo
            </a>
        </div>
    @endif

    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
        <form
            method="POST"
            action="{{ route('entities.store') }}"
            enctype="multipart/form-data"
        >
            @csrf

            @include('entities.partials.form')
        </form>
    </div>
</x-app-layout>