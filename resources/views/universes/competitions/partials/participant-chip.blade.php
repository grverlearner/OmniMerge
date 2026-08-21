@php
    /*
     * Ficha de participante — componente único.
     *
     * Se usa en el selector, en el workspace y en el inspector del Lab,
     * para que un participante se vea igual en todas partes.
     *
     * Degrada con elegancia: si no hay Entidad detrás (participantes
     * sintéticos del Competition Lab) muestra solo el nombre.
     *
     * Parámetros:
     *   $name            string
     *   $imageUrl        ?string
     *   $typeName        ?string
     *   $versionName     ?string
     *   $attributes      ?array   [['name','display','featured'], ...]
     *   $seed            ?int
     *   $size            'sm' | 'md'   (por defecto 'md')
     *   $maxAttributes   int           (por defecto 3)
     */

    $size = $size ?? 'md';
    $maxAttributes = $maxAttributes ?? 3;
    $attributes = $attributes ?? [];

    $avatar = $size === 'sm' ? 'h-9 w-9 text-base' : 'h-12 w-12 text-xl';
    $title = $size === 'sm' ? 'text-[12px]' : 'text-sm';

    /* Los destacados de la Biblioteca mandan sobre el resto. */
    $shown = collect($attributes)
        ->sortByDesc(fn($attribute) => ($attribute['featured'] ?? false) ? 1 : 0)
        ->take($maxAttributes)
        ->values();
@endphp


<div class="flex min-w-0 items-center gap-3">

    <div
        class="
            {{ $avatar }}

            flex
            shrink-0
            items-center
            justify-center
            overflow-hidden
            rounded-xl
            bg-violet-100
            text-violet-500
        ">

        @if (!empty($imageUrl))
            <img src="{{ $imageUrl }}" alt="{{ $name }}"
                class="h-full w-full object-cover">
        @else
            ✦
        @endif

    </div>


    <div class="min-w-0 flex-1">

        <div class="flex items-center gap-1.5">

            @if (!empty($seed))
                <span
                    class="
                        shrink-0
                        rounded
                        bg-slate-100
                        px-1.5
                        py-0.5
                        font-mono
                        text-[9px]
                        font-black
                        text-slate-500
                    ">
                    #{{ $seed }}
                </span>
            @endif


            <p
                class="
                    {{ $title }}

                    truncate
                    font-black
                    text-slate-900
                ">
                {{ $name }}
            </p>

        </div>


        @if (!empty($typeName) || !empty($versionName))
            <p
                class="
                    mt-0.5
                    truncate
                    text-[10px]
                    text-slate-400
                ">
                {{ $typeName }}

                @if (!empty($typeName) && !empty($versionName))
                    ·
                @endif

                @if (!empty($versionName))
                    <span class="text-violet-500">
                        {{ $versionName }}
                    </span>
                @endif
            </p>
        @endif


        @if ($shown->isNotEmpty())
            <div class="mt-1.5 flex flex-wrap gap-1">

                @foreach ($shown as $attribute)
                    <span
                        class="
                            {{ ($attribute['featured'] ?? false)
                                ? 'bg-violet-100 text-violet-700'
                                : 'bg-slate-100 text-slate-600' }}

                            rounded
                            px-1.5
                            py-0.5
                            text-[9px]
                            font-bold
                        ">
                        {{ $attribute['name'] }}
                        <span class="font-black">
                            {{ $attribute['display'] }}
                        </span>
                    </span>
                @endforeach

            </div>
        @endif

    </div>

</div>
