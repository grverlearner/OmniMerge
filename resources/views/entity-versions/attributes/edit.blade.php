@php
    /*
     * Decidir qué características cambia una versión.
     *
     * La pantalla tenía la decisión escondida en un desplegable de tres
     * palabras —«Heredar», «Sobrescribir», «Ocultar»— que no dicen qué pasa.
     * Y no enseñaba lo más importante para decidir: **qué vale ahora mismo en
     * la entidad**, que es de lo que se parte.
     *
     * Ahora cada característica es una fila con:
     *
     *   · el valor heredado, con su cara, siempre a la vista
     *   · las tres decisiones como botones que dicen qué hacen
     *   · el campo del valor propio, solo cuando hace falta
     *
     * Y arriba, un contador de cuántas se cambian de verdad: es la cifra que
     * define esta versión.
     */

    $modos = [
        'INHERIT' => ['Igual que la entidad', 'border-slate-700 bg-slate-800 text-slate-200'],
        'OVERRIDE' => ['Cambiarla aquí', 'border-violet-500 bg-violet-500/15 text-violet-200'],
        'HIDE' => ['Que no la tenga', 'border-rose-500 bg-rose-500/15 text-rose-200'],
    ];

    $conValor = collect($attributes)->filter(
        fn($atributo) => $entity->entityAttributes->firstWhere('attribute_id', $atributo->id) !== null,
    );

    $cambiadas = collect($versionValues)->keys()->count();
@endphp

