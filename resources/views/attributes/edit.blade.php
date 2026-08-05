<x-app-layout>
    <x-slot name="header">
        Editar atributo
    </x-slot>

    <div class="mb-6">
        <h2 class="text-2xl font-black text-slate-900">
            Editar {{ $attribute->name }}
        </h2>

        <p class="mt-2 text-slate-500">
            Modifica la configuración del atributo.
        </p>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
        <form
            method="POST"
            action="{{ route('attributes.update', $attribute) }}"
        >
            @csrf
            @method('PUT')

            @include('attributes.partials.form')
        </form>
    </div>
</x-app-layout>