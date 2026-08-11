@php

    $currentBase = $activeBaseEntityVersion ?? $entity->baseVersionSetting?->entityVersion;

    $availableBaseVersions = $entity->entityVersions->where('status', 'ACTIVE');

@endphp


<section x-data="{
    open: false
}"
    class="
        mt-6
        overflow-hidden
        rounded-3xl
        border
        border-slate-200
        bg-white
        shadow-sm
    ">

    {{-- ===================================================== --}}
    {{-- CURRENT --}}
    {{-- ===================================================== --}}

    <div
        class="
            flex
            flex-col
            gap-5
            p-5
            sm:p-6
            lg:flex-row
            lg:items-center
            lg:justify-between
        ">

        <div
            class="
                flex
                min-w-0
                items-center
                gap-4
            ">

            <div
                class="
                    relative
                    h-20
                    w-20
                    shrink-0
                    overflow-hidden
                    rounded-2xl
                    bg-gradient-to-br
                    from-violet-100
                    to-indigo-100
                    ring-4
                    ring-violet-50
                ">

                @if ($currentBase?->image_url)
                    <img src="{{ $currentBase->image_url }}" alt="{{ $currentBase->name }}"
                        class="
                            h-full
                            w-full
                            object-cover
                        ">
                @elseif ($entity->image_url)
                    <img src="{{ $entity->image_url }}" alt="{{ $entity->name }}"
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
                            text-3xl
                            text-violet-300
                        ">
                        ✦
                    </div>
                @endif


                <span
                    class="
                        absolute
                        bottom-1
                        right-1
                        flex
                        h-6
                        w-6
                        items-center
                        justify-center
                        rounded-full
                        bg-violet-600
                        text-[10px]
                        font-black
                        text-white
                        shadow
                    ">
                    ★
                </span>

            </div>


            <div class="min-w-0">

                <p
                    class="
                        text-[9px]
                        font-black
                        uppercase
                        tracking-[0.18em]
                        text-violet-500
                    ">
                    Base activa de la Entidad
                </p>


                <h2
                    class="
                        mt-1
                        truncate
                        text-xl
                        font-black
                        text-slate-900
                    ">
                    {{ $currentBase ? $currentBase->name : $entity->name }}
                </h2>


                <div
                    class="
                        mt-2
                        flex
                        flex-wrap
                        gap-2
                    ">

                    @if ($currentBase)
                        <span
                            class="
                                rounded-full
                                bg-violet-100
                                px-2.5
                                py-1
                                text-[8px]
                                font-black
                                text-violet-700
                            ">
                            ★ VERSION COMO BASE
                        </span>


                        <span
                            class="
                                rounded-full
                                bg-slate-100
                                px-2.5
                                py-1
                                text-[8px]
                                font-black
                                text-slate-500
                            ">
                            {{ $currentBase->version?->name }}
                        </span>
                    @else
                        <span
                            class="
                                rounded-full
                                bg-indigo-50
                                px-2.5
                                py-1
                                text-[8px]
                                font-black
                                text-indigo-600
                            ">
                            BASE ORIGINAL
                        </span>
                    @endif

                </div>


                <p
                    class="
                        mt-2
                        max-w-2xl
                        text-[10px]
                        leading-5
                        text-slate-400
                    ">
                    Cambiar la Base activa no elimina ni sobrescribe
                    la Entidad original y tampoco modifica el fallback
                    del Resolver.
                </p>

            </div>

        </div>


        <div
            class="
                flex
                shrink-0
                flex-wrap
                gap-2
            ">

            @if ($currentBase)
                <a href="{{ route('entity-versions.show', [$entity, $currentBase]) }}"
                    class="
                        rounded-xl
                        border
                        border-violet-200
                        bg-violet-50
                        px-4
                        py-2.5
                        text-xs
                        font-black
                        text-violet-700
                    ">
                    Abrir Base
                </a>
            @endif


            <button type="button" @click="
                    open = true
                "
                class="
                    rounded-xl
                    bg-violet-600
                    px-4
                    py-2.5
                    text-xs
                    font-black
                    text-white
                    shadow-lg
                    shadow-violet-600/20
                    transition
                    hover:bg-violet-700
                ">
                ⇄ Cambiar Base
            </button>

        </div>

    </div>


    {{-- ===================================================== --}}
    {{-- MODAL --}}
    {{-- ===================================================== --}}

    <div x-show="open" x-cloak @keydown.escape.window="
            open = false
        "
        class="
            fixed
            inset-0
            z-[100]
            overflow-y-auto
            bg-slate-950/70
            p-4
            backdrop-blur-sm
        ">

        <div
            class="
                flex
                min-h-full
                items-center
                justify-center
            ">

            <div @click.outside="
                    open = false
                "
                class="
                    w-full
                    max-w-5xl
                    overflow-hidden
                    rounded-3xl
                    bg-slate-50
                    shadow-2xl
                ">

                {{-- HEADER --}}
                <header
                    class="
                        flex
                        items-start
                        justify-between
                        gap-5
                        bg-gradient-to-br
                        from-slate-950
                        via-indigo-950
                        to-violet-950
                        p-6
                        text-white
                    ">

                    <div>

                        <p
                            class="
                                text-[9px]
                                font-black
                                uppercase
                                tracking-[0.18em]
                                text-violet-300
                            ">
                            Configuración
                        </p>


                        <h2
                            class="
                                mt-2
                                text-2xl
                                font-black
                            ">
                            Cambiar Base activa
                        </h2>


                        <p
                            class="
                                mt-2
                                max-w-2xl
                                text-xs
                                leading-5
                                text-white/55
                            ">
                            Elige qué representación debe funcionar
                            como la Base principal de {{ $entity->name }}.
                            La Base original siempre seguirá disponible.
                        </p>

                    </div>


                    <button type="button" @click="
                            open = false
                        "
                        class="
                            flex
                            h-10
                            w-10
                            shrink-0
                            items-center
                            justify-center
                            rounded-full
                            bg-white/10
                            text-xl
                            font-black
                            text-white
                            hover:bg-white/20
                        ">
                        ×
                    </button>

                </header>


                {{-- BODY --}}
                <div
                    class="
                        max-h-[72vh]
                        overflow-y-auto
                        p-5
                        sm:p-6
                    ">

                    <div
                        class="
                            grid
                            gap-4
                            sm:grid-cols-2
                            lg:grid-cols-3
                        ">

                        {{-- ================================================= --}}
                        {{-- ORIGINAL --}}
                        {{-- ================================================= --}}

                        <article
                            class="
                                overflow-hidden
                                rounded-3xl
                                border-2
                                bg-white
                                shadow-sm

                                {{ !$currentBase ? 'border-indigo-500 ring-4 ring-indigo-100' : 'border-slate-200' }}
                            ">

                            <div
                                class="
                                    relative
                                    aspect-[4/3]
                                    overflow-hidden
                                    bg-slate-100
                                ">

                                @if ($entity->image_url)
                                    <img src="{{ $entity->image_url }}" alt="{{ $entity->name }}"
                                        class="
                                            h-full
                                            w-full
                                            object-cover
                                        ">
                                @endif


                                <span
                                    class="
                                        absolute
                                        left-3
                                        top-3
                                        rounded-full
                                        bg-indigo-600
                                        px-2.5
                                        py-1
                                        text-[8px]
                                        font-black
                                        text-white
                                    ">
                                    ORIGINAL
                                </span>


                                @if (!$currentBase)
                                    <span
                                        class="
                                            absolute
                                            right-3
                                            top-3
                                            rounded-full
                                            bg-emerald-400
                                            px-2.5
                                            py-1
                                            text-[8px]
                                            font-black
                                            text-emerald-950
                                        ">
                                        ✓ ACTUAL
                                    </span>
                                @endif

                            </div>


                            <div class="p-4">

                                <p
                                    class="
                                        truncate
                                        text-sm
                                        font-black
                                        text-slate-900
                                    ">
                                    {{ $entity->name }}
                                </p>


                                <p
                                    class="
                                        mt-1
                                        text-[9px]
                                        text-slate-400
                                    ">
                                    Información original almacenada
                                    directamente en la Entidad.
                                </p>


                                @if ($currentBase)
                                    <form method="POST" action="{{ route('entities.base-version.destroy', $entity) }}"
                                        data-omni-confirm data-confirm-variant="primary" data-confirm-icon="◇"
                                        data-confirm-title="Volver a la Base original"
                                        data-confirm-message="
                                            La Entidad volverá a utilizar su
                                            representación original como Base.
                                        "
                                        data-confirm-subject="{{ $entity->name }}"
                                        data-confirm-detail="
                                            La Version que utilizas actualmente
                                            no será eliminada. Podrás volver a
                                            seleccionarla cuando quieras.
                                        "
                                        data-confirm-action="Usar Base original"
                                        data-confirm-image="{{ $entity->image_url ?? '' }}">

                                        @csrf
                                        @method('DELETE')


                                        <button type="submit"
                                            class="
                                                mt-4
                                                w-full
                                                rounded-xl
                                                bg-indigo-600
                                                px-4
                                                py-2.5
                                                text-xs
                                                font-black
                                                text-white
                                            ">
                                            Usar Base original
                                        </button>

                                    </form>
                                @else
                                    <div
                                        class="
                                            mt-4
                                            rounded-xl
                                            bg-emerald-50
                                            px-4
                                            py-2.5
                                            text-center
                                            text-xs
                                            font-black
                                            text-emerald-700
                                        ">
                                        ✓ Es la Base actual
                                    </div>
                                @endif

                            </div>

                        </article>


                        {{-- ================================================= --}}
                        {{-- VERSIONES --}}
                        {{-- ================================================= --}}

                        @foreach ($availableBaseVersions as $versionItem)
                            @php

                                $isCurrent = $currentBase && $currentBase->id === $versionItem->id;

                            @endphp


                            <article
                                class="
                                    overflow-hidden
                                    rounded-3xl
                                    border-2
                                    bg-white
                                    shadow-sm
                                    transition
                                    hover:-translate-y-1
                                    hover:shadow-lg

                                    {{ $isCurrent ? 'border-violet-500 ring-4 ring-violet-100' : 'border-slate-200 hover:border-violet-300' }}
                                ">

                                <div
                                    class="
                                        relative
                                        aspect-[4/3]
                                        overflow-hidden
                                        bg-slate-100
                                    ">

                                    @if ($versionItem->image_url)
                                        <img src="{{ $versionItem->image_url }}" alt="{{ $versionItem->name }}"
                                            class="
                                                h-full
                                                w-full
                                                object-cover
                                            ">
                                    @endif


                                    @if ($isCurrent)
                                        <span
                                            class="
                                                absolute
                                                right-3
                                                top-3
                                                rounded-full
                                                bg-violet-600
                                                px-2.5
                                                py-1
                                                text-[8px]
                                                font-black
                                                text-white
                                            ">
                                            ★ BASE ACTIVA
                                        </span>
                                    @endif


                                    @if ($versionItem->is_default)
                                        <span
                                            class="
                                                absolute
                                                bottom-3
                                                left-3
                                                rounded-full
                                                bg-amber-400
                                                px-2.5
                                                py-1
                                                text-[8px]
                                                font-black
                                                text-amber-950
                                            ">
                                            ⚡ RESOLVER
                                        </span>
                                    @endif

                                </div>


                                <div class="p-4">

                                    <p
                                        class="
                                            truncate
                                            text-sm
                                            font-black
                                            text-slate-900
                                        ">
                                        {{ $versionItem->name }}
                                    </p>


                                    <p
                                        class="
                                            mt-1
                                            truncate
                                            text-[9px]
                                            font-bold
                                            text-violet-500
                                        ">
                                        {{ $versionItem->version?->name }}
                                        ·
                                        {{ $versionItem->version?->kind_label }}
                                    </p>


                                    @if ($isCurrent)
                                        <div
                                            class="
                                                mt-4
                                                rounded-xl
                                                bg-violet-50
                                                px-4
                                                py-2.5
                                                text-center
                                                text-xs
                                                font-black
                                                text-violet-700
                                            ">
                                            ✓ Es la Base actual
                                        </div>
                                    @else
                                        <form method="POST"
                                            action="{{ route('entities.base-version.update', $entity) }}"
                                            data-omni-confirm data-confirm-variant="violet" data-confirm-icon="★"
                                            data-confirm-title="Cambiar Base activa"
                                            data-confirm-message="
        Esta Version pasará a ser la
        representación principal de la Entidad.
    "
                                            data-confirm-subject="{{ $versionItem->name }}"
                                            data-confirm-detail="
        La Base original no se elimina
        y el Default del Resolver no cambiará.
    "
                                            data-confirm-action="Sí, usar como Base"
                                            data-confirm-image="{{ $versionItem->image_url ?? '' }}">

                                            @csrf
                                            @method('PUT')


                                            <input type="hidden" name="entity_version_id"
                                                value="{{ $versionItem->id }}">


                                            <button type="submit"
                                                class="
                                                    mt-4
                                                    w-full
                                                    rounded-xl
                                                    bg-violet-600
                                                    px-4
                                                    py-2.5
                                                    text-xs
                                                    font-black
                                                    text-white
                                                ">
                                                ★ Usar como Base
                                            </button>

                                        </form>
                                    @endif

                                </div>

                            </article>
                        @endforeach

                    </div>


                    @if ($availableBaseVersions->isEmpty())
                        <div
                            class="
                                mt-5
                                rounded-2xl
                                border
                                border-dashed
                                border-violet-200
                                bg-violet-50
                                p-8
                                text-center
                            ">

                            <p
                                class="
                                    text-sm
                                    font-black
                                    text-violet-800
                                ">
                                Todavía no existen Versiones activas.
                            </p>


                            <a href="{{ route('entity-versions.create', $entity) }}"
                                class="
                                    mt-3
                                    inline-flex
                                    rounded-xl
                                    bg-violet-600
                                    px-4
                                    py-2.5
                                    text-xs
                                    font-black
                                    text-white
                                ">
                                + Crear Version
                            </a>

                        </div>
                    @endif

                </div>

            </div>

        </div>

    </div>

</section>