<x-app-layout :title="'Características de ' . $entityVersion->name" surface="dark">

    <x-slot name="header">Versiones</x-slot>

    <form method="POST" action="{{ route('entity-versions.attributes.update', [$entity, $entityVersion]) }}"
        x-data="{
            soloAsignadas: true,
            buscar: '',
        }" class="space-y-4">

        @csrf
        @method('PUT')


        {{-- ===================================================== --}}
        {{-- CABECERA --}}
        {{-- ===================================================== --}}

        <header class="flex flex-wrap items-center gap-3">

            <a href="{{ route('entity-versions.show', [$entity, $entityVersion]) }}"
                class="h-12 w-12 shrink-0 overflow-hidden rounded-xl border border-slate-800 bg-slate-950">
                @if ($entityVersion->image_url)
                    <img src="{{ $entityVersion->image_url }}" alt="" class="h-full w-full object-cover">
                @else
                    <span class="flex h-full w-full items-center justify-center text-slate-700">◈</span>
                @endif
            </a>

            <div class="min-w-0 flex-1">
                <a href="{{ route('entity-versions.show', [$entity, $entityVersion]) }}"
                    class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-600 transition hover:text-violet-400">
                    ← {{ $entityVersion->name }}
                </a>

                <h1 class="mt-0.5 truncate text-xl font-black tracking-tight text-white">
                    ¿Qué cambia en esta versión?
                </h1>

                <p class="text-[10px] text-slate-500">
                    Parte de {{ $entity->name }} y solo guarda lo que sea distinto.
                </p>
            </div>

            <a href="{{ route('entity-versions.show', [$entity, $entityVersion]) }}"
                class="rounded-xl border border-slate-800 bg-slate-900/50 px-3 py-2 text-[11px] font-black text-slate-400 transition hover:border-slate-700 hover:text-white">
                Cancelar
            </a>
        </header>


        @include('versions.partials.workspace-navigation')


        @if ($errors->any())
            <div class="rounded-2xl border border-rose-500/30 bg-rose-500/10 px-4 py-2.5 text-[12px] font-bold text-rose-200">
                <ul class="space-y-0.5">
                    @foreach ($errors->all() as $error)
                        <li>· {{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif


        {{-- ===================================================== --}}
        {{-- DE QUÉ SE PARTE --}}
        {{-- ===================================================== --}}

        <section class="flex flex-wrap items-center gap-3 rounded-2xl border border-slate-800 bg-slate-900/50 p-3">

            <a href="{{ route('entities.show', $entity) }}"
                class="group flex min-w-0 items-center gap-2.5 rounded-xl border border-slate-800 bg-slate-950 p-2 transition hover:border-indigo-500/50">
                <span class="h-10 w-10 shrink-0 overflow-hidden rounded-lg border border-slate-800 bg-slate-900">
                    @if ($entity->image_url)
                        <img src="{{ $entity->image_url }}" alt="" class="h-full w-full object-cover">
                    @else
                        <span class="flex h-full w-full items-center justify-center text-slate-700">◍</span>
                    @endif
                </span>
                <span class="min-w-0">
                    <span class="block text-[9px] font-black uppercase tracking-wider text-slate-600">Se parte de</span>
                    <span class="block truncate text-[12px] font-black text-white transition group-hover:text-indigo-300">{{ $entity->name }}</span>
                </span>
            </a>

            <span class="text-slate-700">→</span>

            <span class="flex min-w-0 items-center gap-2.5 rounded-xl border border-violet-500/30 bg-violet-500/5 p-2">
                <span class="h-10 w-10 shrink-0 overflow-hidden rounded-lg border border-slate-800 bg-slate-900">
                    @if ($entityVersion->image_url)
                        <img src="{{ $entityVersion->image_url }}" alt="" class="h-full w-full object-cover">
                    @endif
                </span>
                <span class="min-w-0">
                    <span class="block text-[9px] font-black uppercase tracking-wider text-violet-400/70">Se está editando</span>
                    <span class="block truncate text-[12px] font-black text-white">{{ $entityVersion->name }}</span>
                </span>
            </span>

            <div class="min-w-0 flex-1 text-[11px] leading-relaxed text-slate-500">
                Ahora mismo cambia <strong class="font-mono text-base text-violet-300">{{ $cambiadas }}</strong>
                {{ $cambiadas === 1 ? 'característica' : 'características' }}
                de las {{ $conValor->count() }} que {{ $entity->name }} tiene asignadas.
                Lo que no toques se hereda, y seguirá cambiando solo si cambia la entidad.
            </div>
        </section>


        {{-- ===================================================== --}}
        {{-- QUÉ SIGNIFICA CADA DECISIÓN --}}
        {{-- ===================================================== --}}

        <section x-data="{ abierto: false }"
            class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/50">

            <button type="button" @click="abierto = !abierto"
                class="flex w-full items-center gap-3 px-4 py-2.5 text-left transition hover:bg-slate-950/50">
                <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-violet-500/15 text-violet-300">
                    <x-omni-icon name="grafo" size="h-3.5 w-3.5" />
                </span>

                <span class="min-w-0 flex-1 text-[12px] font-black text-white">
                    Las tres decisiones
                    <span class="font-bold text-slate-500">— qué hace cada una, en un dibujo</span>
                </span>

                <span class="shrink-0 text-slate-500 transition" :class="abierto ? 'rotate-90' : ''">
                    <x-omni-icon name="chevron-derecha" size="h-4 w-4" />
                </span>
            </button>

            <div x-show="abierto" x-cloak x-collapse class="grid gap-3 border-t border-slate-800 p-4 lg:grid-cols-3">

                @foreach ([['Igual que la entidad', 'text-slate-400', 'La versión no dice nada: usa el valor de la entidad. Si mañana cambias la entidad, esta versión cambia con ella.'], ['Cambiarla aquí', 'text-violet-400', 'La versión guarda su propio valor. A partir de ahí es independiente: cambiar la entidad ya no la afecta.'], ['Que no la tenga', 'text-rose-400', 'La característica desaparece en esta versión, aunque la entidad la tenga. Útil cuando algo deja de aplicar.']] as $i => [$titulo, $tono, $texto])
                    <div class="rounded-xl border border-slate-800 bg-slate-950 p-3">

                        <svg viewBox="0 0 120 46" class="h-11 w-full {{ $tono }}" fill="none"
                            stroke="currentColor" stroke-width="1.4" stroke-linecap="round" aria-hidden="true">

                            <rect x="4" y="12" width="34" height="22" rx="3" />
                            <path d="M10 20h22M10 27h14" opacity=".5" />

                            @if ($i === 0)
                                <path d="M42 23h22M64 23l-5-3M64 23l-5 3" opacity=".8" />
                                <rect x="70" y="12" width="34" height="22" rx="3" stroke-dasharray="3 2" />
                                <path d="M76 20h22M76 27h14" opacity=".5" />
                            @elseif ($i === 1)
                                <path d="M42 23h22M64 23l-5-3M64 23l-5 3" opacity=".35" stroke-dasharray="3 2" />
                                <rect x="70" y="12" width="34" height="22" rx="3" />
                                <path d="M76 20h22" opacity=".3" />
                                <path d="M76 27h18" opacity="1" />
                                <circle cx="106" cy="27" r="4" opacity=".9" />
                            @else
                                <path d="M42 23h16" opacity=".35" stroke-dasharray="3 2" />
                                <path d="M62 17l12 12M74 17l-12 12" opacity=".9" />
                                <rect x="82" y="12" width="34" height="22" rx="3" stroke-dasharray="3 2" opacity=".35" />
                            @endif
                        </svg>

                        <p class="mt-2 text-[11px] font-black text-white">{{ $titulo }}</p>
                        <p class="mt-0.5 text-[10px] leading-4 text-slate-500">{{ $texto }}</p>
                    </div>
                @endforeach

            </div>
        </section>


        {{-- ===================================================== --}}
        {{-- FILTROS --}}
        {{-- ===================================================== --}}

        <div class="sticky top-20 z-20 flex flex-wrap items-center gap-2 rounded-2xl border border-slate-800 bg-slate-950/95 px-4 py-3 backdrop-blur">

            <label class="relative min-w-[180px] flex-1">
                <span class="sr-only">Buscar característica</span>
                <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-slate-600">
                    <x-omni-icon name="brujula" size="h-3.5 w-3.5" />
                </span>
                <input type="search" x-model="buscar" placeholder="Buscar característica…"
                    class="w-full rounded-xl border-slate-800 bg-slate-900 pl-9 text-xs text-slate-200 placeholder:text-slate-600 focus:border-violet-500 focus:ring-violet-500">
            </label>

            <label class="flex cursor-pointer items-center gap-2 rounded-xl border border-slate-800 bg-slate-900 px-3 py-2">
                <input type="checkbox" x-model="soloAsignadas"
                    class="rounded border-slate-700 bg-slate-950 text-violet-500">
                <span class="text-[11px] font-black text-slate-300">
                    Solo las que {{ $entity->name }} tiene
                </span>
            </label>

            <span class="text-[10px] text-slate-600">
                {{ $conValor->count() }} de {{ count($attributes) }}
            </span>
        </div>


        {{-- ===================================================== --}}
        {{-- LAS CARACTERÍSTICAS --}}
        {{-- ===================================================== --}}

        <div class="space-y-2">

            @forelse ($attributes as $attribute)
                @php
                    $tieneValor = array_key_exists($attribute->id, $versionValues);

                    $currentMode = old("attributes.{$attribute->id}.mode", $tieneValor ? 'OVERRIDE' : 'INHERIT');

                    $currentValue = old(
                        "attributes.{$attribute->id}.value",
                        $versionValues[$attribute->id] ?? ($attribute->allows_multiple ? [] : ''),
                    );

                    $baseAssignment = $entity->entityAttributes->firstWhere('attribute_id', $attribute->id);

                    $baseOpciones = $baseAssignment
                        ? $baseAssignment->values->map(fn($v) => $v->option)->filter()->values()
                        : collect();

                    $baseDisplay = $baseAssignment
                        ? $baseAssignment->values->map(fn($v) => $v->displayValue())->filter()->implode(', ')
                        : null;
                @endphp

                <article x-data="{ mode: @js($currentMode) }"
                    x-show="(! buscar || @js(mb_strtolower($attribute->name)).includes(buscar.toLowerCase()))
                        && (! soloAsignadas || {{ $baseAssignment ? 'true' : 'false' }})"
                    :class="mode === 'OVERRIDE' ? 'border-violet-500/40' : (mode === 'HIDE' ? 'border-rose-500/40' : 'border-slate-800')"
                    class="overflow-hidden rounded-2xl border bg-slate-900/50 transition">

                    <div class="flex flex-wrap items-center gap-3 p-3">

                        {{-- Qué característica es --}}
                        <span class="h-10 w-10 shrink-0 overflow-hidden rounded-lg border border-slate-800 bg-slate-950">
                            @if ($attribute->image_url)
                                <img src="{{ $attribute->image_url }}" alt="" loading="lazy" class="h-full w-full object-cover">
                            @else
                                <span class="flex h-full w-full items-center justify-center text-slate-700">◱</span>
                            @endif
                        </span>

                        <div class="min-w-0 flex-1">
                            <p class="truncate text-[12px] font-black text-white">{{ $attribute->name }}</p>

                            {{-- Lo que vale ahora en la entidad, con su cara --}}
                            @if ($baseOpciones->isNotEmpty())
                                <div class="mt-1 flex flex-wrap items-center gap-1">
                                    <span class="text-[9px] font-black uppercase tracking-wider text-slate-600">
                                        En {{ $entity->name }}:
                                    </span>

                                    @foreach ($baseOpciones as $opcion)
                                        <span class="flex items-center gap-1 rounded border border-slate-800 bg-slate-950 py-0.5 pl-0.5 pr-1.5">
                                            <span class="h-4 w-4 shrink-0 overflow-hidden rounded-sm border border-slate-800">
                                                @if ($opcion->image_url)
                                                    <img src="{{ $opcion->image_url }}" alt="" loading="lazy" class="h-full w-full object-cover">
                                                @endif
                                            </span>
                                            <span class="text-[10px] font-bold text-slate-300">{{ $opcion->name }}</span>
                                        </span>
                                    @endforeach
                                </div>
                            @elseif (filled($baseDisplay))
                                <p class="mt-0.5 truncate text-[10px] text-slate-500">
                                    <span class="font-black uppercase tracking-wider text-slate-600">En {{ $entity->name }}:</span>
                                    {{ $baseDisplay }}
                                </p>
                            @else
                                <p class="mt-0.5 text-[10px] text-slate-700">
                                    {{ $entity->name }} no tiene valor para esta.
                                </p>
                            @endif
                        </div>

                        {{-- La decisión --}}
                        <div class="flex shrink-0 items-center gap-1 rounded-xl border border-slate-800 bg-slate-950 p-1">
                            @foreach ($modos as $valor => [$etiqueta, $tono])
                                <button type="button" @click="mode = '{{ $valor }}'"
                                    :class="mode === '{{ $valor }}' ? '{{ $tono }} border' : 'text-slate-500 hover:text-slate-200 border border-transparent'"
                                    class="rounded-lg px-2.5 py-1.5 text-[10px] font-black transition">
                                    {{ $etiqueta }}
                                </button>
                            @endforeach
                        </div>

                        <input type="hidden" name="attributes[{{ $attribute->id }}][mode]" :value="mode">
                    </div>


                    {{-- El valor propio --}}
                    <div x-show="mode === 'OVERRIDE'" x-cloak x-collapse
                        class="border-t border-violet-500/20 bg-violet-500/5 p-3">

                        <label class="mb-1.5 block text-[9px] font-black uppercase tracking-wider text-violet-400/80">
                            Qué vale en {{ $entityVersion->name }}
                        </label>

                        @if ($attribute->data_type === 'OPTION' && !$attribute->allows_multiple)

                            {{-- Elegir por la cara --}}
                            <div x-data="{ elegida: @js((string) $currentValue) }">
                                <input type="hidden" name="attributes[{{ $attribute->id }}][value]" :value="elegida">

                                <div class="grid max-h-52 grid-cols-3 gap-1.5 overflow-y-auto sm:grid-cols-6 lg:grid-cols-8">
                                    <button type="button" @click="elegida = ''"
                                        :class="elegida === '' ? 'border-violet-500 ring-1 ring-violet-500' : 'border-slate-800 hover:border-slate-600'"
                                        class="flex aspect-square flex-col items-center justify-center rounded-lg border bg-slate-950 text-center transition">
                                        <span class="text-[10px] font-black text-slate-500">Sin valor</span>
                                    </button>

                                    @foreach ($attribute->options as $option)
                                        <button type="button" @click="elegida = @js((string) $option->id)"
                                            title="{{ $option->name }}"
                                            :class="elegida === @js((string) $option->id) ? 'border-violet-500 ring-1 ring-violet-500' : 'border-slate-800 hover:border-slate-600'"
                                            class="overflow-hidden rounded-lg border bg-slate-950 transition">

                                            <span class="relative block aspect-square overflow-hidden bg-slate-900">
                                                @if ($option->image_url)
                                                    <img src="{{ $option->image_url }}" alt="" loading="lazy" class="h-full w-full object-cover">
                                                @else
                                                    <span class="flex h-full w-full items-center justify-center text-[11px] text-slate-700">◇</span>
                                                @endif

                                                <span x-show="elegida === @js((string) $option->id)" x-cloak
                                                    class="absolute inset-0 flex items-center justify-center bg-violet-500/30 text-sm font-black text-white">✓</span>
                                            </span>

                                            <span class="block truncate px-1 py-0.5 text-center text-[9px] font-black text-slate-400">
                                                {{ $option->name }}
                                            </span>
                                        </button>
                                    @endforeach
                                </div>
                            </div>

                        @elseif ($attribute->data_type === 'OPTION' && $attribute->allows_multiple)

                            @php
                                $marcadas = collect((array) $currentValue)->map(fn($id) => (string) $id)->all();
                            @endphp

                            <div class="grid max-h-52 grid-cols-3 gap-1.5 overflow-y-auto sm:grid-cols-6 lg:grid-cols-8">
                                @foreach ($attribute->options as $option)
                                    <label title="{{ $option->name }}"
                                        class="cursor-pointer overflow-hidden rounded-lg border border-slate-800 bg-slate-950 transition has-[:checked]:border-violet-500 has-[:checked]:ring-1 has-[:checked]:ring-violet-500">

                                        <input type="checkbox" name="attributes[{{ $attribute->id }}][value][]"
                                            value="{{ $option->id }}"
                                            @checked(in_array((string) $option->id, $marcadas, true))
                                            class="peer sr-only">

                                        <span class="relative block aspect-square overflow-hidden bg-slate-900">
                                            @if ($option->image_url)
                                                <img src="{{ $option->image_url }}" alt="" loading="lazy" class="h-full w-full object-cover">
                                            @else
                                                <span class="flex h-full w-full items-center justify-center text-[11px] text-slate-700">◇</span>
                                            @endif

                                            <span class="absolute inset-0 hidden items-center justify-center bg-violet-500/30 text-sm font-black text-white peer-checked:flex">✓</span>
                                        </span>

                                        <span class="block truncate px-1 py-0.5 text-center text-[9px] font-black text-slate-400">
                                            {{ $option->name }}
                                        </span>
                                    </label>
                                @endforeach
                            </div>

                            <p class="mt-1.5 text-[10px] text-slate-600">
                                Esta característica admite varios valores a la vez.
                            </p>

                        @elseif ($attribute->data_type === 'BOOLEAN')

                            <select name="attributes[{{ $attribute->id }}][value]"
                                class="w-full rounded-xl border-slate-800 bg-slate-950 text-xs text-slate-200 focus:border-violet-500 focus:ring-violet-500 sm:w-56">
                                <option value="">Sin valor</option>
                                <option value="1" @selected((string) $currentValue === '1')>Sí</option>
                                <option value="0" @selected((string) $currentValue === '0')>No</option>
                            </select>

                        @elseif (in_array($attribute->data_type, ['INTEGER', 'DECIMAL'], true))

                            <input type="number" name="attributes[{{ $attribute->id }}][value]"
                                value="{{ $currentValue }}"
                                step="{{ $attribute->data_type === 'DECIMAL' ? '0.01' : '1' }}"
                                class="w-full rounded-xl border-slate-800 bg-slate-950 text-xs text-slate-200 focus:border-violet-500 focus:ring-violet-500 sm:w-56">

                        @elseif ($attribute->data_type === 'DATE')

                            <input type="date" name="attributes[{{ $attribute->id }}][value]"
                                value="{{ $currentValue }}"
                                class="w-full rounded-xl border-slate-800 bg-slate-950 text-xs text-slate-200 focus:border-violet-500 focus:ring-violet-500 sm:w-56">

                        @elseif ($attribute->data_type === 'COLOR')

                            <input type="text" name="attributes[{{ $attribute->id }}][value]"
                                value="{{ $currentValue }}" placeholder="#8b5cf6"
                                class="w-full rounded-xl border-slate-800 bg-slate-950 text-xs text-slate-200 placeholder:text-slate-600 focus:border-violet-500 focus:ring-violet-500 sm:w-56">

                        @elseif ($attribute->data_type === 'LONG_TEXT')

                            <textarea name="attributes[{{ $attribute->id }}][value]" rows="4"
                                class="w-full rounded-xl border-slate-800 bg-slate-950 text-xs text-slate-200 focus:border-violet-500 focus:ring-violet-500">{{ $currentValue }}</textarea>

                        @else

                            <input type="text" name="attributes[{{ $attribute->id }}][value]"
                                value="{{ $currentValue }}"
                                class="w-full rounded-xl border-slate-800 bg-slate-950 text-xs text-slate-200 focus:border-violet-500 focus:ring-violet-500">

                        @endif
                    </div>


                    {{-- Las otras dos decisiones, dichas --}}
                    <div x-show="mode === 'INHERIT'" x-cloak
                        class="border-t border-slate-800 px-3 py-2 text-[10px] leading-relaxed text-slate-500">
                        Se usará el valor de
                        <strong class="text-slate-300">{{ $entityVersion->parent?->name ?? $entity->name }}</strong>,
                        y cambiará solo si aquel cambia.
                    </div>

                    <div x-show="mode === 'HIDE'" x-cloak
                        class="border-t border-rose-500/20 bg-rose-500/5 px-3 py-2 text-[10px] leading-relaxed text-rose-200">
                        Esta característica <strong>no existirá</strong> en {{ $entityVersion->name }}, aunque
                        {{ $entity->name }} la tenga. No se borra de la entidad.
                    </div>

                </article>

            @empty
                <div class="rounded-2xl border border-dashed border-slate-800 py-16 text-center">
                    <span class="inline-flex text-slate-700"><x-omni-icon name="controles" size="h-10 w-10" /></span>

                    <h2 class="mt-3 text-lg font-black text-white">No tienes características creadas</h2>

                    <p class="mx-auto mt-1.5 max-w-md text-xs leading-relaxed text-slate-500">
                        Las características son los datos que describen a tus entidades —fuerza, clan, edad—.
                        Se crean una vez y valen para toda la biblioteca.
                    </p>

                    <a href="{{ route('attributes.index') }}"
                        class="mt-4 inline-block rounded-xl bg-violet-500 px-4 py-2 text-[11px] font-black text-white transition hover:bg-violet-400">
                        Crear características →
                    </a>
                </div>
            @endforelse

        </div>


        {{-- ===================================================== --}}
        {{-- GUARDAR --}}
        {{-- ===================================================== --}}

        @if (count($attributes) > 0)
            <div class="sticky bottom-4 z-20 flex flex-wrap items-center gap-3 rounded-2xl border border-slate-800 bg-slate-950/95 px-4 py-3 backdrop-blur">

                <p class="min-w-0 flex-1 text-[11px] leading-relaxed text-slate-400">
                    Lo que dejes en <strong class="text-slate-300">«igual que la entidad»</strong> no se
                    guarda: se hereda. Así, cambiar {{ $entity->name }} sigue actualizando todas sus versiones
                    a la vez.
                </p>

                <a href="{{ route('entity-versions.show', [$entity, $entityVersion]) }}"
                    class="rounded-xl border border-slate-800 px-4 py-2 text-[11px] font-black text-slate-400 transition hover:text-white">
                    Cancelar
                </a>

                <button type="submit"
                    class="rounded-xl bg-violet-500 px-5 py-2.5 text-[11px] font-black text-white transition hover:bg-violet-400">
                    Guardar las características
                </button>
            </div>
        @endif

    </form>

</x-app-layout>
