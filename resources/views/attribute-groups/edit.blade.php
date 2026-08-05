<x-app-layout>
    <x-slot name="header">
        Editar grupo
    </x-slot>

    <div class="mb-6">
        <h2 class="text-2xl font-black text-slate-900">
            Editar {{ $attributeGroup->name }}
        </h2>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
        <form
            method="POST"
            action="{{ route(
                'attribute-groups.update',
                $attributeGroup
            ) }}"
        >
            @csrf
            @method('PUT')

            @include('attribute-groups.partials.form')
        </form>
    </div>
</x-app-layout>