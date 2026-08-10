<x-app-layout>
    <x-slot name="header">
        Explorar atributo
    </x-slot>

    <div class="mb-5">
        <a href="{{ route('community.index', ['tab' => 'attributes']) }}" class="text-sm font-bold text-indigo-600">
            ← Volver al explorador
        </a>
    </div>

    <article class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
        <div class="relative min-h-64"
            style="
                background:
                    linear-gradient(
                        135deg,
                        {{ $attribute->color ?? '#6366F1' }},
                        #7C3AED
                    );
            ">
            @if ($attribute->image_url)
                <img src="{{ $attribute->image_url }}" alt="{{ $attribute->name }}"
                    class="absolute inset-0 h-full w-full object-cover">

                <div class="absolute inset-0 bg-gradient-to-t from-slate-950/85 to-transparent"></div>
            @else
                <div class="flex min-h-64 items-center justify-center text-8xl text-white/80">
                    {{ $attribute->icon ?: '☷' }}
                </div>
            @endif

            <div class="absolute bottom-0 left-0 right-0 p-7 text-white">
                <span class="rounded-full bg-white/20 px-3 py-1 text-xs font-bold backdrop-blur">
                    {{ $attribute->data_type_label }}
                </span>

                <h2 class="mt-4 text-4xl font-black">
                    {{ $attribute->name }}
                </h2>

                <div
                    class="
                    mt-3
                    flex
                    items-center
                    gap-3
                ">

                    <x-user-avatar :user="$attribute->creator" size="sm" />


                    <div>

                        <p
                            class="
                            text-xs
                            text-white/60
                        ">
                            Creado por
                        </p>

                        <a href="{{ route('community.creators.show', $attribute->creator->username) }}"
                            class="
                                text-sm
                                font-bold
                                text-white
                                hover:underline
                            ">
                            {{ $attribute->creator->name }}
                            ·
                            {{ '@' . $attribute->creator->username }}
                        </a>

                    </div>

                </div>
            </div>
        </div>

        <div class="p-6 sm:p-9">
            <div class="grid gap-8 xl:grid-cols-[1fr_300px]">
                <div>
                    <p class="leading-8 text-slate-600">
                        {{ $attribute->description ?: 'Este atributo no tiene descripción.' }}
                    </p>

                    <div class="mt-7 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        <div class="rounded-2xl bg-slate-50 p-5">
                            <p class="text-xs font-bold uppercase tracking-wider text-slate-400">
                                Origen
                            </p>

                            <p class="mt-2 font-black">
                                {{ $attribute->usesCatalog() ? 'Catálogo' : 'Valor libre' }}
                            </p>
                        </div>

                        <div class="rounded-2xl bg-slate-50 p-5">
                            <p class="text-xs font-bold uppercase tracking-wider text-slate-400">
                                Presentación
                            </p>

                            <p class="mt-2 font-black">
                                {{ $attribute->display_style_label }}
                            </p>
                        </div>

                        <div class="rounded-2xl bg-slate-50 p-5">
                            <p class="text-xs font-bold uppercase tracking-wider text-slate-400">
                                Opciones
                            </p>

                            <p class="mt-2 text-2xl font-black">
                                {{ $attribute->options->count() }}
                            </p>
                        </div>

                        <div class="rounded-2xl bg-slate-50 p-5">
                            <p class="text-xs font-bold uppercase tracking-wider text-slate-400">
                                Usos
                            </p>

                            <p class="mt-2 text-2xl font-black">
                                {{ $attribute->entity_attributes_count }}
                            </p>
                        </div>
                    </div>

                    @if ($attribute->options->isNotEmpty())
                        <section class="mt-9">
                            <h3 class="text-xl font-black text-slate-900">
                                Catálogo de opciones
                            </h3>

                            <div class="mt-5 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                                @foreach ($attribute->options as $option)
                                    <article class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
                                        <div class="aspect-[16/9] bg-slate-100"
                                            style="
                                                background-color:
                                                {{ $option->color ?? '#6366F1' }}25;
                                            ">
                                            @if ($option->image_url)
                                                <img src="{{ $option->image_url }}" alt="{{ $option->name }}"
                                                    class="h-full w-full object-cover">
                                            @else
                                                <div class="flex h-full items-center justify-center text-5xl">
                                                    {{ $option->icon ?: '◆' }}
                                                </div>
                                            @endif
                                        </div>

                                        <div class="p-4">
                                            <p class="font-black text-slate-900">
                                                {{ $option->name }}
                                            </p>

                                            <p class="mt-1 line-clamp-2 text-sm text-slate-500">
                                                {{ $option->description }}
                                            </p>
                                        </div>
                                    </article>
                                @endforeach
                            </div>
                        </section>
                    @endif
                </div>

                <aside>
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-6">
                        <h3 class="font-black text-slate-900">
                            Copiar atributo
                        </h3>

                        <p class="mt-2 text-sm leading-6 text-slate-500">
                            Se copiarán su configuración, imagen,
                            opciones y jerarquía de catálogo.
                        </p>

                        @if ($attribute->allow_cloning && $attribute->user_id !== auth()->id())
                            <form method="POST" action="{{ route('community.attributes.clone', $attribute) }}"
                                class="mt-5">
                                @csrf

                                <button type="submit"
                                    class="w-full rounded-xl bg-indigo-600 px-5 py-3 font-black text-white">
                                    ⧉ Copiar a mis atributos
                                </button>
                            </form>
                        @elseif ($attribute->user_id === auth()->id())
                            <a href="{{ route('attributes.show', $attribute) }}"
                                class="mt-5 block rounded-xl bg-slate-900 px-5 py-3 text-center font-black text-white">
                                Administrar mi atributo
                            </a>
                        @else
                            <p class="mt-5 rounded-xl bg-amber-50 p-4 text-sm text-amber-800">
                                El creador no permite copiarlo.
                            </p>
                        @endif
                    </div>
                </aside>
            </div>
        </div>
    </article>
</x-app-layout>
