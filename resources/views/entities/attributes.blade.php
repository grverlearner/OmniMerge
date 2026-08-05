<x-app-layout>
    <x-slot name="header">
        Atributos de {{ $entity->name }}
    </x-slot>

    <div class="mb-6 flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
        <div>
            <h2 class="text-2xl font-black text-slate-900">
                Personalizar entidad
            </h2>

            <p class="mt-2 text-slate-500">
                Asigna valores personalizados a
                {{ $entity->name }}.
            </p>
        </div>

        <a
            href="{{ route('entities.show', $entity) }}"
            class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700"
        >
            Volver a la entidad
        </a>
    </div>

    <form
        method="POST"
        action="{{ route('entities.attributes.update', $entity) }}"
    >
        @csrf
        @method('PUT')

        <div class="space-y-6">
            @forelse ($attributes as $attribute)
                @php
                    $assignment = $existingValues->get(
                        $attribute->id
                    );

                    $values = $assignment?->values ?? collect();

                    $selectedOptionIds = $values
                        ->pluck('attribute_option_id')
                        ->filter()
                        ->map(fn ($id) => (string) $id)
                        ->all();

                    $firstValue = $values->first();
                @endphp

                <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <div class="mb-4">
                        <div class="flex flex-wrap items-center gap-2">
                            <h3 class="font-bold text-slate-900">
                                {{ $attribute->name }}
                            </h3>

                            @if ($attribute->is_required)
                                <span class="rounded-full bg-red-100 px-2 py-0.5 text-xs font-semibold text-red-700">
                                    Obligatorio
                                </span>
                            @endif

                            @if ($attribute->allows_multiple)
                                <span class="rounded-full bg-indigo-100 px-2 py-0.5 text-xs font-semibold text-indigo-700">
                                    Múltiple
                                </span>
                            @endif
                        </div>

                        @if ($attribute->help_text)
                            <p class="mt-2 text-sm text-slate-500">
                                {{ $attribute->help_text }}
                            </p>
                        @endif
                    </div>

                    @if ($attribute->data_type === 'OPTION')
                        @if ($attribute->allows_multiple)
                            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                                @foreach ($attribute->options as $option)
                                    <label class="flex cursor-pointer items-center gap-3 rounded-xl border border-slate-200 p-4 hover:border-indigo-300 hover:bg-indigo-50">
                                        <input
                                            type="checkbox"
                                            name="attributes[{{ $attribute->id }}][]"
                                            value="{{ $option->id }}"
                                            @checked(
                                                in_array(
                                                    (string) $option->id,
                                                    old(
                                                        "attributes.{$attribute->id}",
                                                        $selectedOptionIds
                                                    )
                                                )
                                            )
                                            class="rounded border-slate-300 text-indigo-600"
                                        >

                                        <span>
                                            {{ $option->icon }}
                                            {{ $option->name }}
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                        @else
                            <select
                                name="attributes[{{ $attribute->id }}]"
                                class="w-full rounded-xl border-slate-300 focus:border-indigo-500 focus:ring-indigo-500"
                            >
                                <option value="">
                                    Seleccionar
                                </option>

                                @foreach ($attribute->options as $option)
                                    <option
                                        value="{{ $option->id }}"
                                        @selected(
                                            old(
                                                "attributes.{$attribute->id}",
                                                $firstValue?->attribute_option_id
                                            ) == $option->id
                                        )
                                    >
                                        {{ $option->icon }}
                                        {{ $option->name }}
                                    </option>
                                @endforeach
                            </select>
                        @endif

                    @elseif ($attribute->data_type === 'LONG_TEXT')
                        <textarea
                            name="attributes[{{ $attribute->id }}]"
                            rows="5"
                            class="w-full rounded-xl border-slate-300 focus:border-indigo-500 focus:ring-indigo-500"
                            placeholder="{{ $attribute->placeholder }}"
                        >{{ old(
                            "attributes.{$attribute->id}",
                            $firstValue?->text_value
                        ) }}</textarea>

                    @elseif ($attribute->data_type === 'BOOLEAN')
                        <select
                            name="attributes[{{ $attribute->id }}]"
                            class="w-full rounded-xl border-slate-300 focus:border-indigo-500 focus:ring-indigo-500"
                        >
                            <option value="">Sin definir</option>
                            <option
                                value="1"
                                @selected(
                                    old(
                                        "attributes.{$attribute->id}",
                                        $firstValue?->boolean_value
                                    ) === true
                                    || old(
                                        "attributes.{$attribute->id}"
                                    ) === '1'
                                )
                            >
                                Sí
                            </option>
                            <option
                                value="0"
                                @selected(
                                    old(
                                        "attributes.{$attribute->id}"
                                    ) === '0'
                                    || (
                                        $firstValue
                                        && $firstValue->boolean_value === false
                                    )
                                )
                            >
                                No
                            </option>
                        </select>

                    @else
                        @php
                            $inputType = match (
                                $attribute->data_type
                            ) {
                                'INTEGER',
                                'DECIMAL' => 'number',
                                'DATE' => 'date',
                                'COLOR' => 'color',
                                default => 'text',
                            };

                            $currentValue = match (
                                $attribute->data_type
                            ) {
                                'INTEGER' =>
                                    $firstValue?->integer_value,
                                'DECIMAL' =>
                                    $firstValue?->decimal_value,
                                'DATE' =>
                                    $firstValue?->date_value?->format('Y-m-d'),
                                'COLOR' =>
                                    $firstValue?->color_value,
                                default =>
                                    $firstValue?->text_value,
                            };
                        @endphp

                        <input
                            type="{{ $inputType }}"
                            name="attributes[{{ $attribute->id }}]"
                            value="{{ old(
                                "attributes.{$attribute->id}",
                                $currentValue
                            ) }}"
                            placeholder="{{ $attribute->placeholder }}"
                            @if ($attribute->data_type === 'DECIMAL')
                                step="any"
                            @endif
                            @if ($attribute->min_numeric_value !== null)
                                min="{{ $attribute->min_numeric_value }}"
                            @endif
                            @if ($attribute->max_numeric_value !== null)
                                max="{{ $attribute->max_numeric_value }}"
                            @endif
                            class="w-full rounded-xl border-slate-300 focus:border-indigo-500 focus:ring-indigo-500"
                        >
                    @endif

                    @error("attributes.{$attribute->id}")
                        <p class="mt-2 text-sm text-red-600">
                            {{ $message }}
                        </p>
                    @enderror
                </section>
            @empty
                <div class="rounded-2xl border border-dashed border-slate-300 bg-white py-16 text-center">
                    <p class="font-semibold text-slate-700">
                        Todavía no existen atributos
                    </p>

                    <a
                        href="{{ route('attributes.create') }}"
                        class="mt-4 inline-block text-sm font-bold text-indigo-600"
                    >
                        Crear el primer atributo
                    </a>
                </div>
            @endforelse
        </div>

        @if ($attributes->isNotEmpty())
            <div class="sticky bottom-4 mt-8 flex justify-end">
                <button
                    type="submit"
                    class="rounded-xl bg-indigo-600 px-6 py-3 font-bold text-white shadow-xl shadow-indigo-600/30 hover:bg-indigo-700"
                >
                    Guardar atributos
                </button>
            </div>
        @endif
    </form>
</x-app-layout>