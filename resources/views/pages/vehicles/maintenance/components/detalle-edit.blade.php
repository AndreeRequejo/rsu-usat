<flux:modal name="detalle-edit" wire:close="closeDetEdit" class="md:w-130">
    <form wire:submit="saveDet" class="space-y-6" novalidate>
        <div>
            <flux:heading size="lg">
                {{ __('Editar detalle del día') }}
            </flux:heading>
        </div>

        <div>
            <label class="block text-sm font-medium text-[#333333] mb-1">
                {{ __('Observación') }}
            </label>
            <textarea
                wire:model.live="detObservacion"
                rows="3"
                placeholder="{{ __('Agregue una observación...') }}"
                class="w-full px-4 py-2.5 border border-[#A5D6A7] rounded-lg bg-white text-sm focus:outline-none focus:ring-2 focus:ring-[#2E8B57] resize-none"
            ></textarea>
            @error('detObservacion') <span class="text-xs text-[#E53935] mt-1">{{ $message }}</span> @enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-[#333333] mb-1">
                {{ __('Imagen') }}
            </label>
            <input type="file" wire:model="detImagen" accept="image/*"
                class="text-sm file:mr-3 file:py-2 file:px-4 file:rounded-md file:border-0 file:bg-[#2E8B57] file:text-white hover:file:bg-[#257046]" />
            @error('detImagen') <span class="text-xs text-[#E53935] mt-1">{{ $message }}</span> @enderror

            @if ($detImagen)
                <div class="mt-2">
                    <img src="{{ $detImagen->temporaryUrl() }}"
                        class="h-24 w-24 rounded-lg object-cover border border-[#A5D6A7]" />
                </div>
            @endif

            @if ($detEditingId)
                @php
                    $det = \App\Models\DetalleHorarioMantenimiento::find($detEditingId);
                @endphp
                @if ($det && $det->imagen && !$detImagen)
                    <div class="mt-2 flex items-center gap-3">
                        <img src="{{ Storage::url($det->imagen) }}"
                            class="h-24 w-24 rounded-lg object-cover border border-[#A5D6A7]" />
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" wire:model="detRemoveImage" class="rounded border-[#A5D6A7] text-[#E53935] focus:ring-[#E53935]" />
                            <span class="text-sm text-[#E53935]">{{ __('Eliminar imagen actual') }}</span>
                        </label>
                    </div>
                @endif
            @endif
        </div>

        <div>
            <label class="block text-sm font-medium text-[#333333] mb-1">{{ __('Estado de realización') }}</label>
            <select
                wire:model.live="detRealizado"
                class="w-full px-4 py-2.5 border border-[#A5D6A7] rounded-lg bg-white text-sm focus:outline-none focus:ring-2 focus:ring-[#2E8B57]"
            >
                <option value="0">{{ __('No realizado') }}</option>
                <option value="1">{{ __('Realizado') }}</option>
            </select>
            @error('detRealizado') <span class="text-xs text-[#E53935] mt-1">{{ $message }}</span> @enderror
        </div>

        <div class="flex justify-end gap-2 pt-2">
            <flux:modal.close>
                <flux:button type="button" variant="ghost" wire:click="closeDetEdit" class="text-[#333333]">
                    {{ __('Cancelar') }}
                </flux:button>
            </flux:modal.close>
            <flux:button type="submit" variant="primary" class="bg-[#2E8B57] text-white hover:bg-[#257046]">
                {{ __('Actualizar') }}
            </flux:button>
        </div>
    </form>
</flux:modal>
