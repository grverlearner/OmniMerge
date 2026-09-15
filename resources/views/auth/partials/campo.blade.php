@php
    /*
     * Un campo de las pantallas de entrada.
     *
     * Las seis pantallas del flujo repetían el mismo input con clases distintas
     * cada vez —y cuatro de ellas, las de Breeze, ni siquiera eran oscuras—.
     * Vive aquí una sola vez.
     *
     *   nombre        name e id del input (y la clave del error)
     *   etiqueta      texto de la etiqueta
     *   tipo          text | email (por defecto text)
     *   icono         nombre de <x-omni-icon> a la izquierda, opcional
     *   prefijo       texto fijo a la izquierda en vez de icono (la @ del usuario)
     *   valor         valor inicial
     *   ayuda         línea de ayuda debajo, opcional
     *   extra         atributos crudos para Alpine (x-model…), solo del código
     */

    $tipo = $tipo ?? 'text';
    $icono = $icono ?? null;
    $prefijo = $prefijo ?? null;
    $valor = $valor ?? '';
    $ayuda = $ayuda ?? null;
    $conError = $errors->has($nombre);
@endphp

<div>
    <label for="{{ $nombre }}" class="block text-[11px] font-black uppercase tracking-wider text-slate-500">
        {{ $etiqueta }}
    </label>

    <div class="relative mt-1.5">

        @if ($icono)
            <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-600">
                <x-omni-icon :name="$icono" size="h-4 w-4" />
            </span>
        @elseif ($prefijo)
            <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 font-mono text-[14px] font-black text-slate-600">
                {{ $prefijo }}
            </span>
        @endif

        <input id="{{ $nombre }}" name="{{ $nombre }}" type="{{ $tipo }}" value="{{ $valor }}"
            @if ($required ?? true) required @endif
            @if ($autofocus ?? false) autofocus @endif
            @if (isset($autocomplete)) autocomplete="{{ $autocomplete }}" @endif
            @if (isset($placeholder)) placeholder="{{ $placeholder }}" @endif
            @if ($conError) aria-invalid="true" @endif
            {!! $extra ?? '' !!}
            class="w-full rounded-xl bg-slate-950/70 py-3 pr-4 text-[14px] text-white placeholder:text-slate-600 focus:ring-1
                {{ $icono || $prefijo ? 'pl-10' : 'pl-4' }}
                {{ $conError ? 'border-rose-500/60 focus:border-rose-500 focus:ring-rose-500' : 'border-slate-800 focus:border-indigo-500 focus:ring-indigo-500' }}">
    </div>

    @if ($ayuda)
        <p class="mt-1.5 text-[11px] leading-4 text-slate-600">{{ $ayuda }}</p>
    @endif

    @error($nombre)
        <p class="mt-1.5 text-[12px] font-bold text-rose-300">{{ $message }}</p>
    @enderror
</div>
