<flux:modal name="maintenance-form" class="w-[500px] max-w-[98vw] max-h-[95vh] overflow-hidden flex flex-col"
    wire:close="closeFormModal">
    <div class="px-6 py-4 border-b border-[#A5D6A7] shrink-0">
        <flux:heading size="lg">
            {{ $editingId ? __('Editar Mantenimiento') : __('Nuevo Mantenimiento') }}
        </flux:heading>
    </div>

    <div class="overflow-y-auto px-6 py-5 space-y-4 flex-1">
        <div>
            <label class="block text-sm font-medium text-[#333333] mb-1">{{ __('Nombre') }} *</label>
            <input
                type="text"
                wire:model="name"
                class="w-full px-4 py-2.5 border border-[#A5D6A7] rounded-lg bg-white text-sm focus:outline-none focus:ring-2 focus:ring-[#2E8B57]"
                placeholder="{{ __('Ej: Diciembre 2025') }}"
            />
            @error('name')
                <p class="mt-1 text-xs text-[#E53935]">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-[#333333] mb-1">{{ __('Fecha de Inicio') }} *</label>
            <input
                type="date"
                wire:model="start_date"
                class="w-full px-4 py-2.5 border border-[#A5D6A7] rounded-lg bg-white text-sm focus:outline-none focus:ring-2 focus:ring-[#2E8B57]"
            />
            @error('start_date')
                <p class="mt-1 text-xs text-[#E53935]">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-[#333333] mb-1">{{ __('Fecha de Fin') }} *</label>
            <input
                type="date"
                wire:model="end_date"
                class="w-full px-4 py-2.5 border border-[#A5D6A7] rounded-lg bg-white text-sm focus:outline-none focus:ring-2 focus:ring-[#2E8B57]"
            />
            @error('end_date')
                <p class="mt-1 text-xs text-[#E53935]">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <div class="px-6 py-4 bg-[#F5F5F5] border-t border-[#E0E0E0] flex justify-end gap-3 shrink-0">
        <flux:button type="button" variant="ghost" wire:click="closeFormModal" class="cursor-pointer">
            {{ __('Cancelar') }}
        </flux:button>
        <flux:button type="button" variant="primary" wire:click="save" icon="check"
            class="bg-[#2E8B57]! text-white cursor-pointer hover:bg-[#257046]!">
            {{ __('Guardar') }}
        </flux:button>
    </div>
</flux:modal>
