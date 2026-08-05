<x-app-layout>
    <x-slot name="header">
        Crear grupo de atributos
    </x-slot>

    <div class="mb-6">
        <h2 class="text-2xl font-black text-slate-900">
            Nuevo grupo
        </h2>

        <p class="mt-2 text-slate-500">
            Agrupa atributos para organizar la presentación de las entidades.
        </p>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
        <form
            method="POST"
            action="{{ route('attribute-groups.store') }}"
        >
            @csrf

            @include('attribute-groups.partials.form')
        </form>
    </div>
</x-app-layout>