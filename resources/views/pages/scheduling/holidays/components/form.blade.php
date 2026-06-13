<flux:modal
    name="holiday-form"
    class="w-[500px] max-w-[98vw] max-h-[95vh] overflow-hidden flex flex-col"
    wire:close="closeFormModal"
>
    <div class="flex flex-col h-full">
        <div class="px-6 py-4 border-b border-[#A5D6A7] shrink-0 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" />
                </svg>
                <flux:heading size="lg">
                    {{ $editingId ? __('Editar Feriado') : __('Nuevo Dia Feriado') }}
                </flux:heading>
            </div>
        </div>

        <div class="overflow-y-auto px-6 py-5 space-y-4 flex-1">
            <div>
                <label class="block text-sm font-medium text-[#333333] mb-1">
                    {{ __('Fecha del Feriado') }} <span class="text-[#E53935]">*</span>
                </label>
                <input
                    type="date"
                    wire:model="date"
                    class="w-full px-4 py-2.5 border border-[#A5D6A7] rounded-lg bg-white text-sm focus:outline-none focus:ring-2 focus:ring-[#2E8B57]"
                />
                @if ($date)
                    @php
                        $dayName = \Carbon\Carbon::parse($date)->locale('es')->dayName;
                    @endphp
                    <p class="text-xs text-[#1976D2] mt-1 font-semibold">{{ __('Dia:') }} {{ ucfirst($dayName) }}</p>
                @else
                    <p class="text-xs text-[#1976D2] mt-1 font-semibold">{{ __('Dia: Seleccione una fecha') }}</p>
                @endif
                @error('date') <span class="text-xs text-[#E53935] mt-1">{{ $message }}</span> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-[#333333] mb-1">
                    {{ __('Descripcion') }} <span class="text-[#E53935]">*</span>
                </label>
                <input
                    type="text"
                    wire:model="name"
                    placeholder="{{ __('Descripcion del dia feriado') }}"
                    class="w-full px-4 py-2.5 border border-[#A5D6A7] rounded-lg bg-white text-sm focus:outline-none focus:ring-2 focus:ring-[#2E8B57]"
                />
                @error('name') <span class="text-xs text-[#E53935] mt-1">{{ $message }}</span> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-[#333333] mb-1">{{ __('Estado') }}</label>
                <select
                    wire:model="is_active"
                    class="w-full px-4 py-2.5 border border-[#A5D6A7] rounded-lg bg-white text-sm focus:outline-none focus:ring-2 focus:ring-[#2E8B57]"
                >
                    <option value="1">{{ __('Activo') }}</option>
                    <option value="0">{{ __('Inactivo') }}</option>
                </select>
                <p class="text-xs text-[#999999] mt-1">{{ __('Los feriados inactivos no se consideraran en las validaciones de programacion.') }}</p>
                @error('is_active') <span class="text-xs text-[#E53935] mt-1">{{ $message }}</span> @enderror
            </div>

            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                <div class="flex items-start gap-2">
                    <svg class="h-5 w-5 text-[#1976D2] shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" />
                    </svg>
                    <div>
                        <p class="text-sm font-semibold text-[#1976D2]">{{ __('Informacion:') }}</p>
                        <ul class="text-sm text-[#1976D2] mt-1 list-disc list-inside space-y-0.5">
                            <li>{{ __('Los dias feriados afectan la programacion de rutas.') }}</li>
                            <li>{{ __('Puede cargar los feriados oficiales de Peru usando el boton "Cargar Feriados Peru".') }}</li>
                            <li>{{ __('Los feriados inactivos no se consideran en las validaciones.') }}</li>
                        </ul>
                    </div>
                </div>
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
