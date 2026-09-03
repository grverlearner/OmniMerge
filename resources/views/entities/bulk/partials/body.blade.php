{{--
    El cuerpo de la creación masiva.

    Va en su propio archivo por una razón práctica: el componente de Alpine
    que mueve todo esto ocupa 2.300 líneas al final de la vista, y tener el
    marcado en el mismo archivo hacía imposible tocar uno sin arriesgar el
    otro. Aquí solo hay marcado; el motor sigue donde estaba.

    La pantalla está ordenada como se trabaja de verdad:

      cómo llenar    las cuatro formas de meter entidades, dibujadas
      01 ajustes     lo que van a compartir todas
      02 colecciones dónde van a vivir
      03 rasgos      qué características tendrán, y cuáles son iguales para todas
      04 comunes     el valor de las que son iguales
      05 entidades   la lista, en tabla o en fichas
      resumen        siempre a la vista, con lo que falta

    Los nombres de los campos son los que espera `BulkStoreEntityRequest` y no
    se tocan: `batch_name`, `entity_type_id`, `status`, `visibility`,
    `allow_cloning`, `duplicate_strategy`, `collection_ids[]`,
    `selected_attribute_ids[]`, `common_attribute_ids[]`, `common_attributes`,
    `rows[clave][…]`, `images[clave]` y `bulk_images[]`.
--}}


{{-- ===================================================== --}}
{{-- CABECERA --}}
{{-- ===================================================== --}}

<header
    class="relative overflow-hidden rounded-2xl border border-slate-800 bg-gradient-to-br from-slate-900 via-slate-900 to-indigo-950/40">

    <span class="pointer-events-none absolute -right-24 -top-28 h-64 w-64 rounded-full bg-indigo-500/10 blur-3xl"></span>

    <div class="relative flex flex-wrap items-end gap-4 px-5 py-5">

        <div class="min-w-0 flex-1">
            <a href="{{ route('entities.index') }}"
                class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-600 transition hover:text-indigo-400">
                ← Entidades
            </a>

            <h1 class="mt-1.5 text-2xl font-black tracking-tight text-white">
                Creación masiva
            </h1>

            <p class="mt-1 max-w-2xl text-[12px] leading-relaxed text-slate-400">
                Un lote es un grupo de entidades que comparten tipo, características y
                colecciones. Se define una vez lo que tienen en común y solo se escribe fila
                a fila lo que las diferencia.
            </p>
        </div>

        {{-- El pulso del lote, siempre visible --}}
        <div class="flex flex-wrap gap-2">
            @foreach ([['Filas', 'rows.length', 'text-white'], ['Listas', 'readyCount', 'text-emerald-300'], ['Sin imagen', 'rowsWithoutImage', 'text-amber-300'], ['Repetidas', 'existingNameCount', 'text-rose-300']] as [$etiqueta, $expresion, $color])
                <span class="flex items-baseline gap-2 rounded-xl border border-slate-800 bg-slate-950/60 px-3 py-2">
                    <span class="font-mono text-lg font-black {{ $color }}" x-text="{{ $expresion }}"></span>
                    <span class="text-[9px] font-black uppercase tracking-wider text-slate-600">
                        {{ $etiqueta }}
                    </span>
                </span>
            @endforeach
        </div>

    </div>

</header>


{{-- ===================================================== --}}
{{-- UN LOTE A MEDIAS DE ANTES --}}
{{-- ===================================================== --}}

{{--
    El borrador no se aplica solo.

    Antes se restauraba sin preguntar, y como al enviar también se guardaba,
    al entrar a crear un lote nuevo aparecían las entidades del lote anterior
    —que ya estaban creadas—. Ahora un lote nuevo nace vacío siempre y lo que
    quedara a medias se ofrece aquí.
--}}

<div x-show="pendingDraft" x-cloak
    class="flex flex-wrap items-center gap-3 rounded-2xl border border-amber-500/30 bg-amber-500/5 px-4 py-3">

    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-amber-500/15 text-amber-300">
        <x-omni-icon name="historial" size="h-4 w-4" />
    </span>

    <p class="min-w-0 flex-1 text-[11px] leading-4 text-slate-400">
        Dejaste un lote a medias en este navegador
        <span class="text-slate-500">
            (<span x-text="pendingDraft?.rows?.length ?? 0"></span>
            <span x-text="(pendingDraft?.rows?.length ?? 0) === 1 ? 'fila' : 'filas'"></span>).
        </span>
        Este lote empieza vacío; recupéralo solo si lo quieres continuar.
    </p>

    <button type="button" @click="resumeDraft()"
        class="shrink-0 rounded-lg bg-amber-500 px-3 py-1.5 text-[10px] font-black text-slate-950 transition hover:bg-amber-400">
        Recuperarlo
    </button>

    <button type="button" @click="clearDraft()"
        class="shrink-0 rounded-lg border border-slate-700 px-3 py-1.5 text-[10px] font-black text-slate-400 transition hover:border-rose-500 hover:text-rose-300">
        Descartarlo
    </button>

</div>


{{-- ===================================================== --}}
{{-- ERRORES DEL SERVIDOR --}}
{{-- ===================================================== --}}

@if ($errors->any())
    <section class="rounded-2xl border border-rose-500/40 bg-rose-500/10 p-4" role="alert">
        <p class="text-xs font-black uppercase tracking-wider text-rose-300">
            No se pudo crear el lote
        </p>

        <ul class="mt-2 max-h-40 list-disc space-y-1 overflow-y-auto pl-5 text-[11px] font-bold text-rose-200/80">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </section>
@endif


{{-- ===================================================== --}}
{{-- CÓMO LLENAR EL LOTE --}}
{{-- ===================================================== --}}

@include('entities.bulk.partials.methods')


