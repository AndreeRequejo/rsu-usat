<flux:modal name="detail-form" class="w-[550px] max-w-[98vw] max-h-[95vh] overflow-hidden flex flex-col"
    wire:close="closeFormModal">
    <div class="px-6 py-4 border-b border-[#A5D6A7] shrink-0">
        <flux:heading size="lg">
            {{ __('Editar Detalle') }}
        </flux:heading>
    </div>

    <div class="overflow-y-auto px-6 py-5 space-y-4 flex-1">
        <div>
            <label class="block text-sm font-medium text-[#333333] mb-1">{{ __('Observación') }}</label>
            <textarea
                wire:model="observation"
                rows="3"
                class="w-full px-4 py-2.5 border border-[#A5D6A7] rounded-lg bg-white text-sm focus:outline-none focus:ring-2 focus:ring-[#2E8B57]"
                placeholder="{{ __('Ingrese una observación...') }}"
            ></textarea>
            @error('observation')
                <p class="mt-1 text-xs text-[#E53935]">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-[#333333] mb-1">{{ __('Imagen') }}</label>
            <input
                type="file"
                wire:model="image"
                accept="image/*"
                class="w-full px-4 py-2.5 border border-[#A5D6A7] rounded-lg bg-white text-sm focus:outline-none focus:ring-2 focus:ring-[#2E8B57] file:mr-3 file:py-1 file:px-3 file:rounded-lg file:border-0 file:text-sm file:bg-[#2E8B57] file:text-white"
            />
            @error('image')
                <p class="mt-1 text-xs text-[#E53935]">{{ $message }}</p>
            @enderror

            @if ($image)
                <div class="mt-2">
                    <img src="{{ $image->temporaryUrl() }}" class="w-32 h-32 object-cover rounded-lg border border-[#A5D6A7]" />
                </div>
            @endif
        </div>

        <div>
            <label class="block text-sm font-medium text-[#333333] mb-1">{{ __('Realizado') }}</label>
            <div class="flex gap-4 mt-1">
                <label class="inline-flex items-center gap-2 cursor-pointer">
                    <input type="radio" wire:model="completed" value="1"
                        class="w-4 h-4 text-[#2E8B57] border-[#A5D6A7] focus:ring-[#2E8B57]" />
                    <span class="text-sm">{{ __('Sí') }}</span>
                </label>
                <label class="inline-flex items-center gap-2 cursor-pointer">
                    <input type="radio" wire:model="completed" value="0"
                        class="w-4 h-4 text-[#E53935] border-[#A5D6A7] focus:ring-[#E53935]" />
                    <span class="text-sm">{{ __('No') }}</span>
                </label>
            </div>
            @error('completed')
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
