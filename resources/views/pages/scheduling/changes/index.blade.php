<div class="min-h-screen bg-white p-6 text-[#333333]">
    <div class="flex items-start justify-between mb-6">
        <div>
            <h1 class="text-3xl font-bold text-[#2E8B57]">
                {{ __('Cambios de Programaciones') }}
            </h1>
            <p class="text-sm text-[#333333] mt-1">
                {{ __('Administra los cambios realizados a las programaciones de rutas.') }}
            </p>
        </div>
        <div class="flex gap-2">
            <flux:button
                wire:click="openCreate"
                variant="primary"
                icon="arrows-right-left"
                class="bg-[#1976D2] text-white cursor-pointer hover:bg-[#1565C0]!"
            >
                {{ __('Nuevo Cambio Masivo') }}
            </flux:button>
        </div>
    </div>

    {{-- Filtros --}}
    <div class="bg-white rounded-xl shadow-sm border border-[#A5D6A7] p-5 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-3 items-end">
            <div>
                <label class="block text-sm font-medium text-[#333333] mb-1">{{ __('Fecha de inicio') }}</label>
                <input
                    type="date"
                    wire:model.live="dateFrom"
                    class="w-full px-4 py-2.5 border border-[#A5D6A7] rounded-lg bg-white text-sm focus:outline-none focus:ring-2 focus:ring-[#2E8B57]"
                />
            </div>
            <div>
                <label class="block text-sm font-medium text-[#333333] mb-1">{{ __('Fecha de fin') }}</label>
                <input
                    type="date"
                    wire:model.live="dateTo"
                    class="w-full px-4 py-2.5 border border-[#A5D6A7] rounded-lg bg-white text-sm focus:outline-none focus:ring-2 focus:ring-[#2E8B57]"
                />
            </div>
            <div>
                <label class="block text-sm font-medium text-[#333333] mb-1">{{ __('Tipo de cambio') }}</label>
                <select
                    wire:model.live="typeFilter"
                    class="w-full px-4 py-2.5 border border-[#A5D6A7] rounded-lg bg-white text-sm focus:outline-none focus:ring-2 focus:ring-[#2E8B57]"
                >
                    <option value="">{{ __('Todos los tipos') }}</option>
                    <option value="turn">{{ __('Turno') }}</option>
                    <option value="vehicle">{{ __('Vehiculo') }}</option>
                    <option value="driver">{{ __('Conductor') }}</option>
                    <option value="helper">{{ __('Ocupante') }}</option>
                </select>
            </div>
            <div class="flex gap-2">
                <flux:button
                    wire:click="$refresh"
                    variant="filled"
                    icon="funnel"
                    class="bg-[#1976D2] text-white cursor-pointer hover:bg-[#1565C0]!"
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
        @include('pages.scheduling.changes.components.table')
    </div>

    @include('pages.scheduling.changes.components.form')
    @include('pages.scheduling.changes.components.delete')
    @include('pages.scheduling.changes.components.viewer')
</div>
