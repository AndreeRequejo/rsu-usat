<flux:modal name="mantenimiento-form" wire:close="closeMantForm" class="md:w-130">
    <form wire:submit="save" class="space-y-6" novalidate>
        <div>
            <flux:heading size="lg">
                {{ $mantEditingId ? __('Editar mantenimiento') : __('Nuevo mantenimiento') }}
            </flux:heading>
            <flux:text class="mt-2">
                {{ __('Ingrese el nombre y el rango de fechas del mantenimiento.') }}
            </flux:text>
        </div>

        <flux:input
            wire:model.live="mantNombre"
            :label="__('Nombre del Mantenimiento')"
            placeholder="{{ __('Ej. Mantenimiento Anual 2025') }}"
        />

        <div class="grid gap-4 sm:grid-cols-2">
            <flux:input type="date" wire:model.live="mantFechaInicio" :label="__('Fecha de Inicio')" />
            <flux:input type="date" wire:model.live="mantFechaFin" :label="__('Fecha de Fin')" />
        </div>

        <div class="flex justify-end gap-2 pt-2">
            <flux:modal.close>
                <flux:button type="button" variant="ghost" wire:click="closeMantForm" class="text-[#333333]">
                    {{ __('Cancelar') }}
                </flux:button>
            </flux:modal.close>
            <flux:button type="submit" variant="primary" class="bg-[#2E8B57] text-white hover:bg-[#257046]">
                {{ $mantEditingId ? __('Actualizar') : __('Guardar') }}
            </flux:button>
        </div>
    </form>
</flux:modal>
