@php
    /*
     * Un campo de contraseña con su botón de ver / ocultar.
     *
     * Entrada lo resolvía con dos SVG pegados en la vista y registro con las
     * palabras «Ver» y «Ocultar»: dos soluciones distintas para lo mismo. Ahora
     * es una, con los iconos del juego de la aplicación.
     *
     *   nombre        name e id del input
     *   etiqueta      texto de la etiqueta
     *   autocomplete  current-password | new-password
     *   placeholder   opcional
     *   enlace        [url, texto] a la derecha de la etiqueta, opcional
     *   ayuda         línea de ayuda debajo, opcional
     *   extra         atributos crudos para Alpine, solo del código
     *   bolsa         bolsa de errores (por defecto la normal)
     */

    $bolsa = isset($bolsa) ? $errors->getBag($bolsa) : $errors;
    $conError = $bolsa->has($nombre);
@endphp

<div x-data="{ ver: false }">

    <div class="flex items-center justify-between gap-2">
        <label for="{{ $nombre }}" class="block text-[11px] font-black uppercase tracking-wider text-slate-500">
            {{ $etiqueta }}
        </label>

        @if (isset($enlace))
            <a href="{{ $enlace[0] }}" class="text-[12px] font-bold text-indigo-400 transition hover:text-indigo-200">
                {{ $enlace[1] }}
            </a>
        @endif
    </div>

    <div class="relative mt-1.5">

        <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-600">
            <x-omni-icon name="candado" size="h-4 w-4" />
        </span>

        <input id="{{ $nombre }}" name="{{ $nombre }}" :type="ver ? 'text' : 'password'" type="password" required
            autocomplete="{{ $autocomplete ?? 'current-password' }}"
            @if (isset($placeholder)) placeholder="{{ $placeholder }}" @endif
            @if ($autofocus ?? false) autofocus @endif
            @if ($conError) aria-invalid="true" @endif
            {!! $extra ?? '' !!}
            class="w-full rounded-xl bg-slate-950/70 py-3 pl-10 pr-12 text-[14px] text-white placeholder:text-slate-600 focus:ring-1
                {{ $conError ? 'border-rose-500/60 focus:border-rose-500 focus:ring-rose-500' : 'border-slate-800 focus:border-indigo-500 focus:ring-indigo-500' }}">

        <button type="button" @click="ver = ! ver"
            :aria-label="ver ? 'Ocultar la contraseña' : 'Ver la contraseña'"
            class="absolute inset-y-0 right-0 flex items-center px-3.5 text-slate-600 transition hover:text-indigo-300">
            <x-omni-icon x-show="! ver" name="ojo" size="h-4 w-4" />
            <x-omni-icon x-show="ver" x-cloak name="ojo-tachado" size="h-4 w-4" />
        </button>
    </div>

    @if (isset($ayuda))
        <p class="mt-1.5 text-[11px] leading-4 text-slate-600">{{ $ayuda }}</p>
    @endif

    @foreach ($bolsa->get($nombre) as $mensaje)
        <p class="mt-1.5 text-[12px] font-bold text-rose-300">{{ $mensaje }}</p>
    @endforeach
</div>
