@php
    /*
     * El pie. Enlaces a lo que existe, y nada mas.
     */
@endphp

<footer class="relative border-t border-white/10 px-5 py-8 lg:px-8">

    <div class="mx-auto flex max-w-7xl flex-wrap items-center gap-4">

        <a href="{{ route('home') }}" class="flex shrink-0 items-center gap-2.5">

            <span class="flex h-8 w-8 items-center justify-center overflow-hidden rounded-lg bg-gradient-to-br from-indigo-500 to-violet-600">
                <img src="{{ asset('images/joganboruto.jpg') }}" alt="" class="h-full w-full object-cover">
            </span>

            <span class="leading-none">
                <span class="block text-[13px] font-black tracking-tight text-white">OmniMerge</span>
                <span class="block text-[8px] font-black uppercase tracking-[0.2em] text-slate-600">
                    Create · Connect · Evolve
                </span>
            </span>
        </a>

        <nav class="flex flex-wrap items-center gap-x-4 gap-y-1">
            @foreach (['#modulos' => 'Qué es', '#como-funciona' => 'Cómo funciona', '#enfrentamiento' => 'Cómo se gana', '#comunidad' => 'Comunidad'] as $ancla => $texto)
                <a href="{{ $ancla }}"
                    class="text-[12px] font-semibold text-slate-500 transition hover:text-slate-200">
                    {{ $texto }}
                </a>
            @endforeach
        </nav>

        <span class="flex-1"></span>

        <span class="font-mono text-[11px] text-slate-600">
            © {{ date('Y') }} OmniMerge
        </span>
    </div>
</footer>
