@php
    /*
     * La cuenta en si: lo que no se edita pero conviene ver.
     *
     * Estos datos existian en la tabla y no se enseñaban en ninguna parte:
     * desde cuando estas, cuando entraste por ultima vez, en que estado esta la
     * cuenta y si el correo esta verificado.
     */

    $tonosEstado = [
        'ACTIVE' => ['#34d399', 'Activa'],
        'INACTIVE' => ['#fbbf24', 'Inactiva'],
        'SUSPENDED' => ['#fb7185', 'Suspendida'],
        'BANNED' => ['#fb7185', 'Bloqueada'],
    ];

    [$tonoCuenta, $textoCuenta] = $tonosEstado[$usuario->status] ?? ['#94a3b8', $usuario->status];
@endphp

<section class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/50">

    <header class="flex flex-wrap items-center gap-2 border-b border-slate-800 px-4 py-2.5">

        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-slate-800 text-slate-300">
            <x-omni-icon name="panel" size="h-4 w-4" />
        </span>

        <div class="min-w-0 flex-1">
            <h2 class="text-[13px] font-black text-white">Tu cuenta</h2>
            <p class="text-[10px] text-slate-500">Esto no se edita; se mira.</p>
        </div>
    </header>

    <div class="grid gap-px bg-slate-800 sm:grid-cols-2 lg:grid-cols-4">

        <span class="bg-slate-900/60 px-3 py-2.5">
            <span class="block text-[9px] font-black uppercase tracking-wider text-slate-600">Estado</span>
            <span class="mt-0.5 block text-[13px] font-black" style="color: {{ $tonoCuenta }}">
                {{ $textoCuenta }}
            </span>
        </span>

        <span class="bg-slate-900/60 px-3 py-2.5">
            <span class="block text-[9px] font-black uppercase tracking-wider text-slate-600">Correo</span>
            <span class="mt-0.5 block text-[13px] font-black"
                style="color: {{ $usuario->email_verified_at ? '#34d399' : '#fbbf24' }}">
                {{ $usuario->email_verified_at ? 'Verificado' : 'Sin verificar' }}
            </span>
        </span>

        <span class="bg-slate-900/60 px-3 py-2.5">
            <span class="block text-[9px] font-black uppercase tracking-wider text-slate-600">Aquí desde</span>
            <span class="mt-0.5 block font-mono text-[13px] font-black text-slate-300">
                {{ $usuario->created_at->format('d/m/Y') }}
            </span>
        </span>

        <span class="bg-slate-900/60 px-3 py-2.5">
            <span class="block text-[9px] font-black uppercase tracking-wider text-slate-600">Última entrada</span>
            <span class="mt-0.5 block font-mono text-[13px] font-black text-slate-300">
                {{ $usuario->last_login_at ? $usuario->last_login_at->format('d/m/Y H:i') : '—' }}
            </span>
        </span>
    </div>
</section>
