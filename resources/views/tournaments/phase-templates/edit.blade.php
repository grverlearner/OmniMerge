<x-tournament-layout>

    <x-slot name="header">
        Editar Fase
    </x-slot>

    <div class="mx-auto max-w-5xl">

        <a href="{{ route('tournaments.phase-templates.show', $phaseTemplate) }}"
            class="text-xs font-black text-slate-400 transition hover:text-amber-600">
            ← Volver a la Fase
        </a>

        <div class="mb-7 mt-5">
            <p class="font-mono text-xs font-black text-amber-600">
                {{ $phaseTemplate->code }}
            </p>

            <h2 class="mt-2 text-3xl font-black text-slate-900">
                Editar {{ $phaseTemplate->name }}
            </h2>
        </div>

        <form method="POST" action="{{ route('tournaments.phase-templates.update', $phaseTemplate) }}"
            enctype="multipart/form-data">

            @csrf
            @method('PUT')

            @include('tournaments.phase-templates.partials.form')

        </form>

    </div>

</x-tournament-layout>