<div class="grid gap-4 xl:grid-cols-[minmax(0,1fr)_320px] xl:items-start">

    {{-- ============================================================= --}}
    {{-- LO QUE SE DEFINE --}}
    {{-- ============================================================= --}}

    <div class="space-y-4">

        {{-- ===================================================== --}}
        {{-- 01 · AJUSTES DEL LOTE --}}
        {{-- ===================================================== --}}

        <section class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/50">

            <header class="flex items-center gap-3 border-b border-slate-800 px-5 py-3">
                <span
                    class="flex h-8 w-8 items-center justify-center rounded-lg bg-indigo-500/15 text-[11px] font-black text-indigo-300">
                    01
                </span>
                <div>
                    <h2 class="text-sm font-black text-white">Lo que van a compartir</h2>
                    <p class="text-[10px] text-slate-500">
                        Se aplica a todas las filas. Cada fila puede llevar la contraria en su tipo.
                    </p>
                </div>
            </header>

            <div class="grid gap-4 p-5 sm:grid-cols-2">

                <div class="sm:col-span-2">
                    <label for="batch-name" class="text-[9px] font-black uppercase tracking-wider text-slate-500">
                        Nombre del lote
                    </label>

                    <input id="batch-name" type="text" name="batch_name" x-model="batchName" maxlength="150"
                        placeholder="Ej. Personajes de Konoha — tanda 1"
                        class="mt-1.5 w-full rounded-xl border-slate-800 bg-slate-950 text-sm font-bold text-white placeholder:text-slate-700 focus:border-indigo-500 focus:ring-indigo-500">

                    <p class="mt-1.5 text-[10px] text-slate-600">
                        Solo para acordarte de qué metiste. No se guarda en cada entidad.
                    </p>
                </div>

                <div>
                    <label for="batch-type" class="text-[9px] font-black uppercase tracking-wider text-slate-500">
                        Tipo por defecto
                    </label>

                    <select id="batch-type" name="entity_type_id" x-model="entityTypeId"
                        class="mt-1.5 w-full rounded-xl border-slate-800 bg-slate-950 text-xs font-bold text-slate-200 focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">Sin tipo</option>

                        <template x-for="type in entityTypes" :key="type.id">
                            <option :value="type.id" x-text="type.name"></option>
                        </template>
                    </select>
                </div>

                <div>
                    <p class="text-[9px] font-black uppercase tracking-wider text-slate-500">Si el nombre ya existe</p>

                    <input type="hidden" name="duplicate_strategy" :value="duplicateStrategy">

                    <div class="mt-1.5 grid grid-cols-2 gap-1.5">
                        @foreach ([['create', 'Crearla igual', 'Aunque se repita el nombre.'], ['skip', 'Saltarla', 'No la crea y sigue con las demás.']] as [$valor, $titulo, $texto])
                            <button type="button" @click="duplicateStrategy = '{{ $valor }}'"
                                :class="duplicateStrategy === '{{ $valor }}' ?
                                    'border-rose-500/50 bg-rose-500/10' :
                                    'border-slate-800 bg-slate-950 hover:border-slate-700'"
                                class="rounded-xl border p-2 text-left transition">
                                <span class="block text-[11px] font-black text-white">{{ $titulo }}</span>
                                <span class="mt-0.5 block text-[9px] leading-3 text-slate-500">{{ $texto }}</span>
                            </button>
                        @endforeach
                    </div>
                </div>

                <div>
                    <p class="text-[9px] font-black uppercase tracking-wider text-slate-500">Estado</p>

                    <input type="hidden" name="status" :value="status">

                    <div class="mt-1.5 flex flex-wrap gap-1.5">
                        @foreach ([['ACTIVE', 'Activas'], ['INACTIVE', 'Inactivas'], ['ARCHIVED', 'Archivadas']] as [$valor, $titulo])
                            <button type="button" @click="status = '{{ $valor }}'"
                                :class="status === '{{ $valor }}' ?
                                    'border-emerald-500/50 bg-emerald-500/10 text-emerald-300' :
                                    'border-slate-800 bg-slate-950 text-slate-400 hover:border-slate-700'"
                                class="rounded-lg border px-2.5 py-1.5 text-[10px] font-black transition">
                                {{ $titulo }}
                            </button>
                        @endforeach
                    </div>
                </div>

                <div>
                    <p class="text-[9px] font-black uppercase tracking-wider text-slate-500">Visibilidad</p>

                    <input type="hidden" name="visibility" :value="visibility">

                    <div class="mt-1.5 flex flex-wrap gap-1.5">
                        @foreach ([['PRIVATE', 'Privadas'], ['PUBLIC', 'Públicas'], ['UNLISTED', 'No listadas']] as [$valor, $titulo])
                            <button type="button" @click="visibility = '{{ $valor }}'"
                                :class="visibility === '{{ $valor }}' ?
                                    'border-sky-500/50 bg-sky-500/10 text-sky-300' :
                                    'border-slate-800 bg-slate-950 text-slate-400 hover:border-slate-700'"
                                class="rounded-lg border px-2.5 py-1.5 text-[10px] font-black transition">
                                {{ $titulo }}
                            </button>
                        @endforeach
                    </div>
                </div>

                <label
                    class="flex cursor-pointer items-start gap-3 rounded-xl border border-slate-800 bg-slate-950 p-3 transition hover:border-sky-500/40 sm:col-span-2">
                    <input type="hidden" name="allow_cloning" value="0">
                    <input type="checkbox" name="allow_cloning" value="1" x-model="allowCloning"
                        class="mt-0.5 rounded border-slate-700 bg-slate-900 text-sky-500 focus:ring-sky-500">
                    <span>
                        <span class="block text-xs font-black text-white">Permitir que las copien</span>
                        <span class="mt-0.5 block text-[10px] leading-4 text-slate-500">
                            Solo tiene efecto en las que sean públicas.
                        </span>
                    </span>
                </label>

            </div>

        </section>


        {{-- ===================================================== --}}
        {{-- 02 · COLECCIONES --}}
        {{-- ===================================================== --}}

        <section class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/50">

            <header class="flex items-center gap-3 border-b border-slate-800 px-5 py-3">
                <span
                    class="flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-500/15 text-[11px] font-black text-emerald-300">
                    02
                </span>
                <div class="min-w-0 flex-1">
                    <h2 class="text-sm font-black text-white">Dónde van a vivir</h2>
                    <p class="text-[10px] text-slate-500">Todas entrarán en las colecciones que marques.</p>
                </div>

                <span class="shrink-0 rounded-lg bg-emerald-500/15 px-2 py-1 font-mono text-[11px] font-black text-emerald-300"
                    x-text="collectionIds.length"></span>
            </header>

            <div class="p-5">

                <template x-if="collections.length === 0">
                    <div class="rounded-xl border border-dashed border-slate-800 px-4 py-6 text-center">
                        <p class="text-[11px] text-slate-500">
                            Todavía no tienes colecciones. El lote puede crearse sin ninguna.
                        </p>
                    </div>
                </template>

                <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                    <template x-for="collection in collections" :key="collection.id">
                        <label
                            :class="collectionIds.includes(collection.id) ?
                                'border-emerald-500/50 bg-emerald-500/10' :
                                'border-slate-800 bg-slate-950 hover:border-slate-700'"
                            class="flex cursor-pointer items-center gap-2.5 rounded-xl border p-2.5 transition">

                            <input type="checkbox" name="collection_ids[]" :value="collection.id"
                                x-model="collectionIds" class="sr-only">

                            <span class="h-9 w-9 shrink-0 overflow-hidden rounded-lg bg-slate-900">
                                <template x-if="collection.image_url">
                                    <img :src="collection.image_url" alt="" class="h-full w-full object-cover">
                                </template>

                                <template x-if="!collection.image_url">
                                    <span class="flex h-full w-full items-center justify-center text-sm text-slate-600"
                                        x-text="collection.icon"></span>
                                </template>
                            </span>

                            <span class="min-w-0 flex-1">
                                <span class="block truncate text-[11px] font-black text-white"
                                    x-text="collection.name"></span>
                                <span class="block text-[9px] text-slate-500">
                                    <span x-text="collection.entities_count"></span> entidades
                                </span>
                            </span>

                            <span
                                class="flex h-5 w-5 shrink-0 items-center justify-center rounded-md border text-[11px] font-black transition"
                                :class="collectionIds.includes(collection.id) ?
                                    'border-emerald-500 bg-emerald-500 text-slate-950' :
                                    'border-slate-700 text-transparent'">✓</span>
                        </label>
                    </template>
                </div>

            </div>

        </section>


        {{-- ===================================================== --}}
        {{-- 03 · CARACTERÍSTICAS --}}
        {{-- ===================================================== --}}

        <section class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/50">

            <header class="flex items-center gap-3 border-b border-slate-800 px-5 py-3">
                <span
                    class="flex h-8 w-8 items-center justify-center rounded-lg bg-violet-500/15 text-[11px] font-black text-violet-300">
                    03
                </span>
                <div class="min-w-0 flex-1">
                    <h2 class="text-sm font-black text-white">Qué características tendrán</h2>
                    <p class="text-[10px] text-slate-500">
                        Y cuáles valen lo mismo para todas: esas se rellenan una vez, no doscientas.
                    </p>
                </div>

                <span class="shrink-0 rounded-lg bg-violet-500/15 px-2 py-1 font-mono text-[11px] font-black text-violet-300"
                    x-text="selectedAttributeIds.length"></span>
            </header>

            <div class="space-y-4 p-5">

                {{-- Buscar y elegir --}}
                <div>
                    <label class="relative block">
                        <span class="sr-only">Buscar característica</span>

                        <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-slate-600">
                            <x-omni-icon name="brujula" size="h-3.5 w-3.5" />
                        </span>

                        <input type="search" x-model="attributeSearch"
                            placeholder="Buscar entre tus características..."
                            class="w-full rounded-xl border-slate-800 bg-slate-950 pl-9 text-xs text-slate-200 placeholder:text-slate-700 focus:border-violet-500 focus:ring-violet-500">
                    </label>

                    <div class="mt-2 max-h-56 space-y-1 overflow-y-auto pr-1">
                        <template x-for="attribute in filteredAttributes" :key="attribute.id">
                            <button type="button" @click="toggleAttribute(attribute)"
                                :class="isAttributeSelected(attribute.id) ?
                                    'border-violet-500/50 bg-violet-500/10' :
                                    'border-slate-800 bg-slate-950 hover:border-slate-700'"
                                class="flex w-full items-center gap-2.5 rounded-xl border p-2 text-left transition">

                                <span class="h-8 w-8 shrink-0 overflow-hidden rounded-lg bg-slate-900">
                                    <template x-if="attribute.image_url">
                                        <img :src="attribute.image_url" alt="" class="h-full w-full object-cover">
                                    </template>

                                    <template x-if="!attribute.image_url">
                                        <span class="flex h-full w-full items-center justify-center text-sm"
                                            :style="'color: ' + attribute.color" x-text="attribute.icon"></span>
                                    </template>
                                </span>

                                <span class="min-w-0 flex-1">
                                    <span class="block truncate text-[11px] font-black text-white"
                                        x-text="attribute.name"></span>
                                    <span class="block truncate text-[9px] text-slate-500"
                                        x-text="attribute.data_type_label + (attribute.allows_multiple ? ' · varios valores' : '')"></span>
                                </span>

                                <span
                                    class="flex h-5 w-5 shrink-0 items-center justify-center rounded-md border text-[11px] font-black transition"
                                    :class="isAttributeSelected(attribute.id) ?
                                        'border-violet-500 bg-violet-500 text-white' :
                                        'border-slate-700 text-transparent'">✓</span>
                            </button>
                        </template>

                        <template x-if="filteredAttributes.length === 0">
                            <p class="px-2 py-4 text-center text-[11px] text-slate-600">
                                Ninguna característica encaja con esa búsqueda.
                            </p>
                        </template>
                    </div>
                </div>

                {{-- Los campos que se envían --}}
                <template x-for="id in selectedAttributeIds" :key="`sel-${id}`">
                    <input type="hidden" name="selected_attribute_ids[]" :value="id">
                </template>

                <template x-for="id in commonAttributeIds" :key="`com-${id}`">
                    <input type="hidden" name="common_attribute_ids[]" :value="id">
                </template>

                {{-- Las elegidas, con su conmutador --}}
                <div x-show="selectedAttributes.length" x-cloak>
                    <p class="text-[9px] font-black uppercase tracking-wider text-slate-500">
                        Elegidas · ¿igual para todas o distinta en cada fila?
                    </p>

                    <div class="mt-2 space-y-1.5">
                        <template x-for="attribute in selectedAttributes" :key="`chosen-${attribute.id}`">
                            <div class="flex flex-wrap items-center gap-2 rounded-xl border border-slate-800 bg-slate-950 p-2">

                                <span class="h-8 w-8 shrink-0 overflow-hidden rounded-lg bg-slate-900">
                                    <template x-if="attribute.image_url">
                                        <img :src="attribute.image_url" alt="" class="h-full w-full object-cover">
                                    </template>

                                    <template x-if="!attribute.image_url">
                                        <span class="flex h-full w-full items-center justify-center text-sm"
                                            :style="'color: ' + attribute.color" x-text="attribute.icon"></span>
                                    </template>
                                </span>

                                <span class="min-w-0 flex-1">
                                    <span class="block truncate text-[11px] font-black text-white"
                                        x-text="attribute.name"></span>
                                    <span class="block text-[9px] text-slate-600" x-text="attribute.data_type_label"></span>
                                </span>

                                <span class="flex shrink-0 items-center gap-1 rounded-lg border border-slate-800 bg-slate-900 p-0.5">
                                    <button type="button" @click="setAttributeMode(attribute.id, 'common')"
                                        :class="isCommon(attribute.id) ?
                                            'bg-violet-500 text-white' :
                                            'text-slate-500 hover:text-slate-200'"
                                        class="rounded-md px-2 py-1 text-[9px] font-black transition">
                                        Igual para todas
                                    </button>

                                    <button type="button" @click="setAttributeMode(attribute.id, 'row')"
                                        :class="!isCommon(attribute.id) ?
                                            'bg-cyan-500 text-slate-950' :
                                            'text-slate-500 hover:text-slate-200'"
                                        class="rounded-md px-2 py-1 text-[9px] font-black transition">
                                        Una por fila
                                    </button>
                                </span>

                                <button type="button" @click="removeAttribute(attribute.id)" title="Quitarla del lote"
                                    class="shrink-0 rounded-lg px-2 py-1 text-[11px] font-black text-slate-600 transition hover:text-rose-300">
                                    ×
                                </button>
                            </div>
                        </template>
                    </div>
                </div>

                <p x-show="!selectedAttributes.length" class="text-[11px] leading-4 text-slate-600">
                    Sin características elegidas el lote solo llevará nombre, descripción, tipo e
                    imagen. Se les pueden poner después, una a una o desde la gestión masiva.
                </p>

            </div>

        </section>


        {{-- ===================================================== --}}
        {{-- 04 · VALORES COMUNES --}}
        {{-- ===================================================== --}}

        <section x-show="commonAttributes.length" x-cloak
            class="overflow-hidden rounded-2xl border border-violet-500/30 bg-violet-500/5">

            <header class="flex items-center gap-3 border-b border-violet-500/20 px-5 py-3">
                <span
                    class="flex h-8 w-8 items-center justify-center rounded-lg bg-violet-500/20 text-[11px] font-black text-violet-300">
                    04
                </span>
                <div>
                    <h2 class="text-sm font-black text-white">El valor que comparten</h2>
                    <p class="text-[10px] text-slate-500">
                        Se escribe una vez y va a las <span x-text="rows.length"></span> filas.
                    </p>
                </div>
            </header>

            <div class="grid gap-3 p-5 sm:grid-cols-2 lg:grid-cols-3">
                <template x-for="attribute in commonAttributes" :key="`common-${attribute.id}`">
                    <div class="rounded-xl border border-slate-800 bg-slate-950 p-2.5">

                        <p class="flex items-center gap-2">
                            <span class="text-[13px]" :style="'color: ' + attribute.color" x-text="attribute.icon"></span>
                            <span class="min-w-0 flex-1 truncate text-[11px] font-black text-slate-300"
                                x-text="attribute.name"></span>
                        </p>

                        <div class="mt-1.5">
                            @include('entities.bulk.partials.value-editor', [
                                'model' => 'commonValues[attribute.id]',
                                'name' => '`common_attributes[${attribute.id}]`',
                            ])
                        </div>

                    </div>
                </template>
            </div>

        </section>


        {{-- ===================================================== --}}
        {{-- 05 · LAS ENTIDADES --}}
        {{-- ===================================================== --}}

        <section class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/50">

            <header class="flex flex-wrap items-center gap-3 border-b border-slate-800 px-5 py-3">
                <span
                    class="flex h-8 w-8 items-center justify-center rounded-lg bg-cyan-500/15 text-[11px] font-black text-cyan-300">
                    05
                </span>
                <div class="min-w-0 flex-1">
                    <h2 class="text-sm font-black text-white">Las entidades del lote</h2>
                    <p class="text-[10px] text-slate-500">
                        Lo que cambia de una a otra: su nombre, su cara y lo que hayas marcado como
                        «una por fila».
                    </p>
                </div>

                {{-- Dos maneras de mirarlas --}}
                <span class="flex shrink-0 items-center gap-1 rounded-xl border border-slate-800 bg-slate-950 p-1">
                    @foreach ([['table', 'grafo', 'Tabla: para escribir rápido'], ['cards', 'galeria', 'Fichas: para ver las caras']] as [$modo, $icono, $ayuda])
                        <button type="button" @click="rowView = '{{ $modo }}'" title="{{ $ayuda }}"
                            :aria-pressed="rowView === '{{ $modo }}'"
                            :class="rowView === '{{ $modo }}' ? 'bg-cyan-500 text-slate-950' :
                                'text-slate-500 hover:text-slate-200'"
                            class="rounded-lg px-2 py-1.5 transition">
                            <x-omni-icon :name="$icono" size="h-4 w-4" />
                        </button>
                    @endforeach
                </span>
            </header>


            {{-- ============ LA BARRA DE ACCIONES ============ --}}

            <div class="flex flex-wrap items-center gap-2 border-b border-slate-800 bg-slate-950/60 px-4 py-2.5">

                <button type="button" @click="toggleSelectAll()"
                    class="rounded-lg border border-slate-800 bg-slate-900 px-2.5 py-1.5 text-[10px] font-black text-slate-300 transition hover:border-slate-600">
                    Marcar todas
                </button>

                <span class="rounded-lg bg-slate-900 px-2 py-1.5 font-mono text-[10px] font-black text-cyan-300">
                    <span x-text="selectedRowsCount"></span> marcadas
                </span>

                <button type="button" @click="duplicateSelected()" :disabled="!selectedRowsCount"
                    class="rounded-lg border border-slate-800 bg-slate-900 px-2.5 py-1.5 text-[10px] font-black text-slate-300 transition hover:border-indigo-500 hover:text-indigo-300 disabled:opacity-30">
                    ⧉ Duplicarlas
                </button>

                <button type="button" @click="removeSelected()" :disabled="!selectedRowsCount"
                    class="rounded-lg border border-slate-800 bg-slate-900 px-2.5 py-1.5 text-[10px] font-black text-slate-300 transition hover:border-rose-500 hover:text-rose-300 disabled:opacity-30">
                    × Quitarlas
                </button>

                {{--
                    Rellenar una característica en todas las marcadas de golpe.
                    Es lo que convierte «doscientas filas» en «un minuto».
                --}}
                <span class="ml-auto flex flex-wrap items-center gap-1.5 rounded-xl border border-cyan-500/25 bg-cyan-500/5 px-2 py-1.5">
                    <span class="text-[9px] font-black uppercase tracking-wider text-cyan-300">
                        A las marcadas
                    </span>

                    <select x-model="bulkAttributeId" @change="resetBulkValue()"
                        class="rounded-lg border-slate-800 bg-slate-900 py-1 text-[10px] font-bold text-slate-300 focus:border-cyan-500 focus:ring-cyan-500">
                        <option value="">elegir característica…</option>

                        <template x-for="attribute in individualAttributes" :key="`bulk-${attribute.id}`">
                            <option :value="attribute.id" x-text="attribute.name"></option>
                        </template>
                    </select>

                    <template x-if="bulkAttribute">
                        <span class="w-40" x-data="{ get attribute() { return bulkAttribute } }">
                            @include('entities.bulk.partials.value-editor', [
                                'model' => 'bulkValue',
                                'name' => null,
                                'sinValor' => 'Sin valor',
                            ])
                        </span>
                    </template>

                    <button type="button" @click="applyBulkValue()" :disabled="!bulkAttributeId || !selectedRowsCount"
                        class="rounded-lg bg-cyan-500 px-2.5 py-1 text-[10px] font-black text-slate-950 transition hover:bg-cyan-400 disabled:opacity-30">
                        Aplicar
                    </button>
                </span>

            </div>


            {{-- ============ SIN FILAS ============ --}}

            <template x-if="rows.length === 0">
                <div class="px-5 py-12 text-center">
                    <span class="inline-flex text-slate-700">
                        <x-omni-icon name="capas" size="h-10 w-10" />
                    </span>

                    <p class="mt-2 text-sm font-black text-white">El lote está vacío</p>

                    <p class="mx-auto mt-1 max-w-sm text-[11px] leading-relaxed text-slate-500">
                        Usa cualquiera de las cuatro formas de arriba. La más rápida para muchas es
                        pegar los nombres desde una hoja de cálculo.
                    </p>

                    <div class="mt-4 flex flex-wrap justify-center gap-2">
                        <button type="button" @click="addRows(5)"
                            class="rounded-xl border border-slate-700 px-4 py-2 text-[11px] font-black text-slate-300 transition hover:border-indigo-500 hover:text-indigo-300">
                            + 5 filas vacías
                        </button>

                        <button type="button" @click="pasteOpen = true"
                            class="rounded-xl bg-emerald-500 px-4 py-2 text-[11px] font-black text-slate-950 transition hover:bg-emerald-400">
                            Pegar de una hoja
                        </button>
                    </div>
                </div>
            </template>


            {{-- ============ TABLA ============ --}}

            <div x-show="rows.length && rowView === 'table'" class="overflow-x-auto">

                <table class="w-full min-w-[900px]">

                    <thead class="border-b border-slate-800 bg-slate-950/40 text-left">
                        <tr class="text-[9px] font-black uppercase tracking-wider text-slate-600">
                            <th class="w-10 px-3 py-2.5">#</th>
                            <th class="w-16 px-3 py-2.5">Cara</th>
                            <th class="px-3 py-2.5">Nombre</th>
                            <th class="px-3 py-2.5">Descripción</th>
                            <th class="px-3 py-2.5">Tipo</th>

                            <template x-for="attribute in individualAttributes" :key="`th-${attribute.id}`">
                                <th class="px-3 py-2.5">
                                    <span class="flex items-center gap-1">
                                        <span :style="'color: ' + attribute.color" x-text="attribute.icon"></span>
                                        <span x-text="attribute.name"></span>
                                    </span>
                                </th>
                            </template>

                            <th class="w-10 px-3 py-2.5"></th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-800/70">
                        <template x-for="(row, index) in rows" :key="row.key">
                            <tr :class="row.selected ? 'bg-cyan-500/5' : ''" class="transition hover:bg-slate-900/60">

                                {{-- Número y marca --}}
                                <td class="px-3 py-2 align-top">
                                    <label class="flex cursor-pointer items-center gap-1.5">
                                        <input type="checkbox" x-model="row.selected"
                                            class="rounded border-slate-700 bg-slate-900 text-cyan-500 focus:ring-cyan-500">
                                        <span class="font-mono text-[10px] text-slate-600" x-text="index + 1"></span>
                                    </label>
                                </td>

                                {{-- La cara --}}
                                <td class="px-3 py-2 align-top">
                                    <label class="group relative block h-12 w-12 cursor-pointer overflow-hidden rounded-lg border border-slate-800 bg-slate-950">

                                        <template x-if="row.imagePreview || row.bulkImagePreview">
                                            <img :src="row.imagePreview || row.bulkImagePreview" alt=""
                                                class="h-full w-full object-cover">
                                        </template>

                                        <template x-if="!row.imagePreview && !row.bulkImagePreview">
                                            <span class="flex h-full w-full items-center justify-center text-slate-700">
                                                <x-omni-icon name="galeria" size="h-4 w-4" />
                                            </span>
                                        </template>

                                        <span
                                            class="absolute inset-0 flex items-center justify-center bg-slate-950/80 text-[9px] font-black text-indigo-300 opacity-0 transition group-hover:opacity-100">
                                            Cambiar
                                        </span>

                                        <input type="file"
                                            accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                                            :name="`images[${row.key}]`" class="hidden"
                                            @change="previewIndividualImage($event, row)">
                                    </label>

                                    {{-- De dónde salió la imagen, si vino en masa --}}
                                    <p x-show="row.bulkImageName && !row.imagePreview" x-cloak
                                        class="mt-1 max-w-[3rem] truncate font-mono text-[8px] text-amber-400"
                                        :title="row.bulkImageName" x-text="row.bulkImageName"></p>
                                </td>

                                {{-- Nombre --}}
                                <td class="px-3 py-2 align-top">
                                    <input type="text" x-model="row.name" :name="`rows[${row.key}][name]`"
                                        placeholder="Nombre de la entidad" maxlength="255"
                                        :class="isExistingName(row.name) ? 'border-rose-500/60' : 'border-slate-800'"
                                        class="w-full min-w-[150px] rounded-lg bg-slate-950 text-[11px] font-bold text-white placeholder:text-slate-700 focus:border-indigo-500 focus:ring-indigo-500">

                                    <p x-show="isExistingName(row.name)" x-cloak
                                        class="mt-1 text-[9px] font-bold text-rose-300">
                                        Ya tienes una con ese nombre
                                    </p>

                                    <template x-for="aviso in row.importWarnings" :key="aviso">
                                        <p class="mt-1 text-[9px] text-amber-400" x-text="aviso"></p>
                                    </template>
                                </td>

                                {{-- Descripción --}}
                                <td class="px-3 py-2 align-top">
                                    <textarea x-model="row.description" :name="`rows[${row.key}][description]`" rows="1"
                                        placeholder="Opcional"
                                        class="w-full min-w-[160px] rounded-lg border-slate-800 bg-slate-950 text-[11px] text-slate-300 placeholder:text-slate-700 focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                                </td>

                                {{-- Tipo --}}
                                <td class="px-3 py-2 align-top">
                                    <select x-model="row.entity_type_id" :name="`rows[${row.key}][entity_type_id]`"
                                        class="w-full min-w-[110px] rounded-lg border-slate-800 bg-slate-950 text-[11px] text-slate-300 focus:border-indigo-500 focus:ring-indigo-500">
                                        <option value="">Usar el del lote</option>

                                        <template x-for="type in entityTypes" :key="type.id">
                                            <option :value="type.id" x-text="type.name"></option>
                                        </template>
                                    </select>
                                </td>

                                {{-- Una celda por característica individual --}}
                                <template x-for="attribute in individualAttributes" :key="`cell-${row.key}-${attribute.id}`">
                                    <td class="px-3 py-2 align-top">
                                        <span class="block min-w-[130px]">
                                            @include('entities.bulk.partials.value-editor', [
                                                'model' => 'row.attributes[attribute.id]',
                                                'name' => '`rows[${row.key}][attributes][${attribute.id}]`',
                                            ])
                                        </span>

                                        {{-- Copiar este valor hacia abajo: el gesto de hoja de cálculo --}}
                                        <button type="button" @click="copyDown(row, attribute)"
                                            title="Copiar este valor a las filas de abajo"
                                            class="mt-1 text-[9px] font-black text-slate-700 transition hover:text-cyan-300">
                                            ↓ copiar abajo
                                        </button>
                                    </td>
                                </template>

                                {{-- Quitarla --}}
                                <td class="px-3 py-2 align-top text-right">
                                    <button type="button" @click="removeRow(row)" title="Quitar esta fila"
                                        class="rounded-lg px-2 py-1 text-[12px] font-black text-slate-700 transition hover:text-rose-300">
                                        ×
                                    </button>
                                </td>

                            </tr>
                        </template>
                    </tbody>

                </table>

            </div>


            {{-- ============ FICHAS ============ --}}

            {{--
                El mismo lote, pero enseñando la cara grande. Sirve para lo que
                la tabla no deja hacer: comprobar de un vistazo que cada imagen
                cayó en la entidad correcta después de soltarlas en masa.
            --}}

            <div x-show="rows.length && rowView === 'cards'" x-cloak
                class="grid gap-2 p-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">

                <template x-for="(row, index) in rows" :key="`card-${row.key}`">
                    <div :class="row.selected ? 'border-cyan-500/50 bg-cyan-500/5' : 'border-slate-800 bg-slate-950/60'"
                        class="overflow-hidden rounded-xl border transition">

                        <label class="group relative block aspect-[4/3] cursor-pointer overflow-hidden bg-slate-950">

                            <template x-if="row.imagePreview || row.bulkImagePreview">
                                <img :src="row.imagePreview || row.bulkImagePreview" alt=""
                                    class="h-full w-full object-cover">
                            </template>

                            <template x-if="!row.imagePreview && !row.bulkImagePreview">
                                <span class="flex h-full w-full flex-col items-center justify-center gap-1 text-slate-700">
                                    <x-omni-icon name="galeria" size="h-6 w-6" />
                                    <span class="text-[9px] font-bold">Sin imagen</span>
                                </span>
                            </template>

                            <span
                                class="absolute inset-0 flex items-center justify-center bg-slate-950/80 text-[10px] font-black text-indigo-300 opacity-0 transition group-hover:opacity-100">
                                Elegir imagen
                            </span>

                            <span class="absolute left-1.5 top-1.5 rounded bg-slate-950/85 px-1.5 py-0.5 font-mono text-[9px] font-black text-slate-400 backdrop-blur"
                                x-text="index + 1"></span>

                            <input type="file" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                                class="hidden" @change="previewIndividualImage($event, row)">
                        </label>

                        <div class="p-2">
                            <div class="flex items-center gap-1.5">
                                <input type="checkbox" x-model="row.selected"
                                    class="rounded border-slate-700 bg-slate-900 text-cyan-500 focus:ring-cyan-500">

                                <input type="text" x-model="row.name" placeholder="Nombre"
                                    :class="isExistingName(row.name) ? 'border-rose-500/60' : 'border-slate-800'"
                                    class="min-w-0 flex-1 rounded-lg bg-slate-950 text-[11px] font-bold text-white placeholder:text-slate-700 focus:border-indigo-500 focus:ring-indigo-500">

                                <button type="button" @click="removeRow(row)"
                                    class="rounded-lg px-1.5 py-1 text-[12px] font-black text-slate-700 transition hover:text-rose-300">
                                    ×
                                </button>
                            </div>

                            <p x-show="isExistingName(row.name)" x-cloak
                                class="mt-1 text-[9px] font-bold text-rose-300">
                                Ya existe una con ese nombre
                            </p>

                            <p x-show="row.bulkImageName" x-cloak
                                class="mt-1 truncate font-mono text-[8px] text-amber-400" x-text="row.bulkImageName"></p>
                        </div>

                    </div>
                </template>

            </div>


            {{-- Añadir más, desde el final de la lista --}}
            <div x-show="rows.length" class="flex flex-wrap items-center gap-2 border-t border-slate-800 px-4 py-2.5">
                <span class="text-[10px] font-black uppercase tracking-wider text-slate-600">Añadir</span>

                @foreach ([1, 5, 10] as $cuantas)
                    <button type="button" @click="addRows({{ $cuantas }})"
                        class="rounded-lg border border-slate-800 bg-slate-950 px-2.5 py-1 text-[10px] font-black text-slate-300 transition hover:border-indigo-500 hover:text-indigo-300">
                        +{{ $cuantas }}
                    </button>
                @endforeach

                <button type="button" @click="pasteOpen = true"
                    class="ml-auto rounded-lg border border-emerald-500/30 px-2.5 py-1 text-[10px] font-black text-emerald-300 transition hover:bg-emerald-500/10">
                    Pegar más filas
                </button>
            </div>

        </section>

    </div>


    {{-- ============================================================= --}}
    {{-- EL RESUMEN, SIEMPRE A LA VISTA --}}
    {{-- ============================================================= --}}

    <aside class="space-y-4 xl:sticky xl:top-4">

        <section class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/50">

            <header class="border-b border-slate-800 px-4 py-3">
                <p class="text-[9px] font-black uppercase tracking-wider text-slate-500">Antes de crear</p>
            </header>

            <div class="space-y-3 p-4">

                {{-- Cuántas están listas --}}
                <div>
                    <div class="flex items-baseline justify-between">
                        <span class="text-[11px] font-bold text-slate-400">Listas para crear</span>
                        <span class="font-mono text-[13px] font-black text-emerald-300">
                            <span x-text="readyCount"></span><span class="text-slate-700">/</span><span
                                x-text="rows.length"></span>
                        </span>
                    </div>

                    <div class="mt-1.5 h-1.5 overflow-hidden rounded-full bg-slate-950">
                        <div class="h-full rounded-full bg-emerald-400 transition-all"
                            :style="'width: ' + (rows.length ? Math.round(readyCount / rows.length * 100) : 0) + '%'">
                        </div>
                    </div>

                    <p class="mt-1 text-[10px] text-slate-600">
                        Una fila está lista cuando tiene nombre.
                    </p>
                </div>

                {{-- Lo que conviene mirar antes --}}
                <div class="space-y-1.5 border-t border-slate-800 pt-3">

                    <template x-if="existingNameCount > 0">
                        <p class="flex items-start gap-2 rounded-lg border border-rose-500/30 bg-rose-500/5 p-2 text-[10px] leading-4 text-slate-400">
                            <span class="mt-0.5 h-1.5 w-1.5 shrink-0 rounded-full bg-rose-400"></span>
                            <span>
                                <strong class="text-rose-300"><span x-text="existingNameCount"></span></strong>
                                con un nombre que ya tienes.
                                <span x-show="duplicateStrategy === 'skip'">Se saltarán.</span>
                                <span x-show="duplicateStrategy === 'create'">Se crearán igual.</span>
                            </span>
                        </p>
                    </template>

                    <template x-if="rowsWithoutImage > 0">
                        <p class="flex items-start gap-2 rounded-lg border border-amber-500/30 bg-amber-500/5 p-2 text-[10px] leading-4 text-slate-400">
                            <span class="mt-0.5 h-1.5 w-1.5 shrink-0 rounded-full bg-amber-400"></span>
                            <span>
                                <strong class="text-amber-300"><span x-text="rowsWithoutImage"></span></strong>
                                sin imagen. Se pueden poner después, una a una.
                            </span>
                        </p>
                    </template>

                    <template x-if="importWarningCount > 0">
                        <p class="flex items-start gap-2 rounded-lg border border-sky-500/30 bg-sky-500/5 p-2 text-[10px] leading-4 text-slate-400">
                            <span class="mt-0.5 h-1.5 w-1.5 shrink-0 rounded-full bg-sky-400"></span>
                            <span>
                                <strong class="text-sky-300"><span x-text="importWarningCount"></span></strong>
                                avisos de la importación. Están marcados en su fila.
                            </span>
                        </p>
                    </template>

                    <template x-if="!existingNameCount && !rowsWithoutImage && !importWarningCount && rows.length">
                        <p class="flex items-start gap-2 rounded-lg border border-emerald-500/30 bg-emerald-500/5 p-2 text-[10px] leading-4 text-slate-400">
                            <span class="mt-0.5 h-1.5 w-1.5 shrink-0 rounded-full bg-emerald-400"></span>
                            <span>Nada que revisar. Todo listo.</span>
                        </p>
                    </template>

                </div>

                {{-- Qué se va a crear, en una frase --}}
                <div class="space-y-1.5 border-t border-slate-800 pt-3 text-[11px]">
                    @foreach ([['Tipo', 'entityTypes.find(t => t.id === entityTypeId)?.name ?? "Sin tipo"'], ['Estado', 'status === "ACTIVE" ? "Activas" : (status === "INACTIVE" ? "Inactivas" : "Archivadas")'], ['Visibilidad', 'visibility === "PUBLIC" ? "Públicas" : (visibility === "PRIVATE" ? "Privadas" : "No listadas")'], ['Colecciones', 'collectionIds.length'], ['Características', 'selectedAttributeIds.length']] as [$etiqueta, $expresion])
                        <div class="flex items-baseline justify-between gap-3">
                            <dt class="text-slate-600">{{ $etiqueta }}</dt>
                            <dd class="truncate text-right font-bold text-slate-200" x-text="{{ $expresion }}"></dd>
                        </div>
                    @endforeach
                </div>

                <button type="submit" :disabled="!readyCount"
                    class="w-full rounded-xl bg-indigo-500 px-4 py-3 text-xs font-black text-white transition hover:bg-indigo-400 disabled:cursor-not-allowed disabled:opacity-40">
                    Crear <span x-text="readyCount"></span>
                    <span x-text="readyCount === 1 ? 'entidad' : 'entidades'"></span>
                </button>

            </div>

        </section>


        {{-- El borrador: esta pantalla se llena en varios ratos --}}

        <section class="rounded-2xl border border-slate-800 bg-slate-900/50 p-4">

            <p class="text-[9px] font-black uppercase tracking-wider text-slate-500">Borrador</p>

            <p class="mt-2 text-[10px] leading-4 text-slate-600">
                Mientras lo llenas se guarda solo en este navegador, así que puedes cerrar y
                volver. <strong class="text-slate-500">Al crear el lote se borra</strong>, para
                que el siguiente empiece vacío.
            </p>

            <div class="mt-2.5 flex flex-wrap items-center gap-1.5">
                <button type="button" @click="saveDraft()"
                    class="rounded-lg border border-slate-800 bg-slate-950 px-2.5 py-1.5 text-[10px] font-black text-slate-300 transition hover:border-indigo-500 hover:text-indigo-300">
                    Guardar ahora
                </button>

                <button type="button" @click="clearDraft()"
                    class="rounded-lg border border-slate-800 bg-slate-950 px-2.5 py-1.5 text-[10px] font-black text-slate-500 transition hover:border-rose-500 hover:text-rose-300">
                    Descartar
                </button>

                <span x-show="draftSavedAt" x-cloak class="text-[9px] font-bold text-emerald-400">
                    ✓ guardado
                </span>
            </div>

        </section>


        <a href="{{ route('entities.create') }}"
            class="block rounded-2xl border border-slate-800 bg-slate-900/50 p-4 transition hover:border-indigo-500/40">
            <p class="text-[9px] font-black uppercase tracking-wider text-slate-500">¿Solo una?</p>
            <p class="mt-1.5 text-[11px] leading-4 text-slate-400">
                Para una entidad suelta, con todas sus opciones, está la pantalla de siempre. →
            </p>
        </a>

    </aside>

