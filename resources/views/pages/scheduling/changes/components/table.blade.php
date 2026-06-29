<div class="overflow-x-auto">
    <div class="flex items-center justify-between px-4 py-3 border-b border-[#A5D6A7]">
        <div class="flex items-center gap-2 text-sm text-[#333333]">
            <span>{{ __('Mostrar') }}</span>
            <select wire:model.live="perPage" class="border border-[#A5D6A7] rounded px-2 py-1 text-sm focus:outline-none focus:ring-2 focus:ring-[#2E8B57]">
                <option value="10">10</option>
                <option value="25">25</option>
                <option value="50">50</option>
                <option value="100">100</option>
            </select>
            <span>{{ __('registros') }}</span>
        </div>
        <div class="relative">
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-[#999999]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
            <input
                type="text"
                wire:model.live.debounce.300ms="search"
                placeholder="{{ __('Buscar...') }}"
                class="pl-9 pr-4 py-2 border border-[#A5D6A7] rounded-lg bg-white text-sm focus:outline-none focus:ring-2 focus:ring-[#2E8B57] w-64"
            />
        </div>
    </div>

    <table class="w-full">
        <thead>
            <tr class="bg-[#2E8B57] text-white text-xs font-bold uppercase tracking-wider">
                <th class="px-4 py-3 text-left">{{ __('Tipo de cambio') }}</th>
                <th class="px-4 py-3 text-left">{{ __('Fecha cambio') }}</th>
                <th class="px-4 py-3 text-left">{{ __('Antes') }}</th>
                <th class="px-4 py-3 text-left">{{ __('Despues') }}</th>
                <th class="px-4 py-3 text-left">{{ __('Realizado por') }}</th>
                <th class="px-4 py-3 text-right">{{ __('Acciones') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($this->changes as $i => $change)
                <tr wire:key="change-{{ $change->id }}" class="{{ $i % 2 === 0 ? 'bg-white' : 'bg-[#A5D6A7]/20' }} border-b border-[#A5D6A7] hover:bg-[#A5D6A7]/30 transition">
                    <td class="px-4 py-3 text-center">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium text-white" style="background-color: {{ $change->type_badge_color }}">
                            {{ $change->type_label }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-sm text-[#333333]">
                        <div class="font-semibold">{{ $change->created_at->format('d/m/Y') }}</div>
                        <div class="text-xs text-[#666666]">{{ $change->created_at->format('H:i') }}</div>
                    </td>
                    <td class="px-4 py-3 text-sm text-[#333333]">
                        @if ($change->change_type === 'turn')
                            <span class="text-red-600">{{ $change->oldShift?->name ?? '-' }}</span>
                            <div class="text-xs text-[#666666]">{{ $change->oldShift?->hour_in }} - {{ $change->oldShift?->hour_out }}</div>
                        @elseif ($change->change_type === 'vehicle')
                            <span class="text-red-600">{{ $change->oldVehicle?->name ?? '-' }}</span>
                        @elseif (in_array($change->change_type, ['driver', 'helper']))
                            <span class="text-red-600">{{ ($change->oldPerson?->first_name ?? '') . ' ' . ($change->oldPerson?->last_name ?? '') }}</span>
                        @else
                            -
                        @endif
                    </td>
                    <td class="px-4 py-3 text-sm text-[#333333]">
                        @if ($change->change_type === 'turn')
                            <span class="text-green-600 font-semibold">{{ $change->newShift?->name ?? '-' }}</span>
                            <div class="text-xs text-[#666666]">{{ $change->newShift?->hour_in }} - {{ $change->newShift?->hour_out }}</div>
                        @elseif ($change->change_type === 'vehicle')
                            <span class="text-green-600 font-semibold">{{ $change->newVehicle?->name ?? '-' }}</span>
                        @elseif (in_array($change->change_type, ['driver', 'helper']))
                            <span class="text-green-600 font-semibold">{{ ($change->newPerson?->first_name ?? '') . ' ' . ($change->newPerson?->last_name ?? '') }}</span>
                        @else
                            -
                        @endif
                    </td>
                    <td class="px-4 py-3 text-sm text-[#333333]">
                        <div class="flex items-center gap-2">
                            <svg class="h-4 w-4 text-[#666666]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/>
                            </svg>
                            <div>
                                <div class="font-medium">{{ $change->user?->name ?? '-' }}</div>
                                <div class="text-xs text-[#666666]">{{ $change->user?->email ?? '' }}</div>
                            </div>
                        </div>
                    </td>
                    <td class="px-4 py-3">
                        <div class="flex justify-end gap-2">
                            <button wire:click="openView({{ $change->id }})" class="inline-flex h-8 w-8 items-center justify-center rounded-md text-[#1976D2] hover:bg-[#1976D2]/20 transition" title="Ver detalle" aria-label="Ver detalle">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 5.25 12 5.25c4.638 0 8.573 2.25 9.963 6.516.146.41.146.861 0 1.272C20.577 16.49 16.64 18.75 12 18.75c-4.638 0-8.573-2.25-9.963-6.516z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                            </button>
                            <button wire:click="confirmDelete({{ $change->id }})" class="inline-flex h-8 w-8 items-center justify-center rounded-md text-[#E53935] hover:bg-[#E53935]/20 transition" title="Eliminar" aria-label="Eliminar">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 7h12M9 7V5a1 1 0 011-1h4a1 1 0 011 1v2M7 7l1 12a2 2 0 002 2h4a2 2 0 002-2l1-12" />
                                </svg>
                            </button>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="px-4 py-10 text-center text-sm text-[#333333]">
                        {{ __('No hay cambios registrados.') }}
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
    <div class="px-4 py-3 border-t border-[#A5D6A7]">
        {{ $this->changes->links() }}
    </div>
</div>
