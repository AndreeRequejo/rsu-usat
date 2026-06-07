@once
    @push('styles')
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    @endpush
    @push('scripts')
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    @endpush
@endonce

<div class="min-h-screen bg-white p-6 text-[#333333]">
    <div class="flex items-start justify-between mb-6">
        <div>
            <h1 class="text-3xl font-bold text-[#2E8B57]">
                {{ __('Gestion de zonas') }}
            </h1>
            <p class="text-sm text-[#333333] mt-1">
                {{ __('Administra las zonas geograficas donde se realizaran las programaciones de recoleccion.') }}
            </p>
        </div>
        <div class="flex gap-2">
            <flux:button
                wire:click="openCreate"
                variant="primary"
                icon="plus-circle"
                class="bg-[#2E8B57]! text-white cursor-pointer hover:bg-[#257046]!"
            >
                {{ __('Nueva zona') }}
            </flux:button>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-[#A5D6A7] p-5 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            <div class="relative">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 h-5 w-5 text-[#333333]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <input
                    type="text"
                    wire:model.live.debounce.300ms="search"
                    placeholder="{{ __('Buscar por nombre, distrito, provincia...') }}"
                    class="w-full pl-10 pr-4 py-2.5 border border-[#A5D6A7] rounded-lg bg-white text-sm focus:outline-none focus:ring-2 focus:ring-[#2E8B57]"
                />
            </div>
            <div>
                <select
                    wire:model.live="statusFilter"
                    class="w-full px-4 py-2.5 border border-[#A5D6A7] rounded-lg bg-white text-sm focus:outline-none focus:ring-2 focus:ring-[#2E8B57]"
                >
                    <option value="">{{ __('Todos los estados') }}</option>
                    <option value="active">{{ __('Activo') }}</option>
                    <option value="inactive">{{ __('Inactivo') }}</option>
                </select>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-[#A5D6A7] overflow-hidden">
        @include('pages.scheduling.zones.components.table')
    </div>

    @include('pages.scheduling.zones.components.form')
    @include('pages.scheduling.zones.components.delete')
    @include('pages.scheduling.zones.components.viewer-modal')
</div>