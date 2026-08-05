@php
    $editing = isset($attribute);
@endphp

<div x-data="{
    dataType: '{{ old('data_type', $attribute->data_type ?? 'TEXT') }}',
    valueSource: '{{ old('value_source', $attribute->value_source ?? 'FREE') }}'
}" class="space-y-8">
    <div class="grid gap-6 lg:grid-cols-2">
        <div>
            <label class="mb-2 block text-sm font-semibold">
                Nombre *
            </label>

            <input name="name" value="{{ old('name', $attribute->name ?? '') }}" required
                class="w-full rounded-xl border-slate-300" placeholder="Ejemplo: Elemento">
        </div>

        <div>
            <label class="mb-2 block text-sm font-semibold">
                Código
            </label>

            <input name="code" value="{{ old('code', $attribute->code ?? '') }}"
                class="w-full rounded-xl border-slate-300 uppercase">
        </div>

        <div>
            <label class="mb-2 block text-sm font-semibold">
                Identificador URL
            </label>

            <input name="slug" value="{{ old('slug', $attribute->slug ?? '') }}"
                class="w-full rounded-xl border-slate-300" placeholder="elemento">

            <p class="mt-2 text-xs text-slate-500">
                Se genera automáticamente si lo dejas vacío.
            </p>
        </div>

        <div>
            <label class="mb-2 block text-sm font-semibold">
                Tipo de dato *
            </label>

            <select name="data_type" x-model="dataType" class="w-full rounded-xl border-slate-300">
                <option value="TEXT" @selected(old('data_type', $attribute->data_type ?? 'TEXT') === 'TEXT')> Texto corto </option>
                <option value="LONG_TEXT">Texto largo</option>
                <option value="INTEGER">Número entero</option>
                <option value="DECIMAL">Número decimal</option>
                <option value="BOOLEAN">Sí o no</option>
                <option value="DATE">Fecha</option>
                <option value="COLOR">Color</option>
                <option value="OPTION">Catálogo de opciones</option>
            </select>
        </div>

        <div>
            <label class="mb-2 block text-sm font-semibold">
                Origen de valores *
            </label>

            <select name="value_source" x-model="valueSource" class="w-full rounded-xl border-slate-300">
                <option value="FREE">Valor libre</option>
                <option value="CATALOG">Solo catálogo</option>
                <option value="MIXED">Catálogo y valor libre</option>
            </select>
        </div>

        <div>
            <label class="mb-2 block text-sm font-semibold">
                Presentación
            </label>

            <select name="display_style" class="w-full rounded-xl border-slate-300">
                <option value="TEXTBOX">Caja de texto</option>
                <option value="TEXTAREA">Área de texto</option>
                <option value="NUMBER">Número</option>
                <option value="SELECT">Lista</option>
                <option value="MULTISELECT">Selección múltiple</option>
                <option value="RADIO">Opciones únicas</option>
                <option value="CHECKBOX">Casilla</option>
                <option value="TAGS">Etiquetas</option>
                <option value="SLIDER">Deslizador</option>
                <option value="COLOR_PICKER">Selector de color</option>
                <option value="DATE_PICKER">Selector de fecha</option>
            </select>
        </div>

        <div>
            <label class="mb-2 block text-sm font-semibold">
                Unidad
            </label>

            <input name="unit" value="{{ old('unit', $attribute->unit ?? '') }}"
                class="w-full rounded-xl border-slate-300" placeholder="kg, puntos, metros...">
        </div>

        <div class="lg:col-span-2">
            <label class="mb-2 block text-sm font-semibold">
                Descripción
            </label>

            <textarea name="description" rows="4" class="w-full rounded-xl border-slate-300">{{ old('description', $attribute->description ?? '') }}</textarea>
        </div>

        <div class="lg:col-span-2">
            <label class="mb-2 block text-sm font-semibold">
                Texto de ayuda
            </label>

            <input name="help_text" value="{{ old('help_text', $attribute->help_text ?? '') }}"
                class="w-full rounded-xl border-slate-300">
        </div>
    </div>

    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
        @foreach ([
        'allows_multiple' => 'Múltiples valores',
        'allows_custom_values' => 'Valores personalizados',
        'is_required' => 'Obligatorio',
        'is_filterable' => 'Utilizable en filtros',
        'is_comparable' => 'Comparable',
        'is_searchable' => 'Buscable',
        'is_visible' => 'Visible',
        'is_featured' => 'Destacado',
    ] as $field => $label)
            <label class="flex items-center gap-3 rounded-xl border border-slate-200 p-4">
                <input type="checkbox" name="{{ $field }}" value="1" @checked(old(
                        $field,
                        $attribute->{$field} ?? in_array($field, ['is_filterable', 'is_comparable', 'is_searchable', 'is_visible'])))
                    class="rounded border-slate-300 text-indigo-600">

                <span class="text-sm font-medium">
                    {{ $label }}
                </span>
            </label>
        @endforeach
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
        <div>
            <label class="mb-2 block text-sm font-semibold">
                Estado
            </label>

            <select name="status" class="w-full rounded-xl border-slate-300">
                <option value="ACTIVE">Activo</option>
                <option value="INACTIVE">Inactivo</option>
                <option value="ARCHIVED">Archivado</option>
            </select>
        </div>

        <div>
            <label class="mb-2 block text-sm font-semibold">
                Orden
            </label>

            <input type="number" name="sort_order" min="0"
                value="{{ old('sort_order', $attribute->sort_order ?? 0) }}"
                class="w-full rounded-xl border-slate-300">
        </div>
    </div>

    <div class="flex justify-end gap-3 border-t pt-6">
        <a href="{{ route('attributes.index') }}" class="rounded-xl border px-5 py-3">
            Cancelar
        </a>

        <button class="rounded-xl bg-indigo-600 px-5 py-3 font-bold text-white">
            {{ $editing ? 'Guardar cambios' : 'Crear atributo' }}
        </button>
    </div>
</div>
