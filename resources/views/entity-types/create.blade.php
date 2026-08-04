<x-app-layout>
    <x-slot name="header">
        Crear tipo de entidad
    </x-slot>

    <div class="mb-6">
        <h2 class="text-2xl font-black text-slate-900">
            Nuevo tipo de entidad
        </h2>

        <p class="mt-2 text-slate-500">
            Define una categoría reutilizable como personaje,
            país, objeto, criatura o concepto.
        </p>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
        <form
            method="POST"
            action="{{ route('entity-types.store') }}"
        >
            @csrf

            @include('entity-types.partials.form')
        </form>
    </div>
</x-app-layout>