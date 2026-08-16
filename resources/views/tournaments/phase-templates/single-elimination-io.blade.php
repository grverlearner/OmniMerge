<x-tournament-layout>
    <x-slot name="header">
        Puertas de entrada y salida · {{ $phaseTemplate->name }}
    </x-slot>

    <div class="pb-16">
        @include('tournaments.phase-templates.partials.workspace-navigation', [
            'current' => 'io',
        ])

        <section class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            @foreach ([['Entradas', $inputGates->count(), 'border-fuchsia-200 bg-fuchsia-50 text-fuchsia-700'], ['Rutas internas', $inputGates->sum(fn($gate) => $gate->outgoingConnections->count()), 'border-indigo-200 bg-indigo-50 text-indigo-700'], ['Salidas', $exits->count(), 'border-emerald-200 bg-emerald-50 text-emerald-700'], ['Errores', $validation['counts']['errors'], 'border-red-200 bg-red-50 text-red-700']] as [$label, $value, $classes])
                <div class="rounded-2xl border p-4 {{ $classes }}">
                    <p class="text-[9px] font-black uppercase tracking-wider">
                        {{ $label }}
                    </p>

                    <p class="mt-1 text-2xl font-black">
                        {{ $value }}
                    </p>
                </div>
            @endforeach
        </section>

        @if (session('success'))
            <div
                class="mt-5 rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-bold text-emerald-800">
                {{ session('success') }}
            </div>
        @endif

        @if (session('warning'))
            <div class="mt-5 rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm font-bold text-amber-800">
                {{ session('warning') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="mt-5 rounded-2xl border border-red-200 bg-red-50 p-4">
                <p class="font-black text-red-900">
                    No se pudo guardar la configuración
                </p>

                <div class="mt-2 space-y-1">
                    @foreach ($errors->all() as $error)
                        <p class="text-xs leading-5 text-red-700">
                            • {{ $error }}
                        </p>
                    @endforeach
                </div>
            </div>
        @endif

        @if ($rounds->isEmpty())
            <section class="mt-8 rounded-[32px] border border-dashed border-amber-300 bg-amber-50 p-8 text-center">
                <h2 class="text-xl font-black text-slate-900">
                    Primero genera la estructura interna
                </h2>

                <p class="mx-auto mt-2 max-w-2xl text-sm leading-6 text-slate-600">
                    Las puertas pueden definirse, pero el mapeo hacia slots
                    necesita rondas y encuentros generados.
                </p>

                <a href="{{ route('tournaments.single-elimination.structure.show', $phaseTemplate) }}"
                    class="mt-5 inline-flex rounded-xl bg-violet-600 px-5 py-3 text-xs font-black text-white">
                    Ir al generador estructural
                </a>
            </section>
        @endif

        @include('tournaments.phase-templates.partials.single-elimination-io-manager')
    </div>
</x-tournament-layout>
