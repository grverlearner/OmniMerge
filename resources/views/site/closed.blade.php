<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" href="{{ $sitio->faviconUrl() }}">
    <title>{{ $titulo }} | {{ $sitio->name() }}</title>
    @vite(['resources/css/app.css'])
</head>

{{-- Una parte del sitio que un admin ha cerrado --}}

<body class="flex min-h-screen items-center justify-center bg-slate-950 p-4 text-slate-100 antialiased">

    <main class="w-full max-w-md overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/60 text-center">

        <div class="h-1 w-full bg-slate-600"></div>

        <div class="p-8">
            <p class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-500">{{ $sitio->name() }}</p>

            <h1 class="mt-2 text-xl font-black text-white">{{ $titulo }}</h1>

            <p class="mt-2 text-sm leading-relaxed text-slate-400">{{ $mensaje }}</p>

            <a href="{{ route('hub') }}"
                class="mt-6 inline-block rounded-xl bg-violet-500 px-4 py-2 text-xs font-black text-white transition hover:bg-violet-400">
                Volver al centro
            </a>
        </div>
    </main>

</body>

</html>
