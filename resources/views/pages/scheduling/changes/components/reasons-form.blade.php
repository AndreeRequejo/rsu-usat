<flux:modal
    name="reason-form"
    class="w-[500px] max-w-[98vw] max-h-[95vh] overflow-hidden flex flex-col"
    wire:close="closeFormModal"
>
    <div class="flex flex-col h-full">
        <div class="px-6 py-4 border-b border-[#A5D6A7] shrink-0 flex items-center justify-between" style="background-color: #1976D2;">
            <div class="flex items-center gap-2 text-white">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                </svg>
                <flux:heading size="lg" class="text-white">
                    {{ $editingId ? __('Editar Motivo') : __('Nuevo Motivo') }}
                </flux:heading>
            </div>
        </div>

        <div class="overflow-y-auto px-6 py-5 space-y-4 flex-1">
            <div>
                <label class="block text-sm font-medium text-[#333333] mb-1">
                    {{ __('Nombre') }} <span class="text-[#E53935]">*</span>
                </label>
                <input
                    type="text"
                    wire:model="name"
                    placeholder="{{ __('Nombre del motivo') }}"
                    class="w-full px-4 py-2.5 border border-[#A5D6A7] rounded-lg bg-white text-sm focus:outline-none focus:ring-2 focus:ring-[#2E8B57]"
                />
                @error('name') <span class="text-xs text-[#E53935] mt-1">{{ $message }}</span> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-[#333333] mb-1">{{ __('Descripcion') }}</label>
                <textarea
                    wire:model="description"
                    rows="3"
                    placeholder="{{ __('Agregue una descripcion') }}"
                    class="w-full px-4 py-2.5 border border-[#A5D6A7] rounded-lg bg-white text-sm focus:outline-none focus:ring-2 focus:ring-[#2E8B57]"
                ></textarea>
                @error('description') <span class="text-xs text-[#E53935] mt-1">{{ $message }}</span> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-[#333333] mb-1">{{ __('Estado') }}</label>
                <select wire:model="is_active" class="w-full px-4 py-2.5 border border-[#A5D6A7] rounded-lg bg-white text-sm focus:outline-none focus:ring-2 focus:ring-[#2E8B57]">
                    <option value="1">{{ __('Activo') }}</option>
                    <option value="0">{{ __('Inactivo') }}</option>
                </select>
            </div>
        </div>

        <div class="px-6 py-4 bg-[#F5F5F5] border-t border-[#E0E0E0] flex justify-end gap-3 shrink-0">
            <flux:button type="button" variant="ghost" wire:click="closeFormModal" class="text-[#333333]">
                {{ __('Cancelar') }}
            </flux:button>
            <flux:button
                type="button"
                variant="primary"
                class="bg-[#1976D2] text-white hover:bg-[#1565C0]"
                icon="check"
                wire:click="save"
            >
                {{ __('Guardar') }}
            </flux:button>
        </div>
    </div>
</flux:modal>
