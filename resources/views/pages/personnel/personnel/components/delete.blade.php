<flux:modal name="confirm-delete" class="md:w-100">
    <div class="space-y-5">
        <div class="flex items-start gap-4 px-6 pt-4">
            <div class="shrink-0 w-10 h-10 rounded-full bg-red-100 flex items-center justify-center">
                <svg class="h-5 w-5 text-[#E53935]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
            </div>
            <div>
                <flux:heading size="lg" class="text-[#E53935]">Confirmar eliminacion</flux:heading>
                <flux:text class="mt-1 text-sm text-[#666666]">Esta seguro de que desea eliminar este empleado? Esta accion no se puede deshacer.</flux:text>
            </div>
        </div>
        <div class="px-6 py-4 bg-[#F5F5F5] border-t border-[#E0E0E0] flex justify-end gap-3">
            <flux:button x-on:click="Flux.modal('confirm-delete').close()" type="button" variant="ghost" class="text-[#333333]">Cancelar</flux:button>
            <flux:button wire:click="delete" variant="danger" class="bg-[#E53935] text-white hover:bg-[#C62828]">Eliminar</flux:button>
        </div>
    </div>
</flux:modal>