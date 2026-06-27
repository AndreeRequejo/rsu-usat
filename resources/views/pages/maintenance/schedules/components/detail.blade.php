@php
    $days = config('maintenance.days_of_week', []);
    $types = config('maintenance.types', []);
@endphp

<flux:modal name="detail-view-modal" class="w-[700px] max-w-[98vw] max-h-[90vh] overflow-hidden flex flex-col"
    wire:close="closeDetailModal">
    @if ($this->detailSchedule)
    <div class="px-6 py-4 border-b border-[#A5D6A7] shrink-0">
        <div class="flex items-center justify-between">
            <div>
                <flux:heading size="lg">
                    {{ __('MANT.') }} {{ strtoupper($this->detailSchedule->maintenance->name) }}
                </flux:heading>
                <p class="text-sm text-[#666666] mt-1">
                    {{ strtoupper($days[$this->detailSchedule->day_of_week] ?? '') }} —
                    {{ $this->detailSchedule->vehicle->name ?? '-' }}
                </p>
                <p class="text-xs text-[#999999] mt-0.5">
                    {{ __('Responsable:') }} {{ optional($this->detailSchedule->responsible)->first_name . ' ' . optional($this->detailSchedule->responsible)->last_name }} |
                    {{ __('Tipo:') }} {{ $types[$this->detailSchedule->maintenance_type] ?? '' }} |
                    {{ __('Horario:') }} {{ $this->detailSchedule->start_time }} - {{ $this->detailSchedule->end_time }}
                </p>
            </div>
        </div>
    </div>

    <div class="overflow-y-auto flex-1 px-6 py-4">
        <table class="w-full">
            <thead>
                <tr class="bg-[#2E8B57] text-white text-xs font-bold uppercase tracking-wider">
                    <th class="px-4 py-3 text-left">{{ __('Fecha') }}</th>
                    <th class="px-4 py-3 text-left">{{ __('Observación') }}</th>
                    <th class="px-4 py-3 text-center">{{ __('Imagen') }}</th>
                    <th class="px-4 py-3 text-center">{{ __('Estado') }}</th>
                    <th class="px-4 py-3 text-center">{{ __('Editar') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($this->detailRecords as $i => $detail)
                    <tr wire:key="detail-{{ $detail->id }}"
                        class="{{ $i % 2 === 0 ? 'bg-white' : 'bg-[#A5D6A7]/20' }} border-b border-[#A5D6A7] hover:bg-[#A5D6A7]/30 transition">
                        <td class="px-4 py-3 text-sm font-medium">{{ $detail->date->format('d/m/Y') }}</td>
                        <td class="px-4 py-3 text-sm text-[#666666] max-w-[200px] truncate">
                            {{ $detail->observation ?? '-' }}
                        </td>
                        <td class="px-4 py-3 text-sm text-center">
                            @if ($detail->image_path)
                                <button
                                    wire:click="openImageModal('{{ $detail->image_path }}')"
                                    class="text-[#1976D2] hover:underline text-xs cursor-pointer"
                                >
                                    {{ __('Ver') }}
                                </button>
                            @else
                                <span class="text-[#999999] text-xs">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-sm text-center">
                            @if ($detail->completed === true)
                                <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-green-100">
                                    <flux:icon.check class="w-4 h-4 text-green-600" />
                                </span>
                            @elseif ($detail->completed === false)
                                <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-red-100">
                                    <flux:icon.x-mark class="w-4 h-4 text-red-600" />
                                </span>
                            @else
                                <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-gray-100">
                                    <flux:icon.minus class="w-4 h-4 text-gray-400" />
                                </span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-sm text-center">
                            <button
                                wire:click="openDetailEdit({{ $detail->id }})"
                                class="p-1.5 text-[#2E8B57] hover:bg-[#A5D6A7]/30 rounded-lg cursor-pointer transition"
                                title="{{ __('Editar') }}"
                            >
                                <flux:icon.pencil-square class="w-4 h-4" />
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-10 text-center text-sm text-[#666666]">
                            {{ __('No hay días generados para este horario.') }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @endif
</flux:modal>

{{-- Detail Edit Modal --}}
<flux:modal name="detail-edit-form" class="w-[500px] max-w-[98vw] max-h-[95vh] overflow-hidden flex flex-col"
    wire:close="closeDetailEditModal">
    <div class="px-6 py-4 border-b border-[#A5D6A7] shrink-0">
        <flux:heading size="lg">{{ __('Editar Detalle') }}</flux:heading>
    </div>

    <div class="overflow-y-auto px-6 py-5 space-y-4 flex-1">
            <div>
                <label class="block text-sm font-medium text-[#333333] mb-1">{{ __('Observación') }}</label>
                <textarea
                    wire:model="detail_observation"
                    rows="3"
                    class="w-full px-4 py-2.5 border border-[#A5D6A7] rounded-lg bg-white text-sm focus:outline-none focus:ring-2 focus:ring-[#2E8B57]"
                    placeholder="{{ __('Ingrese una observación...') }}"
                ></textarea>
                @error('detail_observation')
                    <p class="mt-1 text-xs text-[#E53935]">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-[#333333] mb-1">{{ __('Imagen') }}</label>
                <input
                    type="file"
                    wire:model="detail_image"
                    accept="image/*"
                    class="w-full px-4 py-2.5 border border-[#A5D6A7] rounded-lg bg-white text-sm focus:outline-none focus:ring-2 focus:ring-[#2E8B57] file:mr-3 file:py-1 file:px-3 file:rounded-lg file:border-0 file:text-sm file:bg-[#2E8B57] file:text-white"
                />
                @error('detail_image')
                    <p class="mt-1 text-xs text-[#E53935]">{{ $message }}</p>
                @enderror

                @if ($detail_image)
                    <div class="mt-2">
                        <img src="{{ $detail_image->temporaryUrl() }}" class="w-32 h-32 object-cover rounded-lg border border-[#A5D6A7]" />
                    </div>
                @endif
            </div>

            <div>
                <label class="block text-sm font-medium text-[#333333] mb-1">{{ __('Realizado') }}</label>
                <div class="flex gap-4 mt-1">
                    <label class="inline-flex items-center gap-2 cursor-pointer">
                        <input type="radio" wire:model="detail_completed" value="1"
                            class="w-4 h-4 text-[#2E8B57] border-[#A5D6A7] focus:ring-[#2E8B57]" />
                        <span class="text-sm">{{ __('Sí') }}</span>
                    </label>
                    <label class="inline-flex items-center gap-2 cursor-pointer">
                        <input type="radio" wire:model="detail_completed" value="0"
                            class="w-4 h-4 text-[#E53935] border-[#A5D6A7] focus:ring-[#E53935]" />
                        <span class="text-sm">{{ __('No') }}</span>
                    </label>
                </div>
                @error('detail_completed')
                    <p class="mt-1 text-xs text-[#E53935]">{{ $message }}</p>
                @enderror
            </div>
    </div>

    <div class="px-6 py-4 bg-[#F5F5F5] border-t border-[#E0E0E0] flex justify-end gap-3 shrink-0">
        <flux:button type="button" variant="ghost" wire:click="closeDetailEditModal" class="cursor-pointer">
            {{ __('Cancelar') }}
        </flux:button>
        <flux:button type="button" variant="primary" wire:click="saveDetail" icon="check"
            class="bg-[#2E8B57]! text-white cursor-pointer hover:bg-[#257046]!">
            {{ __('Guardar') }}
        </flux:button>
    </div>
</flux:modal>

{{-- Image View Modal --}}
<flux:modal name="image-view-modal" class="w-fit max-w-[90vw] max-h-[90vh]"
    wire:close="closeImageModal">
    <div class="p-4 flex flex-col items-center">
        <div class="flex items-center justify-between w-full mb-3">
            <flux:heading size="lg">{{ __('Imagen del Mantenimiento') }}</flux:heading>
        </div>
        @if ($this->viewImagePath)
            <img src="{{ asset('storage/' . $this->viewImagePath) }}"
                 class="max-w-full max-h-[75vh] object-contain rounded-lg border border-[#A5D6A7]"
                 alt="{{ __('Imagen de mantenimiento') }}" />
        @endif
    </div>
</flux:modal>
