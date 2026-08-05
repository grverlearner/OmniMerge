<x-app-layout>
    <x-slot name="header">
        Detalle de opción
    </x-slot>

    <article class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
        <div class="relative min-h-64 bg-gradient-to-br from-indigo-100 to-violet-100 sm:min-h-80"
            style="
                background:
                    linear-gradient(
                        135deg,
                        {{ $attributeOption->color ?? '#6366F1' }}40,
                        #F8FAFC
                    );
            ">
            @if ($attributeOption->image_url)
                <img src="{{ $attributeOption->image_url }}" alt="{{ $attributeOption->name }}"
                    class="absolute inset-0 h-full w-full object-cover">

                <div class="absolute inset-0 bg-gradient-to-t from-slate-950/80 via-slate-950/20 to-transparent"></div>
            @else
                <div class="flex min-h-64 items-center justify-center text-8xl sm:min-h-80">
                    {{ $attributeOption->icon ?: '◆' }}
                </div>

                <div class="absolute inset-0 bg-gradient-to-t from-slate-950/60 via-transparent to-transparent"></div>
            @endif

            <div class="absolute bottom-0 left-0 right-0 p-6 text-white sm:p-8">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="rounded-full bg-white/20 px-3 py-1 text-xs font-bold backdrop-blur">
                        {{ $attributeOption->attribute->name }}
                    </span>

                    <x-status-badge :status="$attributeOption->status" />
                </div>

                <h2 class="mt-4 text-3xl font-black sm:text-4xl">
                    {{ $attributeOption->name }}
                </h2>

                <p class="mt-1 text-sm font-semibold text-white/70">
                    {{ $attributeOption->code }}
                </p>
            </div>
        </div>

        <div class="p-6 sm:p-8">
            <div class="flex flex-col justify-between gap-6 sm:flex-row sm:items-start">
                <div class="max-w-3xl">
                    <h3 class="text-sm font-semibold uppercase tracking-wider text-slate-400">
                        Descripción
                    </h3>

                    <p class="mt-3 whitespace-pre-line leading-8 text-slate-600">
                        {{ $attributeOption->description ?: 'Esta opción todavía no tiene una descripción.' }}
                    </p>
                </div>

                <div class="flex shrink-0 flex-wrap gap-2">
                    <a href="{{ route('attribute-options.edit', $attributeOption) }}"
                        class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                        Editar
                    </a>

                    <form method="POST"
                        action="{{ route('attribute-options.destroy', $attributeOption) }}"
                        onsubmit="return confirm(
                            '¿Eliminar esta opción?'
                        )">
                        @csrf
                        @method('DELETE')

                        <button type="submit"
                            class="rounded-xl border border-red-200 px-4 py-2.5 text-sm font-semibold text-red-600 hover:bg-red-50">
                            Eliminar
                        </button>
                    </form>
                </div>
            </div>

            <div class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div class="rounded-2xl bg-slate-50 p-5">
                    <p class="text-sm text-slate-500">
                        Atributo
                    </p>

                    <a href="{{ route('attributes.show', $attributeOption->attribute) }}"
                        class="mt-2 block font-black text-indigo-600 hover:text-indigo-800">
                        {{ $attributeOption->attribute->name }}
                    </a>
                </div>

                <div class="rounded-2xl bg-slate-50 p-5">
                    <p class="text-sm text-slate-500">
                        Opción superior
                    </p>

                    <p class="mt-2 font-black text-slate-900">
                        {{ $attributeOption->parent?->name ?? 'Ninguna' }}
                    </p>
                </div>

                <div class="rounded-2xl bg-slate-50 p-5">
                    <p class="text-sm text-slate-500">
                        Subopciones
                    </p>

                    <p class="mt-2 text-2xl font-black text-slate-900">
                        {{ $attributeOption->children->count() }}
                    </p>
                </div>

                <div class="rounded-2xl bg-slate-50 p-5">
                    <p class="text-sm text-slate-500">
                        Usado en entidades
                    </p>

                    <p class="mt-2 text-2xl font-black text-slate-900">
                        {{ $attributeOption->values_count }}
                    </p>
                </div>
            </div>

            <div class="mt-8 grid gap-4 sm:grid-cols-3">
                <div class="rounded-2xl border border-slate-200 p-5">
                    <p class="text-sm text-slate-500">
                        Icono
                    </p>

                    <p class="mt-3 text-4xl">
                        {{ $attributeOption->icon ?: 'Sin icono' }}
                    </p>
                </div>

                <div class="rounded-2xl border border-slate-200 p-5">
                    <p class="text-sm text-slate-500">
                        Color
                    </p>

                    <div class="mt-3 flex items-center gap-3">
                        <span class="h-10 w-10 rounded-xl border border-slate-200"
                            style="
                                background-color:
                                {{ $attributeOption->color ?? '#6366F1' }};
                            "></span>

                        <span class="font-mono text-sm font-bold text-slate-700">
                            {{ $attributeOption->color ?? '#6366F1' }}
                        </span>
                    </div>
                </div>

                <div class="rounded-2xl border border-slate-200 p-5">
                    <p class="text-sm text-slate-500">
                        Valor numérico
                    </p>

                    <p class="mt-3 text-2xl font-black text-slate-900">
                        {{ $attributeOption->numeric_value ?? 'No definido' }}
                    </p>
                </div>
            </div>
        </div>
    </article>

    <section class="mt-8">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-xl font-black text-slate-900">
                    Subopciones
                </h3>

                <p class="mt-1 text-sm text-slate-500">
                    Opciones que dependen directamente de
                    {{ $attributeOption->name }}.
                </p>
            </div>

            <a
                href="{{ route('attribute-options.create', [
                    'attribute' => $attributeOption->attribute_id,
                
                    'parent' => $attributeOption->id,
                ]) }}">
                + Nueva subopción
            </a>
        </div>

        <div class="mt-5 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
            @forelse ($attributeOption->children as $child)
                <a href="{{ route('attribute-options.show', $child) }}"
                    class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm transition hover:-translate-y-1 hover:shadow-lg">
                    <div class="aspect-[16/8] bg-slate-100"
                        style="
                            background-color:
                            {{ $child->color ?? '#6366F1' }}20;
                        ">
                        @if ($child->image_url)
                            <img src="{{ $child->image_url }}" alt="{{ $child->name }}"
                                class="h-full w-full object-cover">
                        @else
                            <div class="flex h-full items-center justify-center text-4xl">
                                {{ $child->icon ?: '◆' }}
                            </div>
                        @endif
                    </div>

                    <div class="p-5">
                        <p class="font-black text-slate-900">
                            {{ $child->name }}
                        </p>

                        <p class="mt-1 text-xs font-semibold text-slate-400">
                            {{ $child->code }}
                        </p>
                    </div>
                </a>
            @empty
                <div
                    class="sm:col-span-2 lg:col-span-3 rounded-2xl border border-dashed border-slate-300 bg-white py-12 text-center text-sm text-slate-500">
                    Esta opción no tiene subopciones.
                </div>
            @endforelse
        </div>
    </section>
</x-app-layout>
