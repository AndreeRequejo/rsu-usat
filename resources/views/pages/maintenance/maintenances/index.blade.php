<div class="min-h-screen bg-white p-6 text-[#333333]">
    <div class="flex items-start justify-between mb-6">
        <div>
            <h1 class="text-3xl font-bold text-[#2E8B57]">
                {{ __('Programaciones de Mantenimiento') }}
            </h1>
            <p class="text-sm text-[#333333] mt-1">
                {{ __('Administra los períodos de mantenimiento de vehículos.') }}
            </p>
        </div>
        <div class="flex gap-2">
            <flux:button
                wire:click="openCreate"
                variant="primary"
                icon="plus-circle"
                class="bg-[#2E8B57]! text-white cursor-pointer hover:bg-[#257046]!"
            >
                {{ __('Nueva programación de mantenimiento') }}
            </flux:button>
        </div>
    </div>

    {{-- Filtros --}}
    <div class="bg-white rounded-xl shadow-sm border border-[#A5D6A7] p-5 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-3 items-end">
            <div>
                <label class="block text-sm font-medium text-[#333333] mb-1">{{ __('Buscar') }}</label>
                <input
                    type="text"
                    wire:model.live="search"
                    placeholder="{{ __('Nombre del mantenimiento...') }}"
                    class="w-full px-4 py-2.5 border border-[#A5D6A7] rounded-lg bg-white text-sm focus:outline-none focus:ring-2 focus:ring-[#2E8B57]"
                />
            </div>
            <div class="flex gap-2">
                <flux:button
                    wire:click="$set('search', '')"
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
        @include('pages.maintenance.maintenances.components.table')
    </div>

    @include('pages.maintenance.maintenances.components.form')
    @include('pages.maintenance.maintenances.components.delete')
</div>
