<x-app-layout>
    <x-slot name="header">
        Grupos de atributos
    </x-slot>

    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
        <div>
            <h2 class="text-2xl font-black text-slate-900">
                Grupos de atributos
            </h2>

            <p class="mt-2 text-slate-500">
                Organiza visualmente las características de tus entidades.
            </p>
        </div>

        <a
            href="{{ route('attribute-groups.create') }}"
            class="rounded-xl bg-indigo-600 px-5 py-3 text-sm font-bold text-white shadow-lg shadow-indigo-600/20 hover:bg-indigo-700"
        >
            + Nuevo grupo
        </a>
    </div>

    <form
        method="GET"
        action="{{ route('attribute-groups.index') }}"
        class="mt-6 flex gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm"
    >
        <input
            name="search"
            value="{{ $search }}"
            placeholder="Buscar grupos..."
            class="min-w-0 flex-1 rounded-xl border-slate-300 focus:border-indigo-500 focus:ring-indigo-500"
        >

        <button
            type="submit"
            class="rounded-xl bg-slate-900 px-5 py-3 text-sm font-semibold text-white"
        >
            Buscar
        </button>
    </form>

    <div class="mt-6 grid gap-5 sm:grid-cols-2 xl:grid-cols-3">
        @forelse ($groups as $group)
            <article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm transition hover:-translate-y-1 hover:shadow-lg">
                <div class="flex items-start justify-between">
                    <div
                        class="flex h-12 w-12 items-center justify-center rounded-2xl text-xl"
                        style="
                            background-color: {{ $group->color ?? '#6366F1' }}20;
                            color: {{ $group->color ?? '#6366F1' }};
                        "
                    >
                        {{ $group->icon ?: '▥' }}
                    </div>

                    <x-status-badge :status="$group->status" />
                </div>

                <h3 class="mt-5 text-xl font-black text-slate-900">
                    {{ $group->name }}
                </h3>

                <p class="mt-1 text-xs font-semibold text-slate-400">
                    {{ $group->code }}
                </p>

                <p class="mt-4 line-clamp-3 text-sm leading-6 text-slate-500">
                    {{ $group->description ?: 'Sin descripción.' }}
                </p>

                <div class="mt-5 flex items-center justify-between rounded-xl bg-slate-50 p-4">
                    <div>
                        <p class="text-xs text-slate-500">
                            Atributos
                        </p>

                        <p class="mt-1 text-xl font-black text-slate-900">
                            {{ $group->attributes_count }}
                        </p>
                    </div>

                    <span class="rounded-full bg-indigo-100 px-3 py-1 text-xs font-semibold text-indigo-700">
                        {{ $group->layout_type }}
                    </span>
                </div>

                <div class="mt-5 text-right">
                    <a
                        href="{{ route('attribute-groups.show', $group) }}"
                        class="text-sm font-bold text-indigo-600 hover:text-indigo-800"
                    >
                        Abrir →
                    </a>
                </div>
            </article>
        @empty
            <div class="sm:col-span-2 xl:col-span-3 rounded-2xl border border-dashed border-slate-300 bg-white py-20 text-center">
                <p class="font-bold text-slate-700">
                    Todavía no existen grupos
                </p>

                <p class="mt-2 text-sm text-slate-500">
                    Crea grupos como Información general, Combate o Personalidad.
                </p>

                <a
                    href="{{ route('attribute-groups.create') }}"
                    class="mt-5 inline-block rounded-xl bg-indigo-600 px-5 py-3 text-sm font-bold text-white"
                >
                    Crear primer grupo
                </a>
            </div>
        @endforelse
    </div>

    <div class="mt-8">
        {{ $groups->links() }}
    </div>
</x-app-layout>