<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ $title ?? config('app.name', 'RSU - USAT') }}</title>

        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        <link rel="apple-touch-icon" href="/apple-touch-icon.png">

        @fonts

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-white antialiased">
        <div class="relative grid min-h-screen lg:grid-cols-2">
            {{-- Left panel: dark green gradient --}}
            <div class="relative hidden bg-linear-to-br from-green-900 via-teal-900 to-green-900 lg:flex lg:flex-col">
                <div class="absolute inset-0 bg-[url('data:image/svg+xml,%3Csvg%20width%3D%2260%22%20height%3D%2260%22%20viewBox%3D%220%200%2060%2060%22%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%3E%3Cg%20fill%3D%22none%22%20fill-rule%3D%22evenodd%22%3E%3Cg%20fill%3D%22%23ffffff%22%20fill-opacity%3D%220.03%22%3E%3Cpath%20d%3D%22M36%2034v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6%2034v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6%204V0H4v4H0v2h4v4h2V6h4V4H6z%22%2F%3E%3C%2Fg%3E%3C%2Fg%3E%3C%2Fsvg%3E')] opacity-50"></div>

                <div class="relative z-10 flex flex-1 flex-col p-10">
                    {{-- Back link --}}
                    <a href="{{ route('home') }}" class="inline-flex items-center gap-1.5 text-sm text-white/70 transition-colors hover:text-white" wire:navigate>
                        <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="m12 19-7-7 7-7"/>
                            <path d="M19 12H5"/>
                        </svg>
                        Volver al sitio
                    </a>

                    {{-- Logo --}}
                    <div class="flex flex-1 flex-col items-center justify-center">
                        <div class="flex items-center justify-center gap-3 text-center lg:text-left">
                            <img src="/logo-municipalidad.png" alt="Municipalidad" class="h-20 w-auto brightness-0 invert">
                            <div class="leading-tight">
                                <p class="text-xs font-semibold tracking-widest uppercase text-white/60">Sistema RSU</p>
                                <p class="text-xl font-bold text-white">Municipalidad Distrital de<br>José Leonardo Ortiz</p>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Footer --}}
                <div class="relative z-10 border-t border-white/10 p-10">
                    <p class="text-xs text-white/40">&copy; {{ date('Y') }} Municipalidad de José Leonardo Ortiz</p>
                </div>
            </div>

            {{-- Right panel: form --}}
            <div class="flex items-center justify-center px-6 py-12 lg:px-16">
                <div class="w-full max-w-md">
                    {{-- Mobile logo --}}
                    <div class="mb-8 flex items-center gap-3 lg:hidden">
                        <img src="/logo-municipalidad.png" alt="Municipalidad" class="h-10 w-auto">
                        <div class="leading-tight">
                            <p class="text-[10px] font-semibold tracking-widest uppercase text-neutral-400">Sistema RSU</p>
                            <p class="text-sm font-bold text-neutral-900">Municipalidad Distrital de<br>José Leonardo Ortiz</p>
                        </div>
                    </div>

                    {{ $slot }}
                </div>
            </div>
        </div>

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
