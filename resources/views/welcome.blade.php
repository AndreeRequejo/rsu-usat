<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ config('app.name', 'RSU - USAT') }}</title>

        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        <link rel="apple-touch-icon" href="/apple-touch-icon.png">

        @fonts

        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <style>
            .gradient-green {
                background: linear-gradient(to bottom, #f0fdf4, #ffffff);
            }
        </style>
    </head>
    <body class="min-h-screen bg-white antialiased text-neutral-900">
        <header class="border-b border-neutral-200 bg-white">
            <div class="mx-auto flex max-w-6xl items-center justify-between px-4 py-3 sm:px-6 lg:px-8">
                <a href="{{ route('home') }}" class="flex items-center gap-3" wire:navigate>
                    <img src="/logo-municipalidad.png" alt="Municipalidad" class="h-12 w-auto brightness-0">
                    <div class="hidden sm:block leading-tight">
                        <p class="text-[11px] font-semibold tracking-wide uppercase text-neutral-500">Municipalidad Distrital</p>
                        <p class="text-sm font-bold text-neutral-900">José Leonardo Ortiz</p>
                    </div>
                </a>

                @if (Route::has('login'))
                    <nav class="flex items-center gap-3">
                        @auth
                            <a
                                href="{{ route('dashboard') }}"
                                class="inline-flex items-center gap-2 rounded-full bg-green-900 px-5 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-green-800"
                                wire:navigate
                            >
                                <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <rect width="7" height="9" x="3" y="3" rx="1"/>
                                    <rect width="7" height="5" x="14" y="3" rx="1"/>
                                    <rect width="7" height="9" x="14" y="12" rx="1"/>
                                    <rect width="7" height="5" x="3" y="16" rx="1"/>
                                </svg>
                                Dashboard
                            </a>
                        @else
                            <a
                                href="{{ route('register') }}"
                                class="inline-flex items-center gap-2 rounded-full bg-green-900 px-5 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-green-800"
                                wire:navigate
                            >
                                Registrarse
                                <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M5 12h14"/>
                                    <path d="m12 5 7 7-7 7"/>
                                </svg>
                            </a>
                        @endauth
                    </nav>
                @endif
            </div>
        </header>

        <main class="gradient-gray-100 flex min-h-[calc(100vh-130px)] flex-col items-center justify-center px-4">
            <div class="mx-auto max-w-2xl text-center">
                <div class="mb-6 inline-flex items-center gap-2 rounded-full border border-neutral-200 bg-white px-4 py-1.5 text-sm text-neutral-600">
                    <svg class="h-4 w-4 text-green-600" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"/>
                    </svg>
                    Proyecto Universitario RSU
                </div>

                <h1 class="mb-6 text-4xl font-bold tracking-tight sm:text-5xl lg:text-6xl">
                    Sistema de Gestión de
                    <span class="text-green-600">Residuos Sólidos Urbanos</span>
                </h1>

                <p class="mb-10 text-lg text-neutral-500">
                    Plataforma digital de la Municipalidad Distrital de José Leonardo Ortiz<br>
                    para la gestión integral de residuos sólidos urbanos.
                </p>

                <div class="mb-8">
                    @if (Route::has('login'))
                        @auth
                            <a
                                href="{{ route('dashboard') }}"
                                class="inline-flex items-center gap-2 rounded-full bg-green-900 px-8 py-4 text-base font-semibold text-white transition-colors hover:bg-green-800"
                                wire:navigate
                            >
                                Ir al Dashboard
                                <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M5 12h14"/>
                                    <path d="m12 5 7 7-7 7"/>
                                </svg>
                            </a>
                        @else
                            <a
                                href="{{ route('login') }}"
                                class="inline-flex items-center gap-2 rounded-full bg-green-900 px-8 py-4 text-base font-semibold text-white transition-colors hover:bg-green-800"
                                wire:navigate
                            >
                                Iniciar sesión
                                <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M5 12h14"/>
                                    <path d="m12 5 7 7-7 7"/>
                                </svg>
                            </a>
                        @endauth
                    @endif
                </div>
            </div>
        </main>

        <footer class="border-t border-neutral-200 bg-white py-4">
            <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
                <div class="flex flex-col items-center justify-between gap-2 text-sm text-neutral-500 sm:flex-row">
                    <p>&copy; {{ date('Y') }} Municipalidad Distrital de José Leonardo Ortiz</p>
                    <p>Sistema RSU - USAT</p>
                </div>
            </div>
        </footer>

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
