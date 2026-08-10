<x-app-layout>

    <x-slot name="header">
        Características de Version
    </x-slot>


    @include('entities.partials.section-navigation')


    <section
        class="
            rounded-3xl
            bg-gradient-to-br
            from-indigo-950
            via-violet-950
            to-slate-950
            p-6
            text-white
            sm:p-8
        ">

        <p
            class="
                text-[10px]
                font-black
                uppercase
                text-violet-300
            ">
            {{ $entity->name }}
            →
            {{ $entityVersion->version->name }}
        </p>


        <h1 class="
                mt-2
                text-3xl
                font-black
            ">
            {{ $entityVersion->name }}
        </h1>


        <p
            class="
                mt-3
                max-w-3xl
                text-sm
                leading-6
                text-white/70
            ">
            Para cada característica elige si debe
            heredarse, sobrescribirse o desaparecer
            en esta Version.
        </p>

    </section>


    @if ($errors->any())

        <div
            class="
                mt-5
                rounded-2xl
                bg-red-50
                p-5
                text-sm
                text-red-700
            ">
            @foreach ($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>

    @endif


    <form method="POST"
        action="{{ route('entity-versions.attributes.update', [$entity, $entityVersion]) }}"
        class="
            mt-6
            space-y-4
        ">

        @csrf
        @method('PUT')


        @foreach ($attributes as $attribute)
            @php

                $assignment = $entityVersion->versionAttributes->firstWhere('attribute_id', $attribute->id);

                $currentMode = old("attributes.{$attribute->id}.mode", $assignment?->behavior ?? 'INHERIT');

                $currentValue = old(
                    "attributes.{$attribute->id}.value",
                    $versionValues[$attribute->id] ?? ($attribute->allows_multiple ? [] : ''),
                );

                $baseAssignment = $entity->entityAttributes->firstWhere('attribute_id', $attribute->id);

                $baseDisplay = $baseAssignment
                    ? $baseAssignment->values->map(fn($value) => $value->displayValue())->filter()->implode(', ')
                    : null;

            @endphp


            <article x-data="{
                mode: @js($currentMode)
            }"
                class="
                    rounded-3xl
                    border
                    border-slate-200
                    bg-white
                    p-5
                    shadow-sm
                ">

                <div
                    class="
                        flex
                        flex-col
                        gap-4
                        lg:flex-row
                        lg:items-start
                    ">

                    {{-- ATTRIBUTE --}}
                    <div
                        class="
                            flex
                            min-w-0
                            flex-1
                            gap-3
                        ">

                        <div
                            class="
                                flex
                                h-11
                                w-11
                                shrink-0
                                items-center
                                justify-center
                                rounded-xl
                                bg-violet-50
                                font-black
                                text-violet-600
                            ">
                            {{ $attribute->data_type_icon }}
                        </div>


                        <div class="min-w-0">

                            <p
                                class="
                                    font-black
                                    text-slate-800
                                ">
                                {{ $attribute->name }}

                                @if ($attribute->is_required)
                                    <span class="text-red-500">*</span>
                                @endif
                            </p>


                            <p
                                class="
                                    mt-1
                                    text-[9px]
                                    text-slate-400
                                ">
                                {{ $attribute->data_type_label }}

                                @if ($attribute->allows_multiple)
                                    · Multivalor
                                @endif
                            </p>


                            <p
                                class="
                                    mt-2
                                    text-xs
                                    text-slate-500
                                ">
                                Base:

                                <strong>
                                    {{ $baseDisplay ?: 'Sin valor' }}
                                </strong>
                            </p>

                        </div>

                    </div>


                    {{-- MODE --}}
                    <div
                        class="
                            w-full
                            lg:w-56
                        ">
                        <label
                            class="
                                text-[9px]
                                font-black
                                uppercase
                                text-slate-400
                            ">
                            Comportamiento
                        </label>

                        <select x-model="mode" name="attributes[{{ $attribute->id }}][mode]"
                            class="
                                mt-1
                                w-full
                                rounded-xl
                                border-slate-300
                                text-sm
                            ">
                            <option value="INHERIT">
                                Heredar
                            </option>

                            <option value="OVERRIDE">
                                Sobrescribir
                            </option>

                            <option value="HIDE">
                                Ocultar
                            </option>
                        </select>
                    </div>

                </div>


                {{-- OVERRIDE --}}
                <div x-show="
                        mode === 'OVERRIDE'
                    " x-cloak
                    class="
                        mt-5
                        rounded-2xl
                        border
                        border-violet-100
                        bg-violet-50/30
                        p-4
                    ">

                    <label
                        class="
                            mb-2
                            block
                            text-[9px]
                            font-black
                            uppercase
                            text-violet-500
                        ">
                        Valor para esta Version
                    </label>


                    @if ($attribute->data_type === 'OPTION' && !$attribute->allows_multiple)
                        <select name="attributes[{{ $attribute->id }}][value]"
                            class="
                                w-full
                                rounded-xl
                                border-violet-200
                            ">
                            <option value="">
                                Sin valor
                            </option>

                            @foreach ($attribute->options as $option)
                                <option value="{{ $option->id }}" @selected((string) $currentValue === (string) $option->id)>
                                    {{ $option->name }}
                                </option>
                            @endforeach
                        </select>
                    @elseif ($attribute->data_type === 'OPTION' && $attribute->allows_multiple)
                        <select name="attributes[{{ $attribute->id }}][value][]" multiple
                            class="
                                min-h-36
                                w-full
                                rounded-xl
                                border-violet-200
                            ">
                            @foreach ($attribute->options as $option)
                                <option value="{{ $option->id }}" @selected(in_array((string) $option->id, collect((array) $currentValue)->map(fn($id) => (string) $id)->all(), true))>
                                    {{ $option->name }}
                                </option>
                            @endforeach
                        </select>
                    @elseif ($attribute->data_type === 'BOOLEAN')
                        <select name="attributes[{{ $attribute->id }}][value]"
                            class="
                                w-full
                                rounded-xl
                                border-violet-200
                            ">
                            <option value="">
                                Sin valor
                            </option>

                            <option value="1" @selected((string) $currentValue === '1')>
                                Sí
                            </option>

                            <option value="0" @selected((string) $currentValue === '0')>
                                No
                            </option>
                        </select>
                    @elseif (in_array($attribute->data_type, ['INTEGER', 'DECIMAL'], true))
                        <input type="number" name="attributes[{{ $attribute->id }}][value]"
                            value="{{ $currentValue }}"
                            step="{{ $attribute->data_type === 'INTEGER' ? '1' : 'any' }}"
                            class="
                                w-full
                                rounded-xl
                                border-violet-200
                            ">
                    @elseif ($attribute->data_type === 'DATE')
                        <input type="date" name="attributes[{{ $attribute->id }}][value]"
                            value="{{ $currentValue }}"
                            class="
                                w-full
                                rounded-xl
                                border-violet-200
                            ">
                    @elseif ($attribute->data_type === 'COLOR')
                        <input type="text" name="attributes[{{ $attribute->id }}][value]"
                            value="{{ $currentValue }}" placeholder="#6366F1"
                            class="
                                w-full
                                rounded-xl
                                border-violet-200
                                font-mono
                            ">
                    @elseif ($attribute->data_type === 'LONG_TEXT')
                        <textarea name="attributes[{{ $attribute->id }}][value]" rows="4"
                            class="
                                w-full
                                rounded-xl
                                border-violet-200
                            ">{{ $currentValue }}</textarea>
                    @else
                        <input type="text" name="attributes[{{ $attribute->id }}][value]"
                            value="{{ $currentValue }}"
                            class="
                                w-full
                                rounded-xl
                                border-violet-200
                            ">
                    @endif

                </div>


                <div x-show="
                        mode === 'INHERIT'
                    "
                    class="
                        mt-4
                        rounded-xl
                        bg-slate-50
                        p-3
                        text-xs
                        text-slate-500
                    ">
                    ↳ Se utilizará el valor heredado
                    de la Entidad o Versión padre.
                </div>


                <div x-show="
                        mode === 'HIDE'
                    " x-cloak
                    class="
                        mt-4
                        rounded-xl
                        bg-red-50
                        p-3
                        text-xs
                        font-bold
                        text-red-600
                    ">
                    × Esta característica no existirá
                    en el resultado efectivo de esta Version.
                </div>

            </article>
        @endforeach


        <div
            class="
                sticky
                bottom-4
                z-30
                flex
                flex-col
                gap-3
                rounded-2xl
                border
                border-slate-200
                bg-white/95
                p-4
                shadow-2xl
                backdrop-blur
                sm:flex-row
                sm:items-center
                sm:justify-between
            ">

            <a href="{{ route('entity-versions.show', [$entity, $entityVersion]) }}"
                class="
                    text-sm
                    font-bold
                    text-slate-500
                ">
                ← Cancelar
            </a>


            <button
                class="
                    rounded-xl
                    bg-violet-600
                    px-6
                    py-3
                    text-sm
                    font-black
                    text-white
                ">
                Guardar características
            </button>

        </div>

    </form>

</x-app-layout>
