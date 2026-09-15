@php
    /*
     * La contraseña. Nada que inventar aqui: los tres campos de siempre, en
     * oscuro y con los errores en su sitio.
     */
@endphp

<section class="overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/50">

    <header class="flex flex-wrap items-center gap-2 border-b border-slate-800 px-4 py-2.5">

        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-slate-800 text-slate-300">
            <x-omni-icon name="engranaje" size="h-4 w-4" />
        </span>

        <div class="min-w-0 flex-1">
            <h2 class="text-[13px] font-black text-white">Contraseña</h2>
            <p class="text-[10px] text-slate-500">
                Usa una larga y que no repitas en otro sitio.
            </p>
        </div>
    </header>

    @if (session('status') === 'password-updated')
        <p class="border-b border-emerald-500/20 bg-emerald-500/10 px-4 py-2 text-[11px] font-bold text-emerald-200">
            Contraseña cambiada.
        </p>
    @endif

    <form method="POST" action="{{ route('password.update') }}" class="p-4">

        @csrf
        @method('PUT')

        <div class="grid gap-3 sm:grid-cols-3">

            <label class="block">
                <span class="text-[9px] font-black uppercase tracking-wider text-slate-600">
                    La de ahora
                </span>
                <input type="password" name="current_password" autocomplete="current-password"
                    class="mt-1 w-full rounded-xl border-slate-800 bg-slate-950 text-[12px] text-slate-200 focus:border-indigo-500 focus:ring-indigo-500">
                <x-input-error :messages="$errors->updatePassword->get('current_password')" class="mt-1" />
            </label>

            <label class="block">
                <span class="text-[9px] font-black uppercase tracking-wider text-slate-600">
                    La nueva
                </span>
                <input type="password" name="password" autocomplete="new-password"
                    class="mt-1 w-full rounded-xl border-slate-800 bg-slate-950 text-[12px] text-slate-200 focus:border-indigo-500 focus:ring-indigo-500">
                <x-input-error :messages="$errors->updatePassword->get('password')" class="mt-1" />
            </label>

            <label class="block">
                <span class="text-[9px] font-black uppercase tracking-wider text-slate-600">
                    Otra vez la nueva
                </span>
                <input type="password" name="password_confirmation" autocomplete="new-password"
                    class="mt-1 w-full rounded-xl border-slate-800 bg-slate-950 text-[12px] text-slate-200 focus:border-indigo-500 focus:ring-indigo-500">
                <x-input-error :messages="$errors->updatePassword->get('password_confirmation')" class="mt-1" />
            </label>
        </div>

        <div class="mt-3 flex justify-end">
            <button type="submit"
                class="rounded-xl border border-slate-700 px-4 py-2.5 text-[12px] font-black text-slate-300 transition hover:border-indigo-500 hover:text-indigo-300">
                Cambiar la contraseña
            </button>
        </div>
    </form>
</section>
