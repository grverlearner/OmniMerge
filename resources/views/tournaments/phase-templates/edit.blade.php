@php
    /*
     * La definición de una fase ya creada.
     *
     * Misma pantalla que al crearla, con la navegación del taller encima:
     * desde aquí se llega a las reglas del motor y a la Super Edición, que
     * es donde vive todo lo que decide cómo se juega.
     */
@endphp

<x-tournament-layout surface="dark">

    <x-slot name="header">{{ $phaseTemplate->name }}</x-slot>

    @include('tournaments.phase-templates.partials.workspace-navigation', [
        'current' => 'definition',
        'dark' => true,
    ])

    @if (session('success'))
        <div class="mb-5 rounded-2xl border border-emerald-500/40 bg-emerald-500/10 px-4 py-3 text-xs font-black text-emerald-300"
            role="status">
            ✓ {{ session('success') }}
        </div>
    @endif

    <form method="POST" action="{{ route('tournaments.phase-templates.update', $phaseTemplate) }}"
        enctype="multipart/form-data">

        @csrf
        @method('PUT')

        @include('tournaments.phase-templates.partials.form')

    </form>

</x-tournament-layout>
