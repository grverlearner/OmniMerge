@if (!$diagnostic['valid'] || $diagnostic['warnings'] !== [])
    <section class="mt-6 space-y-3">
        @if (!$diagnostic['valid'])
            <div class="rounded-3xl border border-red-200 bg-red-50 p-5">
                <div class="flex items-start gap-3">
                    <span
                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-red-100 font-black text-red-700">
                        !
                    </span>

                    <div>
                        <p class="font-black text-red-900">
                            Configuración incompatible
                        </p>

                        <div class="mt-2 space-y-1">
                            @foreach ($diagnostic['errors'] as $error)
                                <p class="text-xs leading-5 text-red-700">
                                    • {{ $error }}
                                </p>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        @endif

        @if ($diagnostic['warnings'] !== [])
            <div class="rounded-3xl border border-amber-200 bg-amber-50 p-5">
                <p class="font-black text-amber-900">
                    Recomendaciones
                </p>

                <div class="mt-2 space-y-1">
                    @foreach ($diagnostic['warnings'] as $warning)
                        <p class="text-xs leading-5 text-amber-800">
                            • {{ $warning }}
                        </p>
                    @endforeach
                </div>
            </div>
        @endif
    </section>
@else
    <section class="mt-6 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3">
        <p class="text-xs font-black text-emerald-800">
            ✓ El contrato, el objetivo y los overrides son compatibles.
        </p>
    </section>
@endif