</div>


{{-- ===================================================== --}}
{{-- PEGAR DESDE UNA HOJA --}}
{{-- ===================================================== --}}

<div x-show="pasteOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4"
    @keydown.escape.window="pasteOpen = false">

    <div class="absolute inset-0 bg-slate-950/80 backdrop-blur-sm" @click="pasteOpen = false"></div>

    <div class="relative w-full max-w-2xl overflow-hidden rounded-2xl border border-emerald-500/30 bg-slate-900 shadow-2xl">

        <header class="flex items-center gap-3 border-b border-slate-800 px-5 py-3">
            <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-500/15 text-emerald-300">
                <x-omni-icon name="capas" size="h-4 w-4" />
            </span>

            <div class="min-w-0 flex-1">
                <h2 class="text-sm font-black text-white">Pegar desde una hoja de cálculo</h2>
                <p class="text-[10px] text-slate-500">Una línea por entidad. Se puede pegar directamente de Excel.</p>
            </div>

            <button type="button" @click="pasteOpen = false"
                class="shrink-0 rounded-lg px-2 py-1 text-slate-500 transition hover:text-white">
                <x-omni-icon name="cerrar" size="h-4 w-4" />
            </button>
        </header>

        <div class="space-y-3 p-5">

            {{-- El orden de las columnas, dibujado: es lo que más se equivoca --}}
            <div class="overflow-hidden rounded-xl border border-slate-800 bg-slate-950">
                <div class="grid grid-cols-3 border-b border-slate-800 text-[9px] font-black uppercase tracking-wider">
                    <span class="border-r border-slate-800 px-2 py-1.5 text-emerald-300">Nombre *</span>
                    <span class="border-r border-slate-800 px-2 py-1.5 text-slate-500">Descripción</span>
                    <span class="px-2 py-1.5 text-slate-500">Características…</span>
                </div>

                <div class="grid grid-cols-3 font-mono text-[10px] text-slate-400">
                    <span class="border-r border-slate-800 px-2 py-1.5">Naruto Uzumaki</span>
                    <span class="border-r border-slate-800 px-2 py-1.5">Ninja de Konoha</span>
                    <span class="px-2 py-1.5">Hoja</span>
                </div>

                <div class="grid grid-cols-3 border-t border-slate-800 font-mono text-[10px] text-slate-400">
                    <span class="border-r border-slate-800 px-2 py-1.5">Sasuke Uchiha</span>
                    <span class="border-r border-slate-800 px-2 py-1.5">Ninja de Konoha</span>
                    <span class="px-2 py-1.5">Hoja</span>
                </div>
            </div>

            <p class="text-[10px] leading-4 text-slate-500">
                Las columnas después de la descripción se emparejan, en orden, con las
                características que hayas marcado como <strong class="text-cyan-300">«una por
                    fila»</strong>. Lo que no encaje se avisa en su fila, no se descarta en
                silencio.
            </p>

            <textarea x-model="pasteText" rows="8"
                placeholder="Naruto Uzumaki	Ninja de Konoha	Hoja&#10;Sasuke Uchiha	Ninja de Konoha	Hoja"
                class="w-full rounded-xl border-slate-800 bg-slate-950 font-mono text-[11px] text-slate-200 placeholder:text-slate-700 focus:border-emerald-500 focus:ring-emerald-500"></textarea>

            <p class="text-[9px] text-slate-600">
                Separadores admitidos · tabulador, coma o punto y coma · límite 200 filas por lote
            </p>

        </div>

        <div class="flex items-center justify-end gap-2 border-t border-slate-800 px-5 py-3">
            <button type="button" @click="pasteOpen = false"
                class="rounded-xl border border-slate-800 px-4 py-2 text-[11px] font-black text-slate-400 transition hover:border-slate-600 hover:text-slate-200">
                Cancelar
            </button>

            <button type="button" @click="importPasted()" :disabled="!pasteText.trim()"
                class="rounded-xl bg-emerald-500 px-4 py-2 text-[11px] font-black text-slate-950 transition hover:bg-emerald-400 disabled:opacity-40">
                Añadir al lote
            </button>
        </div>

    </div>

</div>
