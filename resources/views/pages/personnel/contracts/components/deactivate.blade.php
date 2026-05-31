<flux:modal name="confirm-deactivate" class="md:w-100">
    <div class="space-y-5">
        <div class="flex items-start gap-4 px-6 pt-4">
            <div class="shrink-0 w-10 h-10 rounded-full bg-red-100 flex items-center justify-center">
                <svg class="h-5 w-5 text-[#E53935]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </div>
            <div>
                <flux:heading size="lg" class="text-[#E53935]">Desactivar contrato</flux:heading>
                <flux:text class="mt-1 text-sm text-[#666666]">¿Estas seguro de que deseas desactivar este contrato?</flux:text>
            </div>
        </div>
        <div class="px-6 py-4 bg-[#F5F5F5] border-t border-[#E0E0E0] flex justify-end gap-3">
            <flux:button x-on:click="Flux.modal('confirm-deactivate').close()" type="button" variant="ghost" class="text-[#333333]">Cancelar</flux:button>
            <flux:button wire:click="deactivate" variant="danger" class="bg-[#E53935] text-white hover:bg-[#C62828]">Desactivar</flux:button>
        </div>
    </div>
</flux:modal>
