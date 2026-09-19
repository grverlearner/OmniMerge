@php
    /*
     * De dónde viene una cosa de la comunidad.
     *
     * Cuando alguien copia algo, la copia guarda a quién se lo copió
     * (`source_*_id`). El dato existía desde el principio y no se enseñaba en
     * ninguna parte, así que una cadena de tres copias parecía tres creaciones
     * originales. Aquí se dice.
     *
     *   $autor    quien lo tiene ahora
     *   $origen   de quién lo copió, o null si es suyo de origen
     */

    $nombreAutor = $autor?->username ?? $autor?->name;
    $nombreOrigen = $origen?->username ?? $origen?->name;
@endphp

<span class="flex min-w-0 flex-wrap items-center gap-1 text-[9px] leading-3">

    @if ($nombreAutor)
        <a href="{{ route('community.creators.show', $autor->username) }}"
            class="flex min-w-0 items-center gap-1 transition hover:underline">

            <span class="h-4 w-4 shrink-0 overflow-hidden rounded-full border border-slate-700 bg-slate-900">
                @if ($autor->avatar_url ?? null)
                    <img src="{{ $autor->avatar_url }}" alt="" loading="lazy" class="h-full w-full object-cover">
                @else
                    <span class="flex h-full w-full items-center justify-center text-[7px] font-black text-slate-500">
                        {{ mb_strtoupper(mb_substr($nombreAutor, 0, 1)) }}
                    </span>
                @endif
            </span>

            <span class="truncate font-black text-slate-400">{{ '@' . $nombreAutor }}</span>
        </a>

        <x-creator-badge :user="$autor" size="xs" />
    @endif

    @if ($nombreOrigen && $nombreOrigen !== $nombreAutor)
        <span class="shrink-0 text-slate-700">·</span>

        <span class="flex min-w-0 items-center gap-1 text-slate-600" title="Esta copia parte del trabajo de otra persona">
            inspirado en
            <a href="{{ route('community.creators.show', $origen->username) }}"
                class="truncate font-black text-amber-400/80 transition hover:underline">
                {{ '@' . $nombreOrigen }}
            </a>
        </span>
    @endif
</span>
