<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" href="{{ $sitio->faviconUrl() }}">
    <title>En mantenimiento | {{ $sitio->name() }}</title>
    @vite(['resources/css/app.css'])
</head>

{{--
    El sitio en mantenimiento. La ve todo el que no es admin; los admins
    siguen entrando para poder terminar lo que están haciendo y reabrirlo.
--}}

<body class="flex min-h-screen items-center justify-center bg-slate-950 p-4 text-slate-100 antialiased">

    <main class="w-full max-w-md overflow-hidden rounded-2xl border border-amber-500/30 bg-slate-900/60 text-center">

        <div class="h-1 w-full bg-amber-500"></div>

        <div class="p-8">
            <span class="mx-auto flex h-14 w-14 items-center justify-center overflow-hidden rounded-2xl border border-slate-800 bg-slate-950">
                <img src="{{ $sitio->logoUrl() ?? $sitio->faviconUrl() }}" alt="" class="h-full w-full object-cover">
            </span>

            <p class="mt-5 text-[10px] font-black uppercase tracking-[0.2em] text-amber-300">
                {{ $sitio->name() }} · en mantenimiento
            </p>

            <h1 class="mt-2 text-xl font-black text-white">Volvemos enseguida</h1>

            <p class="mt-2 whitespace-pre-line text-sm leading-relaxed text-slate-400">{{ $mensaje }}</p>

            @auth
                <form method="POST" action="{{ route('logout') }}" class="mt-6">
                    @csrf
                    <button type="submit"
                        class="rounded-xl border border-slate-700 px-4 py-2 text-xs font-black text-slate-300 transition hover:border-slate-500 hover:text-white">
                        Cerrar sesión
                    </button>
                </form>
            @else
                <a href="{{ route('login') }}"
                    class="mt-6 inline-block rounded-xl border border-slate-700 px-4 py-2 text-xs font-black text-slate-300 transition hover:border-slate-500 hover:text-white">
                    Entrar
                </a>
            @endauth
        </div>
    </main>

</body>

</html>
