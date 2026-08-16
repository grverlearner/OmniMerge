<x-tournament-layout>

    <x-slot name="header">
        Editar Fase
    </x-slot>

    <div class="mx-auto max-w-7xl">

        @include('tournaments.phase-templates.partials.workspace-navigation', [
            'current' => 'definition',
        ])

        <section
            class="mb-7 overflow-hidden rounded-3xl border border-amber-200 bg-gradient-to-br from-amber-50 via-white to-orange-50">

            <div class="grid gap-6 p-6 lg:grid-cols-[1fr_auto] lg:items-center lg:p-8">

                <div>
                    <div class="flex flex-wrap items-center gap-2">
                        <span
                            class="rounded-full bg-amber-100 px-3 py-1 text-[10px] font-black uppercase tracking-[0.18em] text-amber-700">
                            Definición de la fase
                        </span>

                        <span
                            class="rounded-full border border-slate-200 bg-white px-3 py-1 font-mono text-[10px] font-black text-slate-500">
                            {{ $phaseTemplate->code }}
                        </span>

                        <span
                            class="rounded-full border border-slate-200 bg-white px-3 py-1 text-[10px] font-black text-slate-500">
                            {{ $phaseTemplate->type_label }}
                        </span>
                    </div>

                    <h1 class="mt-4 text-3xl font-black tracking-tight text-slate-900 sm:text-4xl">
                        Editar {{ $phaseTemplate->name }}
                    </h1>

                    <p class="mt-3 max-w-3xl text-sm leading-6 text-slate-600">
                        Ajusta su identidad y el contrato de participantes. El tipo permanece
                        bloqueado para proteger las reglas y la estructura ya configuradas.
                    </p>
                </div>

                <div
                    class="flex h-20 w-20 items-center justify-center rounded-3xl bg-amber-500 text-4xl text-white shadow-xl shadow-amber-500/20">
                    ✎
                </div>

            </div>

        </section>

        @if (session('success'))
            <div class="mb-6 rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-bold text-emerald-800"
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

    </div>

</x-tournament-layout>
