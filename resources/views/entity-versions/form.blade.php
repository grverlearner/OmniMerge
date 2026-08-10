<x-app-layout>

    <x-slot name="header">
        Versiones de Entidad
    </x-slot>


    @include('entities.partials.section-navigation')


    @php
        $editing = $entityVersion !== null;
    @endphp


    <form method="POST" enctype="multipart/form-data"
        action="{{ $editing
            ? route('entity-versions.update', [$entity, $entityVersion])
            : route('entity-versions.store', $entity) }}"
        class="space-y-6">

        @csrf

        @if ($editing)
            @method('PUT')
        @endif


        {{-- HERO --}}
        <section
            class="
                rounded-3xl
                bg-gradient-to-br
                from-indigo-950
                via-violet-950
                to-fuchsia-950
                p-6
                text-white
                sm:p-8
            ">

            <p
                class="
                    text-[10px]
                    font-black
                    uppercase
                    tracking-wider
                    text-violet-300
                ">
                {{ $previewCode }}
            </p>


            <h1
                class="
                    mt-2
                    text-3xl
                    font-black
                ">
                {{ $editing ? 'Editar versión de ' : 'Nueva versión de ' }}
                {{ $entity->name }}
            </h1>


            <p
                class="
                    mt-3
                    max-w-2xl
                    text-sm
                    text-white/70
                ">
                Esta es la representación concreta de
                {{ $entity->name }}
                dentro de una Versión.
            </p>

        </section>


        @if ($errors->any())

            <div
                class="
                    rounded-2xl
                    border
                    border-red-200
                    bg-red-50
                    p-5
                ">
                <ul
                    class="
                        list-disc
                        space-y-1
                        pl-5
                        text-sm
                        text-red-700
                    ">
                    @foreach ($errors->all() as $error)
                        <li>
                            {{ $error }}
                        </li>
                    @endforeach
                </ul>
            </div>

        @endif


        <section
            class="
                rounded-3xl
                border
                border-slate-200
                bg-white
                p-6
                shadow-sm
            ">

            <div
                class="
                    grid
                    gap-6
                    lg:grid-cols-[300px_minmax(0,1fr)]
                ">

                {{-- IMAGE --}}
                <div>

                    @if ($editing)
                        <img src="{{ $entityVersion->image_url }}"
                            class="
                                aspect-square
                                w-full
                                rounded-3xl
                                object-cover
                            ">
                    @elseif ($entity->image_url)
                        <img src="{{ $entity->image_url }}"
                            class="
                                aspect-square
                                w-full
                                rounded-3xl
                                object-cover
                                opacity-40
                            ">
                    @else
                        <div
                            class="
                                flex
                                aspect-square
                                items-center
                                justify-center
                                rounded-3xl
                                bg-violet-50
                                text-7xl
                                text-violet-200
                            ">
                            ◈
                        </div>
                    @endif


                    <label
                        class="
                            mt-3
                            block
                            text-xs
                            font-black
                            text-slate-700
                        ">
                        Imagen de esta Versión *
                    </label>


                    <input type="file" name="image" accept=".jpg,.jpeg,.png,.webp"
                        {{ $editing ? '' : 'required' }}
                        class="
                            mt-2
                            w-full
                            text-xs
                        ">
                </div>


                <div
                    class="
                        grid
                        content-start
                        gap-4
                        md:grid-cols-2
                    ">

                    <div class="md:col-span-2">
                        <label class="text-xs font-black text-slate-700">
                            Versión general *
                        </label>

                        <select name="version_id" required
                            class="
                                mt-2
                                w-full
                                rounded-xl
                                border-slate-300
                            ">
                            <option value="">
                                Seleccionar...
                            </option>

                            @foreach ($versions as $item)
                                <option value="{{ $item->id }}" @selected((string) old('version_id', $entityVersion?->version_id) === (string) $item->id)>
                                    {{ $item->name }}
                                    ·
                                    {{ $item->kind_label }}
                                    ·
                                    {{ $item->scope_label }}
                                </option>
                            @endforeach
                        </select>
                    </div>


                    <div class="md:col-span-2">
                        <label class="text-xs font-black text-slate-700">
                            Nombre *
                        </label>

                        <input type="text" name="name" required value="{{ old('name', $entityVersion?->name) }}"
                            placeholder="{{ $entity->name }} — Shippuden"
                            class="
                                mt-2
                                w-full
                                rounded-xl
                                border-slate-300
                            ">
                    </div>


                    <div class="md:col-span-2">
                        <label class="text-xs font-black text-slate-700">
                            Descripción
                        </label>

                        <textarea name="description" rows="5"
                            class="
                                mt-2
                                w-full
                                rounded-xl
                                border-slate-300
                            ">{{ old('description', $entityVersion?->description) }}</textarea>
                    </div>


                    <div>
                        <label class="text-xs font-black text-slate-700">
                            Versión concreta padre
                        </label>

                        <select name="parent_entity_version_id"
                            class="
                                mt-2
                                w-full
                                rounded-xl
                                border-slate-300
                            ">
                            <option value="">
                                Ninguna
                            </option>

                            @foreach ($parentEntityVersions as $parent)
                                <option value="{{ $parent->id }}" @selected((string) old('parent_entity_version_id', $entityVersion?->parent_entity_version_id) === (string) $parent->id)>
                                    {{ $parent->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>


                    <div>
                        <label class="text-xs font-black text-slate-700">
                            Estado
                        </label>

                        <select name="status"
                            class="
                                mt-2
                                w-full
                                rounded-xl
                                border-slate-300
                            ">
                            @foreach ([
        'ACTIVE' => 'Activa',
        'INACTIVE' => 'Inactiva',
        'ARCHIVED' => 'Archivada',
    ] as $value => $label)
                                <option value="{{ $value }}" @selected(old('status', $entityVersion?->status ?? 'ACTIVE') === $value)>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>


                    <div>
                        <label class="text-xs font-black text-slate-700">
                            Prioridad
                        </label>

                        <input type="number" name="priority"
                            value="{{ old('priority', $entityVersion?->priority ?? 0) }}"
                            class="
                                mt-2
                                w-full
                                rounded-xl
                                border-slate-300
                            ">
                    </div>


                    <div>
                        <label class="text-xs font-black text-slate-700">
                            Orden
                        </label>

                        <input type="number" min="0" name="sort_order"
                            value="{{ old('sort_order', $entityVersion?->sort_order ?? 0) }}"
                            class="
                                mt-2
                                w-full
                                rounded-xl
                                border-slate-300
                            ">
                    </div>


                    <label
                        class="
                            flex
                            cursor-pointer
                            gap-3
                            rounded-2xl
                            bg-indigo-50
                            p-4
                        ">

                        <input type="hidden" name="inherit_base_attributes" value="0">

                        <input type="checkbox" name="inherit_base_attributes" value="1"
                            @checked(old('inherit_base_attributes', $entityVersion?->inherit_base_attributes ?? true))
                            class="
                                mt-1
                                rounded
                                border-indigo-300
                                text-indigo-600
                            ">

                        <span>
                            <strong
                                class="
                                    block
                                    text-xs
                                    text-indigo-800
                                ">
                                Heredar características
                            </strong>

                            <small
                                class="
                                    mt-1
                                    block
                                    text-[9px]
                                    leading-4
                                    text-indigo-500
                                ">
                                La Versión solamente guardará
                                aquello que cambie.
                            </small>
                        </span>
                    </label>


                    <label
                        class="
                            flex
                            cursor-pointer
                            gap-3
                            rounded-2xl
                            bg-violet-50
                            p-4
                        ">

                        <input type="hidden" name="is_default" value="0">

                        <input type="checkbox" name="is_default" value="1" @checked(old('is_default', $entityVersion?->is_default ?? false))
                            class="
                                mt-1
                                rounded
                                border-violet-300
                                text-violet-600
                            ">

                        <span>
                            <strong
                                class="
                                    block
                                    text-xs
                                    text-violet-800
                                ">
                                Versión predeterminada
                            </strong>

                            <small
                                class="
                                    mt-1
                                    block
                                    text-[9px]
                                    text-violet-500
                                ">
                                Solo puede existir una por Entidad.
                            </small>
                        </span>
                    </label>

                </div>

            </div>

        </section>


        <div class="
                flex
                justify-end
                gap-3
            ">

            <a href="{{ route('entity-versions.index', $entity) }}"
                class="
                    rounded-xl
                    border
                    border-slate-200
                    px-5
                    py-3
                    text-sm
                    font-bold
                    text-slate-600
                ">
                Cancelar
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
                {{ $editing ? 'Guardar cambios' : 'Crear Versión' }}
            </button>

        </div>

    </form>

</x-app-layout>
