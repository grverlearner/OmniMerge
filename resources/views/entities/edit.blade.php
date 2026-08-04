<x-app-layout>
    <x-slot name="header">
        Editar entidad
    </x-slot>

    <div class="mb-6">
        <h2 class="text-2xl font-black text-slate-900">
            Editar {{ $entity->name }}
        </h2>

        <p class="mt-2 text-slate-500">
            Actualiza la información general de la entidad.
        </p>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
        <form
            method="POST"
            action="{{ route('entities.update', $entity) }}"
            enctype="multipart/form-data"
        >
            @csrf
            @method('PUT')

            @include('entities.partials.form')
        </form>
    </div>
</x-app-layout>