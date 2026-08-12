<x-tournament-layout>

    <x-slot name="header">
        Nueva Fase
    </x-slot>

    <div class="mx-auto max-w-5xl">

        <a href="{{ route('tournaments.phase-templates.index') }}"
            class="text-xs font-black text-slate-400 transition hover:text-amber-600">
            ← Fases
        </a>

        <div class="mb-7 mt-5">
            <p class="text-xs font-black uppercase tracking-[0.18em] text-amber-600">
                Phase Designer
            </p>

            <h2 class="mt-2 text-3xl font-black text-slate-900">
                Nueva Fase
            </h2>

            <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-500">
                Define un mecanismo competitivo reutilizable.
                Después de crear la Fase podrás configurar sus
                puertas de salida.
            </p>
        </div>

        <form method="POST" action="{{ route('tournaments.phase-templates.store') }}" enctype="multipart/form-data">

            @csrf

            @include('tournaments.phase-templates.partials.form')

        </form>

    </div>

</x-tournament-layout>
