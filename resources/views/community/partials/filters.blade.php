<form method="GET" action="{{ route('community.index') }}"
    class="mt-5 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
    <input type="hidden" name="tab" value="{{ $tab }}">

    <div class="grid gap-3 lg:grid-cols-[1fr_220px_220px_auto]">
        <div>
            <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-slate-400">
                Búsqueda
            </label>

            <input type="search" name="search" value="{{ $search }}"
                placeholder="Nombre, descripción o creador..."
                class="w-full rounded-xl border-slate-300 focus:border-indigo-500 focus:ring-indigo-500">
        </div>

        @if ($tab === 'entities')
            <div>
                <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-slate-400">
                    Tipo de entidad
                </label>

                <select name="entity_type"
                    class="w-full rounded-xl border-slate-300 focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">
                        Todos los tipos
                    </option>

                    @foreach ($entityTypes as $entityType)
                        <option value="{{ $entityType->id }}" @selected($entityTypeId === $entityType->id)>
                            {{ $entityType->name }}
                        </option>
                    @endforeach
                </select>
            </div>
        @elseif ($tab === 'attributes')
            <div>
                <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-slate-400">
                    Tipo de dato
                </label>

                <select name="data_type"
                    class="w-full rounded-xl border-slate-300 focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">
                        Todos los tipos
                    </option>

                    @foreach ([
        'TEXT' => 'Texto',
        'LONG_TEXT' => 'Texto largo',
        'INTEGER' => 'Número entero',
        'DECIMAL' => 'Decimal',
        'BOOLEAN' => 'Sí o no',
        'DATE' => 'Fecha',
        'COLOR' => 'Color',
        'OPTION' => 'Catálogo',
    ] as $value => $label)
                        <option value="{{ $value }}" @selected($dataType === $value)>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
            </div>
        @else
            <div class="hidden lg:block"></div>
        @endif

        <div>
            <label class="mb-2 block text-xs font-bold uppercase tracking-wider text-slate-400">
                Ordenar
            </label>

            <select name="sort"
                class="w-full rounded-xl border-slate-300 focus:border-indigo-500 focus:ring-indigo-500">
                @foreach ([
        'popular' => 'Más populares',
        'newest' => 'Más recientes',
        'oldest' => 'Más antiguos',
        'name' => 'Nombre A–Z',
        'cloned' => 'Más clonados',
        'viewed' => 'Más vistos',
    ] as $value => $label)
                    <option value="{{ $value }}" @selected($sort === $value)>
                        {{ $label }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="flex items-end gap-2">
            <button type="submit"
                class="flex-1 rounded-xl bg-slate-900 px-5 py-3 text-sm font-bold text-white hover:bg-slate-800">
                Aplicar
            </button>

            <a href="{{ route('community.index', ['tab' => $tab]) }}"
                class="rounded-xl border border-slate-300 px-4 py-3 text-sm font-bold text-slate-600 hover:bg-slate-50"
                title="Limpiar filtros">
                ×
            </a>
        </div>
    </div>
</form>
