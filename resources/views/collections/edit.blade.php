<x-app-layout>
    <x-slot name="header">
        Editar colección
    </x-slot>

    <div class="mb-6">
        <h2 class="text-2xl font-black text-slate-900">
            Editar {{ $collection->name }}
        </h2>

        <p class="mt-2 text-slate-500">
            Modifica la información y las entidades de la colección.
        </p>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
        <form
            method="POST"
            action="{{ route('collections.update', $collection) }}"
            enctype="multipart/form-data"
        >
            @csrf
            @method('PUT')

            @include('collections.partials.form')
        </form>
    </div>
</x-app-layout>