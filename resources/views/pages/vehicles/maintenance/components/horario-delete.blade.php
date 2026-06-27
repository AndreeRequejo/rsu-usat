<flux:modal name="confirm-delete-horario" class="md:w-[400px]">
    <div class="space-y-6">
        <div>
            <flux:heading size="lg" class="text-red-500">
                {{ __('Confirmar eliminación') }}
            </flux:heading>
            <flux:text class="mt-2 text-sm text-[#333333]">
                {{ __('¿Estás seguro de que deseas eliminar este horario? Todos los días generados asociados serán eliminados. Esta acción no se puede deshacer.') }}
            </flux:text>
        </div>

        <div class="flex gap-3 justify-end pt-4 border-t border-[#E0E0E0]">
            <flux:button x-on:click="Flux.modal('confirm-delete-horario').close()" type="button">
                {{ __('Cancelar') }}
            </flux:button>
            <flux:button wire:click="deleteHor" variant="danger" class="bg-[#E53935] text-white">
                {{ __('Eliminar') }}
            </flux:button>
        </div>
    </div>
</flux:modal>
