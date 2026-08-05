<x-app-layout>
    <x-slot name="header">
        Crear atributo
    </x-slot>

    <div class="mb-6">
        <h2 class="text-2xl font-black">
            Nuevo atributo
        </h2>

        <p class="mt-2 text-slate-500">
            Define una característica reutilizable.
        </p>
    </div>

    <div class="rounded-2xl border bg-white p-8 shadow-sm">
        <form
            method="POST"
            action="{{ route('attributes.store') }}"
        >
            @csrf

            @include('attributes.partials.form')
        </form>
    </div>
</x-app-layout>