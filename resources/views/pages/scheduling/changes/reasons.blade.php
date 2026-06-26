<div class="min-h-screen bg-white p-6 text-[#333333]">
    <div class="flex items-start justify-between mb-6">
        <div>
            <h1 class="text-3xl font-bold text-[#2E8B57]">
                {{ __('Lista de Motivos') }}
            </h1>
            <p class="text-sm text-[#333333] mt-1">
                {{ __('Administra los motivos de cambios en las programaciones.') }}
            </p>
        </div>
        <div class="flex gap-2">
            <flux:button
                wire:click="openCreate"
                variant="primary"
                icon="plus-circle"
                class="bg-[#2E8B57]! text-white cursor-pointer hover:bg-[#257046]!"
            >
                {{ __('Nuevo Motivo') }}
            </flux:button>
        </div>
    </div>

    {{-- Tabla --}}
    <div class="bg-white rounded-xl shadow-sm border border-[#A5D6A7] overflow-hidden">
        @include('pages.scheduling.changes.components.reasons-table')
    </div>

    @include('pages.scheduling.changes.components.reasons-form')
    @include('pages.scheduling.changes.components.reasons-delete')
</div>
