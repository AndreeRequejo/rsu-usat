<flux:modal name="detalle-view" wire:close="closeDetalleView" class="md:w-[760px] max-h-[90vh] overflow-y-auto">
    <div>
        <div class="flex items-center justify-between mb-4">
            <div>
                <flux:heading size="lg">
                    {{ __('Días generados del horario') }}
                </flux:heading>
            </div>
        </div>

        @if ($this->detalles->isNotEmpty())
        <div class="bg-white rounded-xl shadow-sm border border-[#A5D6A7] overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="bg-[#2E8B57] text-white text-xs font-bold uppercase tracking-wider">
                            <th class="px-4 py-3 text-left">{{ __('Fecha') }}</th>
                            <th class="px-4 py-3 text-left">{{ __('Observación') }}</th>
                            <th class="px-4 py-3 text-center">{{ __('Imagen') }}</th>
                            <th class="px-4 py-3 text-center">{{ __('Estado') }}</th>
                            <th class="px-4 py-3 text-center">{{ __('Acciones') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($this->detalles as $i => $det)
                            <tr wire:key="det-{{ $det->id }}"
                                class="{{ $i % 2 === 0 ? 'bg-white' : 'bg-[#A5D6A7]/20' }} border-b border-[#A5D6A7] hover:bg-[#A5D6A7]/30 transition">
                                <td class="px-4 py-3 text-sm font-semibold text-[#333333]">
                                    {{ $det->fecha->format('d/m/Y') }}
                                    <span class="text-xs text-[#666666] ml-1">({{ ucfirst($det->fecha->locale('es')->dayName) }})</span>
                                </td>
                                <td class="px-4 py-3 text-sm text-[#333333] max-w-[200px] truncate">
                                    {{ $det->observacion ?: '-' }}
                                </td>
                                <td class="px-4 py-3 text-center">
                                    @if ($det->imagen)
                                        <button wire:click="openImagePreview('{{ Storage::url($det->imagen) }}')"
                                            class="inline-flex items-center gap-1 text-[#1976D2] hover:text-[#1565C0] text-xs cursor-pointer">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                                            </svg>
                                            {{ __('Ver') }}
                                        </button>
                                    @else
                                        <span class="text-xs text-[#999999]">-</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                        {{ $det->realizado ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                        {{ $det->realizado ? __('Realizado') : __('No realizado') }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <button wire:click="openDetEdit({{ $det->id }})"
                                        class="inline-flex h-8 w-8 items-center justify-center rounded-md text-[#F4C542] hover:bg-[#F4C542]/20 transition"
                                        title="{{ __('Editar') }}" aria-label="{{ __('Editar') }}">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16.862 3.487a2.25 2.25 0 013.182 3.182l-9.193 9.193a4.5 4.5 0 01-1.897 1.13l-3.17 1.056 1.056-3.17a4.5 4.5 0 011.13-1.897l9.193-9.193z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.5 7.5L16.5 4.5" />
                                        </svg>
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            </div>
        @else
            <div class="py-10 text-center text-sm text-[#333333]">
                {{ __('No se generaron días para este horario.') }}
            </div>
        @endif

        <div class="flex justify-end pt-4 border-t border-[#E0E0E0] mt-4">
            <flux:button type="button" variant="ghost" wire:click="closeDetalleView" class="text-[#333333]">
                {{ __('Cerrar') }}
            </flux:button>
        </div>
    </div>
</flux:modal>
