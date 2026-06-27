<flux:modal name="confirm-delete-maintenance" class="md:w-100">
    <div class="space-y-5">
        <div class="flex items-start gap-4 px-6 pt-4">
            <div class="shrink-0 w-10 h-10 rounded-full bg-red-100 flex items-center justify-center">
                <flux:icon.exclamation-triangle class="w-5 h-5 text-[#E53935]" />
            </div>
            <div>
                <flux:heading size="lg" class="text-[#E53935]">{{ __('Confirmar eliminación') }}</flux:heading>
                <flux:text class="mt-1 text-sm text-[#666666]">
                    {{ __('¿Estás seguro de que deseas eliminar este mantenimiento? Esta acción no se puede deshacer.') }}
                </flux:text>
            </div>
        </div>
        <div class="px-6 py-4 bg-[#F5F5F5] border-t border-[#E0E0E0] flex justify-end gap-3">
            <flux:button x-on:click="Flux.modal('confirm-delete-maintenance').close()" variant="ghost" class="cursor-pointer">
                {{ __('Cancelar') }}
            </flux:button>
            <flux:button wire:click="delete" variant="danger" class="bg-[#E53935] text-white cursor-pointer">
                {{ __('Eliminar') }}
            </flux:button>
        </div>
    </div>
</flux:modal>
