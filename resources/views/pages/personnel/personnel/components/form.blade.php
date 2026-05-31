<flux:modal name="employee-form" class="md:w-[600px] max-h-[90vh] overflow-y-auto">
    <form wire:submit="save" class="space-y-5" novalidate>
        <div class="flex items-center justify-between px-6 pt-4 pb-2">
            <div>
                <flux:heading size="lg">{{ $editingId ? 'Editar Empleado' : 'Nuevo Empleado' }}</flux:heading>
                <flux:text class="mt-1 text-sm text-[#666666]">Complete todos los campos obligatorios.</flux:text>
            </div>
        </div>

        <div class="px-6 space-y-4">
            <div class="flex gap-4">
                <div class="flex-1">
                    <flux:input wire:model="dni" label="DNI" placeholder="8 digitos" maxlength="8" required />
                    @error('dni') <span class="text-xs text-[#E53935] mt-1">{{ $message }}</span> @enderror
                </div>
                <div class="flex-1">
                    <flux:select wire:model="employee_type_id" label="Tipo de Personal" required>
                        <option value="">Seleccionar...</option>
                        @foreach ($this->employeeTypes as $type)
                            <option value="{{ $type->id }}">{{ $type->name }}</option>
                        @endforeach
                    </flux:select>
                    @error('employee_type_id') <span class="text-xs text-[#E53935] mt-1">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="flex gap-4">
                <div class="flex-1">
                    <flux:input wire:model="first_name" label="Nombres" placeholder="Nombres del empleado" required />
                    @error('first_name') <span class="text-xs text-[#E53935] mt-1">{{ $message }}</span> @enderror
                </div>
                <div class="flex-1">
                    <flux:input wire:model="last_name" label="Apellidos" placeholder="Apellidos del empleado" required />
                    @error('last_name') <span class="text-xs text-[#E53935] mt-1">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="flex gap-4">
                <div class="flex-1">
                    <flux:input wire:model="birthdate" type="date" label="Fecha de Nacimiento" required />
                    @error('birthdate') <span class="text-xs text-[#E53935] mt-1">{{ $message }}</span> @enderror
                </div>
                <div class="flex-1">
                    <flux:input wire:model="phone" label="Telefono" placeholder="9 digitos (opcional)" maxlength="9" />
                    @error('phone') <span class="text-xs text-[#E53935] mt-1">{{ $message }}</span> @enderror
                </div>
            </div>

            <flux:input wire:model="email" type="email" label="Correo Electronico" placeholder="email@ejemplo.com" required />
            @error('email') <span class="text-xs text-[#E53935] mt-1">{{ $message }}</span> @enderror

            <flux:input wire:model="password" type="password" label="{{ $editingId ? 'Nueva Contrasena (opcional)' : 'Contrasena' }}" placeholder="{{ $editingId ? 'Solo si desea cambiarla' : 'Minimo 6 caracteres' }}" />
            @error('password') <span class="text-xs text-[#E53935] mt-1">{{ $message }}</span> @enderror

            <flux:input wire:model="address" label="Direccion" placeholder="Direccion (opcional)" />

            <div class="flex items-center gap-3">
                <label class="flex items-center cursor-pointer">
                    <input type="checkbox" wire:model="active" class="sr-only peer">
                    <div class="relative w-11 h-6 bg-[#CCCCCC] peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-[#2E8B57] rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:inset-s-0.5 after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-[#2E8B57]"></div>
                    <span class="ms-3 text-sm font-medium text-[#333333]">Activo</span>
                </label>
            </div>

            @include('pages.personnel.personnel.components.photo-field')
        </div>

        <div class="px-6 py-4 bg-[#F5F5F5] border-t border-[#E0E0E0] flex justify-end gap-3">
            <flux:button type="button" variant="ghost" wire:click="closeFormModal" class="text-[#333333]">Cancelar</flux:button>
            <flux:button type="submit" variant="primary" class="bg-[#2E8B57] text-white hover:bg-[#257046]">{{ $editingId ? 'Actualizar' : 'Guardar' }}</flux:button>
        </div>
    </form>
</flux:modal>