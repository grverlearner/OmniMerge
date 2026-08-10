<x-app-layout>

    <x-slot name="header">
        Probar Resolver
    </x-slot>


    @include('entities.partials.section-navigation')

    @include('versions.partials.workspace-navigation')


    <section
        class="
            rounded-3xl
            bg-gradient-to-br
            from-amber-950
            via-slate-950
            to-violet-950
            p-6
            text-white
            sm:p-8
        ">

        <p
            class="
                text-[10px]
                font-black
                uppercase
                tracking-widest
                text-amber-300
            ">
            Diagnóstico
        </p>


        <h1 class="
                mt-2
                text-3xl
                font-black
            ">
            Version Resolver
        </h1>


        <p
            class="
                mt-3
                max-w-3xl
                text-sm
                leading-6
                text-white/60
            ">
            Simula el contexto que posteriormente
            utilizarán los Torneos y comprueba qué
            representación escogería OmniMerge.
        </p>

    </section>


    <form method="GET"
        class="
            mt-6
            rounded-3xl
            border
            border-slate-200
            bg-white
            p-6
            shadow-sm
        ">

        <div class="
                grid
                gap-5
                lg:grid-cols-2
            ">

            <div>

                <label
                    class="
                        text-xs
                        font-black
                        text-slate-700
                    ">
                    Entidad
                </label>


                <select name="entity_id" required
                    class="
                        mt-2
                        w-full
                        rounded-xl
                        border-slate-300
                    ">

                    <option value="">
                        Seleccionar Entidad...
                    </option>


                    @foreach ($entities as $item)
                        <option value="{{ $item->id }}" @selected($entity?->id === $item->id)>
                            {{ $item->name }}
                        </option>
                    @endforeach

                </select>

            </div>


            <div>

                <p
                    class="
                        text-xs
                        font-black
                        text-slate-700
                    ">
                    Contexto de Catálogos
                </p>


                <div
                    class="
                        mt-2
                        max-h-72
                        space-y-3
                        overflow-y-auto
                        rounded-xl
                        border
                        border-slate-200
                        p-3
                    ">

                    @foreach ($catalogAttributes as $attribute)
                        <details
                            class="
                                rounded-xl
                                bg-slate-50
                                p-3
                            ">

                            <summary
                                class="
                                    cursor-pointer
                                    text-xs
                                    font-black
                                    text-slate-700
                                ">
                                {{ $attribute->name }}
                            </summary>


                            <div
                                class="
                                    mt-3
                                    flex
                                    flex-wrap
                                    gap-2
                                ">

                                @foreach ($attribute->options as $option)
                                    <label
                                        class="
                                            cursor-pointer
                                        ">

                                        <input type="checkbox" name="option_ids[]" value="{{ $option->id }}"
                                            @checked($optionIds->contains($option->id)) class="peer sr-only">


                                        <span
                                            class="
                                                inline-flex
                                                rounded-full
                                                border
                                                border-slate-200
                                                bg-white
                                                px-3
                                                py-1.5
                                                text-[9px]
                                                font-black
                                                text-slate-500
                                                peer-checked:border-amber-400
                                                peer-checked:bg-amber-50
                                                peer-checked:text-amber-700
                                            ">
                                            {{ $option->name }}
                                        </span>

                                    </label>
                                @endforeach

                            </div>

                        </details>
                    @endforeach

                </div>

            </div>

        </div>


        <button
            class="
                mt-5
                rounded-xl
                bg-amber-500
                px-5
                py-3
                text-sm
                font-black
                text-white
            ">
            ⚡ Resolver contexto
        </button>

    </form>


    @if ($entity)

        <section
            class="
                mt-6
                rounded-3xl
                border
                p-6

                {{ $result ? 'border-emerald-200 bg-emerald-50' : 'border-amber-200 bg-amber-50' }}
            ">

            <p
                class="
                    text-[10px]
                    font-black
                    uppercase
                    tracking-wider

                    {{ $result ? 'text-emerald-500' : 'text-amber-500' }}
                ">
                Resultado
            </p>


            @if ($result)
                <div
                    class="
                        mt-4
                        flex
                        flex-col
                        gap-4
                        sm:flex-row
                        sm:items-center
                    ">

                    <img src="{{ $result->image_url }}"
                        class="
                            h-28
                            w-28
                            rounded-2xl
                            object-cover
                        ">


                    <div>

                        <p
                            class="
                                text-2xl
                                font-black
                                text-slate-900
                            ">
                            {{ $result->name }}
                        </p>


                        <p
                            class="
                                mt-1
                                text-xs
                                text-emerald-700
                            ">
                            OmniMerge seleccionaría
                            <strong>
                                {{ $result->version->name }}
                            </strong>.
                        </p>


                        <a href="{{ route('entity-versions.show', [$entity, $result]) }}"
                            class="
                                mt-3
                                inline-flex
                                text-xs
                                font-black
                                text-emerald-700
                                underline
                            ">
                            Abrir resultado →
                        </a>

                    </div>

                </div>
            @else
                <p
                    class="
                        mt-3
                        text-lg
                        font-black
                        text-amber-800
                    ">
                    No existe una Version automática
                    compatible con este contexto.
                </p>
            @endif

        </section>


        @if ($candidateVersions->isNotEmpty())

            <section
                class="
                    mt-5
                    rounded-3xl
                    border
                    border-slate-200
                    bg-white
                    p-6
                ">

                <h2
                    class="
                        text-lg
                        font-black
                        text-slate-800
                    ">
                    Candidatas relacionadas
                </h2>


                <div
                    class="
                        mt-4
                        grid
                        gap-3
                        md:grid-cols-2
                    ">

                    @foreach ($candidateVersions as $candidate)
                        <div
                            class="
                                flex
                                items-center
                                gap-3
                                rounded-2xl
                                bg-slate-50
                                p-3
                            ">

                            <img src="{{ $candidate->image_url }}"
                                class="
                                    h-12
                                    w-12
                                    rounded-xl
                                    object-cover
                                ">


                            <div>

                                <p
                                    class="
                                        text-xs
                                        font-black
                                        text-slate-800
                                    ">
                                    {{ $candidate->name }}
                                </p>


                                <p
                                    class="
                                        mt-1
                                        text-[8px]
                                        text-slate-400
                                    ">
                                    Prioridad Entity:
                                    {{ $candidate->priority }}

                                    ·

                                    Definición:
                                    {{ $candidate->version->priority }}
                                </p>

                            </div>

                        </div>
                    @endforeach

                </div>

            </section>

        @endif

    @endif

</x-app-layout>
