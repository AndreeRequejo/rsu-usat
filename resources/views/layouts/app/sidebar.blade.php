<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
        @stack('styles')
    </head>
    <body class="min-h-screen bg-white dark:bg-zinc-800">
        <flux:sidebar sticky collapsible="mobile" class="border-e border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:sidebar.header>
                <x-app-logo :sidebar="true" href="{{ route('dashboard') }}" wire:navigate />
                <flux:sidebar.collapse class="lg:hidden" />
            </flux:sidebar.header>

            <flux:sidebar.nav>
                <flux:sidebar.group :heading="__('Plataforma')" class="grid">
                    <flux:sidebar.item icon="home" :href="route('dashboard')" :current="request()->routeIs('dashboard')" wire:navigate>
                        {{ __('Dashboard') }}
                    </flux:sidebar.item>
                </flux:sidebar.group>
            </flux:sidebar.nav>

            <flux:sidebar.nav>
                <flux:sidebar.group expandable :heading="__('Gestión de Vehículos')" icon="truck" class="grid">
                    <flux:sidebar.item
                        icon="truck"
                        :href="route('vehicles.vehicles.index')"
                        :current="request()->routeIs('vehicles.vehicles.*')"
                        wire:navigate
                    >
                        {{ __('Vehículos') }}
                    </flux:sidebar.item>

                    <flux:sidebar.item
                        icon="rectangle-stack"
                        :href="route('vehicles.types.index')"
                        :current="request()->routeIs('vehicles.types.*')"
                        wire:navigate
                    >
                        {{ __('Tipos') }}
                    </flux:sidebar.item>

                    <flux:sidebar.item
                        icon="building-office-2"
                        :href="route('vehicles.brands.index')"
                        :current="request()->routeIs('vehicles.brands.*')"
                        wire:navigate
                    >
                        {{ __('Marcas') }}
                    </flux:sidebar.item>

                    <flux:sidebar.item
                        icon="cube"
                        :href="route('vehicles.models.index')"
                        :current="request()->routeIs('vehicles.models.index')"
                        wire:navigate
                    >
                        {{ __('Modelos') }}
                    </flux:sidebar.item>

                    <flux:sidebar.item
                        icon="swatch"
                        :href="route('vehicles.colors')"
                        :current="request()->routeIs('vehicles.colors')"
                        wire:navigate
                    >
                        {{ __('Colores') }}
                    </flux:sidebar.item>

                    <flux:sidebar.item
                        icon="wrench-screwdriver"
                        :href="route('vehicles.maintenance.index')"
                        :current="request()->routeIs('vehicles.maintenance.*')"
                        wire:navigate
                    >
                        {{ __('Mantenimiento') }}
                    </flux:sidebar.item>
                </flux:sidebar.group>
            </flux:sidebar.nav>

            <flux:sidebar.nav>
                <flux:sidebar.group expandable :heading="__('Gestión de Personal')" icon="user-group" class="grid">
                    <flux:sidebar.item
                        icon="queue-list"
                        :href="route('personnel.types.index')"
                        :current="request()->routeIs('personnel.types.*')"
                        wire:navigate
                    >
                        {{ __('Tipos') }}
                    </flux:sidebar.item>

                    <flux:sidebar.item
                        icon="user"
                        :href="route('personnel.personnel.index')"
                        :current="request()->routeIs('personnel.personnel.*')"
                        wire:navigate
                    >
                        {{ __('Personal') }}
                    </flux:sidebar.item>

                    <flux:sidebar.item
                        icon="document-text"
                        :href="route('personnel.contracts.index')"
                        :current="request()->routeIs('personnel.contracts.*')"
                        wire:navigate
                    >
                        {{ __('Contratos') }}
                    </flux:sidebar.item>

                    <flux:sidebar.item
                        icon="check-badge"
                        :href="route('personnel.attendance.index')"
                        :current="request()->routeIs('personnel.attendance.*')"
                        wire:navigate
                    >
                        {{ __('Asistencias') }}
                    </flux:sidebar.item>

                    <flux:sidebar.item
                        icon="arrow-left-end-on-rectangle"
                        :href="route('personnel.vacations.index')"
                        :current="request()->routeIs('personnel.vacations.*')"
                        wire:navigate
                    >
                        {{ __('Vacaciones') }}
                    </flux:sidebar.item>
                </flux:sidebar.group>
            </flux:sidebar.nav>

            <flux:sidebar.nav>
                <flux:sidebar.group expandable :heading="__('Programación')" icon="user-group" class="grid">
                    <flux:sidebar.item
                        icon="queue-list"
                        :href="route('scheduling.shifts.index')"
                        :current="request()->routeIs('scheduling.shifts.*')"
                        wire:navigate
                    >
                        {{ __('Turnos') }}
                    </flux:sidebar.item>

                    <flux:sidebar.item
                        icon="user"
                        :href="route('scheduling.zones.index')"
                        :current="request()->routeIs('scheduling.zones.*')"
                        wire:navigate
                    >
                        {{ __('Zonas') }}
                    </flux:sidebar.item>

                    <flux:sidebar.item
                        icon="document-text"
                        :href="route('scheduling.holidays.index')"
                        :current="request()->routeIs('scheduling.holidays.*')"
                        wire:navigate
                    >
                        {{ __('Feriados') }}
                    </flux:sidebar.item>

                    <flux:sidebar.item
                        icon="check-badge"
                        :href="route('scheduling.groups.index')"
                        :current="request()->routeIs('scheduling.groups.*')"
                        wire:navigate
                    >
                        {{ __('Grupos de personal') }}
                    </flux:sidebar.item>

                    <flux:sidebar.item
                        icon="arrow-left-end-on-rectangle"
                        :href="route('scheduling.scheduling.index')"
                        :current="request()->routeIs('scheduling.scheduling.*')"
                        wire:navigate
                    >
                        {{ __('Programación') }}
                    </flux:sidebar.item>
                </flux:sidebar.group>
            </flux:sidebar.nav>

            <flux:sidebar.nav>
                <flux:sidebar.group expandable :heading="__('Gestión de cambios')" icon="arrows-right-left" class="grid">
                    <flux:sidebar.item
                        icon="document-text"
                        :href="route('scheduling.changes.reasons')"
                        :current="request()->routeIs('scheduling.changes.reasons')"
                        wire:navigate
                    >
                        {{ __('Motivos') }}
                    </flux:sidebar.item>

                    <flux:sidebar.item
                        icon="document-text"
                        :href="route('scheduling.changes.index')"
                        :current="request()->routeIs('scheduling.changes.index')"
                        wire:navigate
                    >
                        {{ __('Cambios') }}
                    </flux:sidebar.item>
                </flux:sidebar.group>
            </flux:sidebar.nav>

            <flux:spacer />

            <x-desktop-user-menu class="hidden lg:block" :name="auth()->user()->name" />
        </flux:sidebar>

        <!-- Mobile User Menu -->
        <flux:header class="lg:hidden">
            <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

            <flux:spacer />

            <flux:dropdown position="top" align="end">
                <flux:profile
                    :initials="auth()->user()->initials()"
                    icon-trailing="chevron-down"
                />

                <flux:menu>
                    <flux:menu.radio.group>
                        <div class="p-0 text-sm font-normal">
                            <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                                <flux:avatar
                                    :name="auth()->user()->name"
                                    :initials="auth()->user()->initials()"
                                />

                                <div class="grid flex-1 text-start text-sm leading-tight">
                                    <flux:heading class="truncate">{{ auth()->user()->name }}</flux:heading>
                                    <flux:text class="truncate">{{ auth()->user()->email }}</flux:text>
                                </div>
                            </div>
                        </div>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <flux:menu.radio.group>
                        <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate>
                            {{ __('Settings') }}
                        </flux:menu.item>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                        @csrf
                        <flux:menu.item
                            as="button"
                            type="submit"
                            icon="arrow-right-start-on-rectangle"
                            class="w-full cursor-pointer"
                            data-test="logout-button"
                        >
                            {{ __('Log out') }}
                        </flux:menu.item>
                    </form>
                </flux:menu>
            </flux:dropdown>
        </flux:header>

        {{ $slot }}

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @stack('scripts')
        @fluxScripts
    </body>
</html>
