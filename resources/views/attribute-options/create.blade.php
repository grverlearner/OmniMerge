<x-app-layout>

    <x-slot name="header">
        Catálogos
    </x-slot>


    <div x-data="{
        catalogSearch: ''
    }">

        {{-- ===================================================== --}}
        {{-- CABECERA --}}
        {{-- ===================================================== --}}

        <div
            class="
                mb-7
                flex
                flex-col
                justify-between
                gap-4
                sm:flex-row
                sm:items-start
            ">

            <div>

                <a href="{{ route('attribute-options.index') }}"
                    class="
                        text-sm
                        font-bold
                        text-slate-400
                        hover:text-violet-600
                    ">
                    ← Catálogos
                </a>


                <p
                    class="
                        mt-4
                        text-xs
                        font-black
                        uppercase
                        tracking-[0.16em]
                        text-violet-600
                    ">
                    Biblioteca · Catálogos
                </p>


                <h2
                    class="
                        mt-2
                        text-3xl
                        font-black
                        tracking-tight
                        text-slate-900
                    ">
                    Nuevo elemento
                </h2>


                <p
                    class="
                        mt-2
                        max-w-2xl
                        text-slate-500
                    ">
                    Crea una pieza reutilizable dentro de uno
                    de tus Catálogos.
                </p>

            </div>


            <div
                class="
                    rounded-2xl
                    border
                    border-violet-100
                    bg-violet-50
                    px-5
                    py-3
                ">

                <p
                    class="
                        text-[10px]
                        font-black
                        uppercase
                        tracking-wider
                        text-violet-400
                    ">
                    Próximo código
                </p>


                <p
                    class="
                        mt-1
                        font-mono
                        text-lg
                        font-black
                        text-violet-700
                    ">
                    {{ $previewCode }}
                </p>

            </div>

        </div>


        {{-- ===================================================== --}}
        {{-- SIN CATÁLOGOS --}}
        {{-- ===================================================== --}}

        @if ($attributes->isEmpty())

            <div
                class="
                    rounded-3xl
                    border
                    border-amber-200
                    bg-amber-50
                    p-8
                ">

                <h3
                    class="
                        text-lg
                        font-black
                        text-amber-900
                    ">
                    No tienes Catálogos disponibles
                </h3>


                <p
                    class="
                        mt-2
                        max-w-xl
                        text-sm
                        leading-6
                        text-amber-700
                    ">
                    Primero crea un atributo de tipo Catálogo.
                    Después podrás añadir sus elementos.
                </p>


                <a href="{{ route('attributes.create') }}"
                    class="
                        mt-5
                        inline-flex
                        rounded-xl
                        bg-amber-600
                        px-5
                        py-3
                        text-sm
                        font-black
                        text-white
                    ">
                    Crear atributo
                </a>

            </div>
        @elseif (!$selectedAttribute)
            {{-- ================================================= --}}
            {{-- SELECCIONAR CATÁLOGO --}}
            {{-- ================================================= --}}

            <section>

                <div
                    class="
                        rounded-3xl
                        border
                        border-slate-200
                        bg-white
                        p-6
                        shadow-sm
                        sm:p-8
                    ">

                    <p
                        class="
                            text-xs
                            font-black
                            uppercase
                            tracking-[0.16em]
                            text-violet-600
                        ">
                        Paso 1
                    </p>


                    <h3
                        class="
                            mt-2
                            text-2xl
                            font-black
                            text-slate-900
                        ">
                        Selecciona el Catálogo
                    </h3>


                    <p
                        class="
                            mt-2
                            max-w-2xl
                            text-sm
                            leading-6
                            text-slate-500
                        ">
                        El elemento tendrá identidad propia,
                        pero necesita un Catálogo que defina
                        su significado.
                    </p>


                    {{-- BUSCADOR --}}
                    <div class="mt-6">

                        <input type="text" x-model="catalogSearch" placeholder="Buscar Anime, Elemento, País..."
                            class="
                                w-full
                                rounded-xl
                                border-slate-300
                                bg-white
                                text-slate-900
                                placeholder:text-slate-400
                                focus:border-violet-500
                                focus:ring-violet-500
                            ">

                    </div>


                    {{-- CARDS --}}
                    <div
                        class="
                            mt-6
                            grid
                            gap-4
                            sm:grid-cols-2
                            lg:grid-cols-3
                        ">

                        @foreach ($attributes as $attribute)
                            <a href="{{ route('attribute-options.create', [
                                'attribute' => $attribute->id,
                            ]) }}"
                                x-show="
                                    ! catalogSearch
                                    ||
                                    @js(mb_strtolower($attribute->name . ' ' . $attribute->code)).includes(
                                        catalogSearch.toLowerCase()
                                    )
                                "
                                class="
                                    group
                                    overflow-hidden
                                    rounded-2xl
                                    border
                                    border-slate-200
                                    bg-white
                                    transition
                                    hover:-translate-y-0.5
                                    hover:border-violet-300
                                    hover:shadow-lg
                                ">

                                <div
                                    class="
                                        aspect-[16/7]
                                        bg-slate-100
                                    ">

                                    @if ($attribute->image_url)
                                        <img src="{{ $attribute->image_url }}" alt="{{ $attribute->name }}"
                                            class="
                                                h-full
                                                w-full
                                                object-cover
                                            ">
                                    @else
                                        <div class="
                                                flex
                                                h-full
                                                items-center
                                                justify-center
                                                text-4xl
                                                font-black
                                            "
                                            style="
                                                background-color:
                                                    {{ $attribute->color ?? '#6366F1' }}20;

                                                color:
                                                    {{ $attribute->color ?? '#6366F1' }};
                                            ">
                                            {{ $attribute->icon ?: '◆' }}
                                        </div>
                                    @endif

                                </div>


                                <div class="p-5">

                                    <p
                                        class="
                                            font-mono
                                            text-[10px]
                                            font-black
                                            text-slate-400
                                        ">
                                        {{ $attribute->code }}
                                    </p>


                                    <h4
                                        class="
                                            mt-1
                                            font-black
                                            text-slate-900
                                            group-hover:text-violet-700
                                        ">
                                        {{ $attribute->name }}
                                    </h4>


                                    <div
                                        class="
                                            mt-3
                                            flex
                                            items-center
                                            justify-between
                                        ">

                                        <span
                                            class="
                                                text-xs
                                                text-slate-500
                                            ">
                                            {{ $attribute->options_count }}
                                            elementos
                                        </span>


                                        <span
                                            class="
                                                text-xs
                                                font-black
                                                text-violet-600
                                            ">
                                            Seleccionar →
                                        </span>

                                    </div>

                                </div>

                            </a>
                        @endforeach

                    </div>

                </div>

            </section>
        @else
            {{-- ================================================= --}}
            {{-- CATÁLOGO SELECCIONADO --}}
            {{-- ================================================= --}}

            <div
                class="
                    mb-6
                    flex
                    flex-col
                    gap-4
                    rounded-2xl
                    border
                    border-violet-100
                    bg-violet-50
                    p-5
                    sm:flex-row
                    sm:items-center
                ">

                <div
                    class="
                        h-16
                        w-16
                        shrink-0
                        overflow-hidden
                        rounded-xl
                        bg-white
                    ">

                    @if ($selectedAttribute->image_url)
                        <img src="{{ $selectedAttribute->image_url }}"
                            class="
                                h-full
                                w-full
                                object-cover
                            ">
                    @else
                        <div
                            class="
                                flex
                                h-full
                                items-center
                                justify-center
                                text-2xl
                                font-black
                            ">
                            {{ $selectedAttribute->icon ?: '◆' }}
                        </div>
                    @endif

                </div>


                <div class="flex-1">

                    <p
                        class="
                            text-[10px]
                            font-black
                            uppercase
                            tracking-wider
                            text-violet-500
                        ">
                        Catálogo seleccionado
                    </p>


                    <h3
                        class="
                            mt-1
                            text-lg
                            font-black
                            text-violet-950
                        ">
                        {{ $selectedAttribute->name }}
                    </h3>


                    <p
                        class="
                            mt-1
                            font-mono
                            text-xs
                            font-bold
                            text-violet-500
                        ">
                        {{ $selectedAttribute->code }}
                        ·
                        {{ $selectedAttribute->options_count }}
                        elementos
                    </p>

                </div>


                <a href="{{ route('attribute-options.create') }}"
                    class="
                        rounded-xl
                        border
                        border-violet-200
                        bg-white
                        px-4
                        py-2.5
                        text-xs
                        font-black
                        text-violet-700
                    ">
                    Cambiar Catálogo
                </a>

            </div>


            {{-- FORM --}}
            <div
                class="
                    rounded-3xl
                    border
                    border-slate-200
                    bg-white
                    p-6
                    shadow-sm
                    sm:p-8
                ">

                <form method="POST"
                    action="{{ route('attributes.options.store', $selectedAttribute) }}"
                    enctype="multipart/form-data">

                    @csrf


                    @include('attribute-options.partials.form')

                </form>

            </div>

        @endif

    </div>

</x-app-layout>
