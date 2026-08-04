@if (session('success'))
    <div class="mb-6 flex items-start justify-between rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-emerald-800">
        <div>
            <p class="font-semibold">
                Operación completada
            </p>

            <p class="mt-1 text-sm">
                {{ session('success') }}
            </p>
        </div>
    </div>
@endif

@if (session('error'))
    <div class="mb-6 rounded-2xl border border-red-200 bg-red-50 px-5 py-4 text-red-800">
        <p class="font-semibold">
            No se pudo completar la operación
        </p>

        <p class="mt-1 text-sm">
            {{ session('error') }}
        </p>
    </div>
@endif

@if ($errors->any())
    <div class="mb-6 rounded-2xl border border-amber-200 bg-amber-50 px-5 py-4 text-amber-900">
        <p class="font-semibold">
            Revisa los datos ingresados
        </p>

        <ul class="mt-2 list-inside list-disc text-sm">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif