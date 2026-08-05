<x-app-layout>
    <x-slot name="header">
        Crear colección
    </x-slot>

    <div class="mb-6">
        <h2 class="text-2xl font-black text-slate-900">
            Nueva colección
        </h2>

        <p class="mt-2 text-slate-500">
            Agrupa entidades por temática, universo, origen o cualquier criterio.
        </p>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
        <form
            method="POST"
            action="{{ route('collections.store') }}"
            enctype="multipart/form-data"
        >
            @csrf

            @include('collections.partials.form')
        </form>
    </div>
</x-app-layout>