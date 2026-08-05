<x-app-layout>
    <x-slot name="header">
        Detalle del grupo
    </x-slot>

    <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
        <div class="flex flex-col justify-between gap-6 sm:flex-row">
            <div class="flex items-start gap-5">
                <div
                    class="flex h-16 w-16 items-center justify-center rounded-2xl text-2xl"
                    style="
                        background-color: {{ $attributeGroup->color ?? '#6366F1' }}20;
                        color: {{ $attributeGroup->color ?? '#6366F1' }};
                    "
                >
                    {{ $attributeGroup->icon ?: '▥' }}
                </div>

                <div>
                    <div class="flex flex-wrap items-center gap-3">
                        <h2 class="text-3xl font-black text-slate-900">
                            {{ $attributeGroup->name }}
                        </h2>

                        <x-status-badge :status="$attributeGroup->status" />
                    </div>

                    <p class="mt-2 text-sm font-semibold text-slate-400">
                        {{ $attributeGroup->code }}
                    </p>

                    <p class="mt-4 max-w-2xl leading-7 text-slate-600">
                        {{ $attributeGroup->description ?: 'Sin descripción.' }}
                    </p>
                </div>
            </div>

            <div class="flex gap-2">
                <a
                    href="{{ route(
                        'attribute-groups.edit',
                        $attributeGroup
                    ) }}"
                    class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-semibold"
                >
                    Editar
                </a>

                <form
                    method="POST"
                    action="{{ route(
                        'attribute-groups.destroy',
                        $attributeGroup
                    ) }}"
                    onsubmit="return confirm('¿Eliminar este grupo?')"
                >
                    @csrf
                    @method('DELETE')

                    <button
                        type="submit"
                        class="rounded-xl border border-red-200 px-4 py-2.5 text-sm font-semibold text-red-600"
                    >
                        Eliminar
                    </button>
                </form>
            </div>
        </div>
    </section>

    <section class="mt-8">
        <h3 class="text-xl font-black text-slate-900">
            Atributos incluidos
        </h3>

        <div class="mt-5 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @forelse ($attributeGroup->attributes as $attribute)
                <a
                    href="{{ route('attributes.show', $attribute) }}"
                    class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-1 hover:shadow-lg"
                >
                    <p class="font-black text-slate-900">
                        {{ $attribute->name }}
                    </p>

                    <p class="mt-1 text-xs font-semibold text-slate-400">
                        {{ $attribute->data_type }}
                    </p>

                    <p class="mt-3 line-clamp-2 text-sm text-slate-500">
                        {{ $attribute->description ?: 'Sin descripción.' }}
                    </p>
                </a>
            @empty
                <p class="sm:col-span-2 lg:col-span-3 rounded-2xl border border-dashed border-slate-300 bg-white p-10 text-center text-slate-500">
                    Este grupo no contiene atributos.
                </p>
            @endforelse
        </div>
    </section>
</x-app-layout>