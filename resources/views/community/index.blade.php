<x-app-layout>
    <x-slot name="header">
        Explorar comunidad
    </x-slot>

    <section class="relative overflow-hidden rounded-3xl bg-slate-950 p-7 text-white shadow-xl sm:p-10">
        <div class="absolute -right-24 -top-24 h-72 w-72 rounded-full bg-indigo-500/30 blur-3xl"></div>
        <div class="absolute -bottom-24 left-1/3 h-64 w-64 rounded-full bg-fuchsia-500/20 blur-3xl"></div>

        <div class="relative max-w-3xl">
            <span
                class="inline-flex rounded-full border border-white/15 bg-white/10 px-3 py-1 text-xs font-bold uppercase tracking-widest text-indigo-200 backdrop-blur">
                Biblioteca pública
            </span>

            <h2 class="mt-5 text-3xl font-black sm:text-5xl">
                Descubre lo que la comunidad está creando
            </h2>

            <p class="mt-4 max-w-2xl text-sm leading-7 text-slate-300 sm:text-base">
                Explora entidades, universos conceptuales, colecciones
                y catálogos creados por otros usuarios. Guarda una copia
                independiente en tu biblioteca y adáptala a tus proyectos.
            </p>

            <form method="GET" action="{{ route('community.index') }}"
                class="mt-7 flex max-w-2xl flex-col gap-3 rounded-2xl border border-white/10 bg-white/10 p-3 backdrop-blur sm:flex-row">
                <input type="hidden" name="tab" value="{{ $tab }}">

                <input type="search" name="search" value="{{ $search }}"
                    placeholder="Buscar Naruto, dragones, países, elementos..."
                    class="min-w-0 flex-1 rounded-xl border-0 bg-white px-4 py-3 text-slate-900 placeholder:text-slate-400 focus:ring-2 focus:ring-indigo-400">

                <button type="submit"
                    class="rounded-xl bg-indigo-500 px-6 py-3 text-sm font-bold text-white transition hover:bg-indigo-400">
                    Explorar
                </button>
            </form>
        </div>
    </section>

    <section class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ([
        [
            'label' => 'Entidades públicas',
            'value' => $statistics['entities'],
            'icon' => '✦',
        ],
        [
            'label' => 'Colecciones',
            'value' => $statistics['collections'],
            'icon' => '▤',
        ],
        [
            'label' => 'Atributos',
            'value' => $statistics['attributes'],
            'icon' => '☷',
        ],
        [
            'label' => 'Creadores',
            'value' => $statistics['creators'],
            'icon' => '◎',
        ],
    ] as $stat)
            <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-slate-500">
                            {{ $stat['label'] }}
                        </p>

                        <p class="mt-2 text-3xl font-black text-slate-900">
                            {{ number_format($stat['value']) }}
                        </p>
                    </div>

                    <div
                        class="flex h-12 w-12 items-center justify-center rounded-2xl bg-indigo-50 text-xl text-indigo-600">
                        {{ $stat['icon'] }}
                    </div>
                </div>
            </article>
        @endforeach
    </section>

    <section class="mt-8">
        <div class="overflow-x-auto">
            <nav class="flex min-w-max gap-2 rounded-2xl border border-slate-200 bg-white p-2 shadow-sm">
                @foreach ([
        'entities' => [
            'label' => 'Entidades',
            'icon' => '✦',
        ],
        'collections' => [
            'label' => 'Colecciones',
            'icon' => '▤',
        ],
        'attributes' => [
            'label' => 'Atributos y catálogos',
            'icon' => '☷',
        ],
    ] as $value => $item)
                    <a href="{{ route('community.index', [
                        'tab' => $value,
                        'search' => $search,
                    ]) }}"
                        class="{{ $tab === $value
                            ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/20'
                            : 'text-slate-600 hover:bg-slate-100' }}
                        flex items-center gap-2 rounded-xl px-5 py-3 text-sm font-bold transition">
                        <span>{{ $item['icon'] }}</span>
                        {{ $item['label'] }}
                    </a>
                @endforeach
            </nav>
        </div>

        @include('community.partials.filters')

        @if ($tab === 'entities')
            <div class="mt-6 grid gap-6 sm:grid-cols-2 xl:grid-cols-3">
                @forelse ($entities as $entity)
                    @include('community.partials.entity-card', ['entity' => $entity])
                @empty
                    <div
                        class="sm:col-span-2 xl:col-span-3 rounded-3xl border border-dashed border-slate-300 bg-white py-20 text-center">
                        <p class="font-bold text-slate-700">
                            No encontramos entidades públicas
                        </p>

                        <p class="mt-2 text-sm text-slate-500">
                            Prueba otro término o elimina algunos filtros.
                        </p>
                    </div>
                @endforelse
            </div>

            <div class="mt-8">
                {{ $entities->links() }}
            </div>
        @endif

        @if ($tab === 'collections')
            <div class="mt-6 grid gap-6 sm:grid-cols-2 xl:grid-cols-3">
                @forelse ($collections as $collection)
                    @include('community.partials.collection-card', ['collection' => $collection])
                @empty
                    <div
                        class="sm:col-span-2 xl:col-span-3 rounded-3xl border border-dashed border-slate-300 bg-white py-20 text-center">
                        <p class="font-bold text-slate-700">
                            No encontramos colecciones públicas
                        </p>
                    </div>
                @endforelse
            </div>

            <div class="mt-8">
                {{ $collections->links() }}
            </div>
        @endif

        @if ($tab === 'attributes')
            <div class="mt-6 grid gap-6 sm:grid-cols-2 xl:grid-cols-3">
                @forelse ($attributes as $attribute)
                    @include('community.partials.attribute-card', ['attribute' => $attribute])
                @empty
                    <div
                        class="sm:col-span-2 xl:col-span-3 rounded-3xl border border-dashed border-slate-300 bg-white py-20 text-center">
                        <p class="font-bold text-slate-700">
                            No encontramos atributos públicos
                        </p>
                    </div>
                @endforelse
            </div>

            <div class="mt-8">
                {{ $attributes->links() }}
            </div>
        @endif
    </section>
</x-app-layout>
