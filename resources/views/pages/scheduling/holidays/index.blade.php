<div class="bg-white p-6 text-[#333333]">
    <div class="flex items-start justify-between mb-6">
        <div>
            <h1 class="text-3xl font-bold text-[#2E8B57]">
                {{ __('Listado de Feriados') }}
            </h1>
            <p class="text-sm text-[#333333] mt-1">
                {{ __('Administra los dias feriados que afectan la programacion de rutas.') }}
            </p>
        </div>
        <div class="flex gap-2 items-end">
            <div>
                <label class="block text-xs font-medium text-[#333333] mb-1">{{ __('Año') }}</label>
                <input
                    type="number"
                    wire:model="loadYear"
                    min="2000"
                    max="2100"
                    class="w-24 px-3 py-2.5 border border-[#A5D6A7] rounded-lg bg-white text-sm focus:outline-none focus:ring-2 focus:ring-[#2E8B57]"
                />
            </div>
            <flux:button
                wire:click="loadPeruHolidays"
                variant="filled"
                icon="arrow-down-tray"
                class="text-white cursor-pointer hover:bg-gray-300!"
            >
                {{ __('Cargar Feriados Peru') }}
            </flux:button>
            <flux:button
                wire:click="openCreate"
                variant="primary"
                icon="plus-circle"
                class="bg-[#2E8B57]! text-white cursor-pointer hover:bg-[#257046]!"
            >
                {{ __('Nuevo Feriado') }}
            </flux:button>
        </div>
    </div>

    {{-- Estadísticas --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="rounded-xl p-5 text-white shadow-sm" style="background-color: #2E8B57;">
            <div class="text-3xl font-bold">{{ $this->stats['total'] }}</div>
            <div class="text-sm opacity-90">{{ __('Total Feriados') }}</div>
        </div>
        <div class="rounded-xl p-5 text-white shadow-sm" style="background-color: #4CAF50;">
            <div class="text-3xl font-bold">{{ $this->stats['active'] }}</div>
            <div class="text-sm opacity-90">{{ __('Feriados Activos') }}</div>
        </div>
        <div class="rounded-xl p-5 text-white shadow-sm" style="background-color: #F4C542;">
            <div class="text-3xl font-bold">{{ $this->stats['upcoming'] }}</div>
            <div class="text-sm opacity-90">{{ __('Proximos Feriados') }}</div>
        </div>
        <div class="rounded-xl p-5 text-white shadow-sm" style="background-color: #00BCD4;">
            <div class="text-3xl font-bold">{{ $this->stats['current_year'] }}</div>
            <div class="text-sm opacity-90">{{ __('Anio Actual') }}</div>
        </div>
    </div>

    {{-- Filtros --}}
    <div class="bg-white rounded-xl shadow-sm border border-[#A5D6A7] p-5 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-3 items-end">
            <div>
                <label class="block text-sm font-medium text-[#333333] mb-1">{{ __('Fecha inicio') }}</label>
                <input
                    type="date"
                    wire:model.live="dateFrom"
                    class="w-full px-4 py-2.5 border border-[#A5D6A7] rounded-lg bg-white text-sm focus:outline-none focus:ring-2 focus:ring-[#2E8B57]"
                />
            </div>
            <div>
                <label class="block text-sm font-medium text-[#333333] mb-1">{{ __('Fecha fin') }}</label>
                <input
                    type="date"
                    wire:model.live="dateTo"
                    class="w-full px-4 py-2.5 border border-[#A5D6A7] rounded-lg bg-white text-sm focus:outline-none focus:ring-2 focus:ring-[#2E8B57]"
                />
            </div>
            <div>
                <label class="block text-sm font-medium text-[#333333] mb-1">{{ __('Estado') }}</label>
                <select
                    wire:model.live="statusFilter"
                    class="w-full px-4 py-2.5 border border-[#A5D6A7] rounded-lg bg-white text-sm focus:outline-none focus:ring-2 focus:ring-[#2E8B57]"
                >
                    <option value="">{{ __('Todos') }}</option>
                    <option value="active">{{ __('Activo') }}</option>
                    <option value="inactive">{{ __('Inactivo') }}</option>
                </select>
            </div>
            <div class="flex gap-2">
                <flux:button
                    wire:click="$refresh"
                    variant="filled"
                    icon="magnifying-glass"
                    class="bg-[#2E8B57] text-white cursor-pointer hover:bg-[#257046]!"
                >
                    {{ __('Filtrar') }}
                </flux:button>
                <flux:button
                    wire:click="resetFilters"
                    variant="ghost"
                    icon="x-mark"
                    class="text-[#333333] cursor-pointer"
                >
                    {{ __('Limpiar') }}
                </flux:button>
            </div>
        </div>
    </div>

    {{-- Tabla --}}
    <div class="bg-white rounded-xl shadow-sm border border-[#A5D6A7] overflow-hidden">
        @include('pages.scheduling.holidays.components.table')
    </div>

    @include('pages.scheduling.holidays.components.form')
    @include('pages.scheduling.holidays.components.delete')
</div>
