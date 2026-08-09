<x-app-layout>

    <x-slot name="header">
        Catálogo público
    </x-slot>


    <div class="mb-5">

        <a href="{{ route('community.index', ['tab' => 'catalogs']) }}" class="text-sm font-bold text-violet-600">
            ← Volver a Catálogos
        </a>

    </div>


    <article class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">

        <div class="relative min-h-72"
            style="
                background:
                    linear-gradient(
                        135deg,
                        {{ $option->color ?? '#7C3AED' }},
                        #312E81
                    );
            ">

            @if ($option->image_url)
                <img src="{{ $option->image_url }}" alt="{{ $option->name }}"
                    class="absolute inset-0 h-full w-full object-cover">

                <div class="absolute inset-0 bg-gradient-to-t from-slate-950/90 via-slate-950/20 to-transparent"></div>
            @else
                <div class="flex min-h-72 items-center justify-center text-8xl text-white/80">
                    {{ $option->icon ?: '◆' }}
                </div>
            @endif


            <div class="absolute bottom-0 left-0 right-0 p-7 text-white">

                <div class="flex flex-wrap gap-2">

                    <span class="rounded-full bg-white/20 px-3 py-1 text-xs font-black backdrop-blur">
                        {{ $option->attribute->name }}
                    </span>

                    <span class="rounded-full bg-white/20 px-3 py-1 font-mono text-xs font-black backdrop-blur">
                        {{ $option->code }}
                    </span>

                </div>


                <h1 class="mt-4 text-4xl font-black sm:text-5xl">
                    {{ $option->name }}
                </h1>


                <p class="mt-2 text-sm text-white/70">
                    por
                    <a href="{{ route('community.creators.show', $option->user->username) }}"
                        class="font-bold text-white hover:underline">
                        {{ $option->user->name }}
                        ·
                        {{ '@' . $option->user->username }}
                    </a>
                </p>

            </div>

        </div>


        <div class="p-6 sm:p-9">

            <div class="grid gap-8 xl:grid-cols-[minmax(0,1fr)_320px]">

                <div>

                    <p class="text-xs font-black uppercase tracking-wider text-slate-400">
                        Descripción
                    </p>


                    <p class="mt-3 whitespace-pre-line leading-8 text-slate-600">
                        {{ $option->description ?: 'Este elemento no tiene descripción.' }}
                    </p>


                    <div class="mt-8 grid gap-3 sm:grid-cols-3">

                        <article class="rounded-2xl bg-slate-50 p-5">

                            <p class="text-[10px] font-black uppercase text-slate-400">
                                Usos
                            </p>

                            <p class="mt-2 text-2xl font-black text-slate-900">
                                {{ $option->values_count }}
                            </p>

                        </article>


                        <article class="rounded-2xl bg-slate-50 p-5">

                            <p class="text-[10px] font-black uppercase text-slate-400">
                                Subelementos
                            </p>

                            <p class="mt-2 text-2xl font-black text-slate-900">
                                {{ $option->children_count }}
                            </p>

                        </article>


                        <article class="rounded-2xl bg-slate-50 p-5">

                            <p class="text-[10px] font-black uppercase text-slate-400">
                                Nivel
                            </p>

                            <p class="mt-2 font-black text-slate-900">
                                {{ $option->hierarchy_label }}
                            </p>

                        </article>

                    </div>


                    @if ($option->parent)

                        <section class="mt-8">

                            <p class="text-xs font-black uppercase tracking-wider text-slate-400">
                                Elemento superior
                            </p>


                            <a href="{{ route('community.catalogs.show', $option->parent) }}"
                                class="mt-3 inline-flex items-center gap-3 rounded-2xl border border-slate-200 p-4 hover:border-violet-200">

                                <div class="h-12 w-12 overflow-hidden rounded-xl bg-slate-100">

                                    @if ($option->parent->image_url)
                                        <img src="{{ $option->parent->image_url }}" class="h-full w-full object-cover">
                                    @else
                                        <div class="flex h-full items-center justify-center">
                                            {{ $option->parent->icon ?: '◆' }}
                                        </div>
                                    @endif

                                </div>


                                <span class="font-black text-slate-800">
                                    {{ $option->parent->name }}
                                </span>

                            </a>

                        </section>

                    @endif


                    @if ($option->children->isNotEmpty())

                        <section class="mt-8">

                            <p class="text-xs font-black uppercase tracking-wider text-slate-400">
                                Subelementos
                            </p>


                            <div class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-4">

                                @foreach ($option->children as $child)
                                    <a href="{{ route('community.catalogs.show', $child) }}" class="group min-w-0">

                                        <div class="aspect-square overflow-hidden rounded-xl bg-slate-100">

                                            @if ($child->image_url)
                                                <img src="{{ $child->image_url }}"
                                                    class="h-full w-full object-cover transition group-hover:scale-105">
                                            @else
                                                <div
                                                    class="flex h-full items-center justify-center text-3xl text-violet-300">
                                                    {{ $child->icon ?: '◆' }}
                                                </div>
                                            @endif

                                        </div>


                                        <p class="mt-2 truncate text-center text-xs font-black text-slate-700">
                                            {{ $child->name }}
                                        </p>

                                    </a>
                                @endforeach

                            </div>

                        </section>

                    @endif

                </div>


                <aside class="space-y-4">

                    <article class="rounded-2xl border border-slate-200 bg-slate-50 p-5">

                        <p class="text-xs font-black uppercase text-slate-400">
                            Pertenece a
                        </p>


                        <a href="{{ route('community.attributes.show', $option->attribute) }}"
                            class="mt-3 block rounded-xl bg-white p-4 shadow-sm">

                            <p class="font-black text-slate-900">
                                {{ $option->attribute->name }}
                            </p>


                            <p class="mt-1 text-xs text-slate-400">
                                {{ $option->attribute->data_type_label }}
                            </p>

                        </a>

                    </article>


                    @if ($option->user_id === auth()->id())

                        <a href="{{ route('attribute-options.show', $option) }}"
                            class="block rounded-2xl bg-slate-900 px-5 py-4 text-center font-black text-white">
                            Administrar mi elemento
                        </a>
                    @else
                        @php

                            $myClone = $option
                                ->clones()
                                ->where('user_id', auth()->id())
                                ->first();

                        @endphp


                        @if ($myClone)
                            <a href="{{ route('attribute-options.show', $myClone) }}"
                                class="block rounded-2xl bg-emerald-600 px-5 py-4 text-center font-black text-white">
                                ✓ Abrir mi copia
                            </a>
                        @elseif ($option->attribute->allow_cloning)
                            <form method="POST" action="{{ route('community.catalogs.clone', $option) }}">

                                @csrf

                                <button type="submit"
                                    class="w-full rounded-2xl bg-violet-600 px-5 py-4 font-black text-white shadow-lg shadow-violet-600/20">
                                    ⧉ Copiar elemento
                                </button>

                            </form>
                        @else
                            <div class="rounded-2xl bg-amber-50 p-5 text-sm text-amber-700">
                                El creador no permite copiar este Catálogo.
                            </div>
                        @endif

                    @endif

                </aside>

            </div>

        </div>

    </article>


    @if ($relatedOptions->isNotEmpty())

        <section class="mt-10">

            <h2 class="text-2xl font-black text-slate-900">
                Más de {{ $option->attribute->name }}
            </h2>


            <div class="mt-5 grid grid-cols-2 gap-3 sm:grid-cols-4 lg:grid-cols-6 xl:grid-cols-8">

                @foreach ($relatedOptions as $related)
                    @include('community.partials.gallery-item', [
                        'item' => $related,
                        'itemType' => 'catalog',
                    ])
                @endforeach

            </div>

        </section>

    @endif

</x-app-layout>
