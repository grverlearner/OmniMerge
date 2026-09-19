@props([
    /* La pantalla va en fondo oscuro */
    'dark' => false,

    /*
     * Lo que la pantalla ya ha pintado. Las pantallas rediseñadas enseñan
     * su propio aviso, en su sitio y con su estilo; si este componente lo
     * repetía arriba, el usuario leía el mismo mensaje dos veces —y el de
     * arriba, además, en claro sobre una pantalla oscura—. Si el mensaje ya
     * está en la página, aquí no se vuelve a decir.
     */
    'contenido' => '',
])

@php
    $contenido = (string) $contenido;

    $yaDicho = fn(?string $mensaje) => $mensaje !== null
        && $mensaje !== ''
        && $contenido !== ''
        && str_contains($contenido, e($mensaje));

    $exito = session('success');
    $fallo = session('error');
    $primerError = $errors->any() ? $errors->first() : null;
@endphp

@if ($exito && ! $yaDicho($exito))
    <div
        class="mb-4 flex items-start justify-between rounded-2xl border px-4 py-3 {{ $dark ? 'border-emerald-500/30 bg-emerald-500/10 text-emerald-200' : 'mb-6 border-emerald-200 bg-emerald-50 px-5 py-4 text-emerald-800' }}">
        <div>
            <p class="font-semibold {{ $dark ? 'text-[12px] font-black text-emerald-300' : '' }}">
                Operación completada
            </p>

            <p class="mt-1 {{ $dark ? 'text-[12px]' : 'text-sm' }}">
                {{ $exito }}
            </p>
        </div>
    </div>
@endif

@if ($fallo && ! $yaDicho($fallo))
    <div
        class="mb-4 rounded-2xl border px-4 py-3 {{ $dark ? 'border-rose-500/30 bg-rose-500/10 text-rose-200' : 'mb-6 border-red-200 bg-red-50 px-5 py-4 text-red-800' }}">
        <p class="font-semibold {{ $dark ? 'text-[12px] font-black text-rose-300' : '' }}">
            No se pudo completar la operación
        </p>

        <p class="mt-1 {{ $dark ? 'text-[12px]' : 'text-sm' }}">
            {{ $fallo }}
        </p>
    </div>
@endif

@if ($errors->any() && ! $yaDicho($primerError))
    <div
        class="mb-4 rounded-2xl border px-4 py-3 {{ $dark ? 'border-amber-500/30 bg-amber-500/10 text-amber-200' : 'mb-6 border-amber-200 bg-amber-50 px-5 py-4 text-amber-900' }}">
        <p class="font-semibold {{ $dark ? 'text-[12px] font-black text-amber-300' : '' }}">
            Revisa los datos ingresados
        </p>

        <ul class="mt-2 list-inside list-disc {{ $dark ? 'text-[12px]' : 'text-sm' }}">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
