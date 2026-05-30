<x-layouts::auth :title="__('Iniciar sesión')">
    <div class="flex flex-col">
        <div class="mb-8">
            <p class="mb-1 text-xs font-semibold tracking-widest uppercase text-green-600">Acceso institucional</p>
            <h1 class="mb-2 text-3xl font-bold text-neutral-900">Bienvenido de vuelta</h1>
            <p class="text-sm text-neutral-500">Ingresa tus credenciales para acceder al panel de gestión RSU.</p>
        </div>

        <x-auth-session-status class="mb-6" :status="session('status')" />

        <form method="POST" action="{{ route('login.store') }}" class="flex flex-col gap-5">
            @csrf

            {{-- Email --}}
            <div>
                <label for="email" class="mb-1.5 block text-sm font-medium text-neutral-700">Correo institucional</label>
                <div class="relative">
                    <div class="pointer-events-none absolute inset-y-0 start-0 flex items-center ps-3.5">
                        <svg class="h-4 w-4 text-neutral-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect width="20" height="16" x="2" y="4" rx="2"/>
                            <path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/>
                        </svg>
                    </div>
                    <input
                        id="email"
                        name="email"
                        type="email"
                        value="{{ old('email') }}"
                        required
                        autofocus
                        autocomplete="email"
                        placeholder="nombre@munijslo.gob.pe"
                        class="block w-full rounded-lg border border-neutral-300 bg-white py-2.5 ps-10 pe-4 text-sm text-neutral-900 placeholder-neutral-400 transition-colors focus:border-green-500 focus:outline-none focus:ring-1 focus:ring-green-500"
                    >
                </div>
                @error('email')
                    <p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>
                @enderror
            </div>

            {{-- Password --}}
            <div>
                <div class="mb-1.5 flex items-center justify-between">
                    <label for="password" class="text-sm font-medium text-neutral-700">Contraseña</label>
                    @if (Route::has('password.request'))
                        <a href="{{ route('password.request') }}" class="text-xs text-green-600 hover:text-green-700" wire:navigate>
                            ¿Olvidaste tu contraseña?
                        </a>
                    @endif
                </div>
                <div class="relative" x-data="{ show: false }">
                    <div class="pointer-events-none absolute inset-y-0 start-0 flex items-center ps-3.5">
                        <svg class="h-4 w-4 text-neutral-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect width="18" height="11" x="3" y="11" rx="2" ry="2"/>
                            <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                        </svg>
                    </div>
                    <input
                        id="password"
                        name="password"
                        :type="show ? 'text' : 'password'"
                        required
                        autocomplete="current-password"
                        placeholder="••••••••"
                        class="block w-full rounded-lg border border-neutral-300 bg-white py-2.5 ps-10 pe-10 text-sm text-neutral-900 placeholder-neutral-400 transition-colors focus:border-green-500 focus:outline-none focus:ring-1 focus:ring-green-500"
                    >
                    <button type="button" @click="show = !show" class="absolute inset-y-0 end-0 flex items-center pe-3.5 text-neutral-400 hover:text-neutral-600">
                        <svg x-show="!show" class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M2.062 12.348a1 1 0 0 1 0-.696 10.75 10.75 0 0 1 19.876 0 1 1 0 0 1 0 .696 10.75 10.75 0 0 1-19.876 0"/>
                            <circle cx="12" cy="12" r="3"/>
                        </svg>
                        <svg x-show="show" x-cloak class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M10.733 5.076a10.744 10.744 0 0 1 11.205 6.575 1 1 0 0 1 0 .696 10.747 10.747 0 0 1-1.444 2.49"/>
                            <path d="M14.084 14.158a3 3 0 0 1-4.242-4.242"/>
                            <path d="M17.479 17.499a10.75 10.75 0 0 1-15.417-5.151 1 1 0 0 1 0-.696 10.75 10.75 0 0 1 4.446-5.143"/>
                            <path d="m2 2 20 20"/>
                        </svg>
                    </button>
                </div>
                @error('password')
                    <p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>
                @enderror
            </div>

            {{-- Remember me --}}
            <div class="flex items-center gap-2">
                <input
                    id="remember"
                    name="remember"
                    type="checkbox"
                    {{ old('remember') ? 'checked' : '' }}
                    class="h-4 w-4 rounded border-neutral-300 accent-green-900 hover:accent-green-600"
                >
                <label for="remember" class="text-sm text-neutral-600">Mantener sesión iniciada</label>
            </div>

            {{-- Submit --}}
            <button
                type="submit"
                class="mt-2 w-full rounded-lg bg-green-900 px-4 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-green-800 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2"
            >
                Iniciar sesión
            </button>
        </form>

        {{-- Support --}}
        <p class="mt-6 text-center text-sm text-neutral-500">
            ¿Aún no tienes cuenta?
            <a href="{{ route('register') }}" class="font-medium text-green-700 hover:text-green-800">Regístrate aquí</a>
        </p>
    </div>


</x-layouts::auth>
