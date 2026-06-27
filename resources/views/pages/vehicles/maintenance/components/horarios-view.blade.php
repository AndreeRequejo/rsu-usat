<div class="flex items-start justify-between mb-6">
    <div>
        <div class="flex items-center gap-2 mb-1">
            <button wire:click="backToMantenimientos"
                class="inline-flex h-8 w-8 items-center justify-center rounded-md text-[#2E8B57] hover:bg-[#A5D6A7]/30 transition"
                title="{{ __('Volver') }}" aria-label="{{ __('Volver') }}">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
                </svg>
            </button>
            <h1 class="text-3xl font-bold text-[#2E8B57]">
                {{ __('Horarios de mantenimiento') }}
            </h1>
        </div>
        <p class="text-sm text-[#333333] mt-1 ml-10">
            @if ($this->currentMantenimiento)
                <span class="font-semibold">{{ $this->currentMantenimiento->nombre }}</span>
                &middot; {{ $this->currentMantenimiento->fecha_inicio->format('d/m/Y') }} - {{ $this->currentMantenimiento->fecha_fin->format('d/m/Y') }}
            @endif
        </p>
    </div>

    <flux:button
        wire:click="openHorCreate"
        variant="primary"
        icon="plus-circle"
        class="bg-[#2E8B57]! text-white cursor-pointer hover:bg-[#257046]!"
    >
        {{ __('Nuevo Horario') }}
    </flux:button>
</div>

<div class="bg-white rounded-xl shadow-sm border border-[#A5D6A7] p-5 mb-6">
    <label class="block text-sm font-medium text-[#333333] mb-2">
        {{ __('Buscar por vehículo, responsable y día') }}
    </label>
    <div class="flex gap-3">
        <div class="relative flex-1">
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 h-5 w-5 text-[#333333]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
            <input
                type="text"
                wire:model.live.debounce.300ms="search"
                placeholder="{{ __('Buscar...') }}"
                class="w-full pl-10 pr-4 py-2.5 border border-[#A5D6A7] rounded-lg bg-white text-sm focus:outline-none focus:ring-2 focus:ring-[#2E8B57]"
            />
        </div>
    </div>
</div>

<div class="bg-white rounded-xl shadow-sm border border-[#A5D6A7] overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead>
                <tr class="bg-[#2E8B57] text-white text-xs font-bold uppercase tracking-wider">
                    <th class="px-6 py-4 text-left">{{ __('Vehículo') }}</th>
                    <th class="px-6 py-4 text-left">{{ __('Responsable') }}</th>
                    <th class="px-6 py-4 text-left">{{ __('Tipo') }}</th>
                    <th class="px-6 py-4 text-left">{{ __('Día') }}</th>
                    <th class="px-6 py-4 text-left">{{ __('Horario') }}</th>
                    <th class="px-6 py-4 text-center">{{ __('Días Gen.') }}</th>
                    <th class="px-6 py-4 text-right">{{ __('Acciones') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($this->horarios as $i => $hor)
                    <tr wire:key="hor-{{ $hor->id }}"
                        class="{{ $i % 2 === 0 ? 'bg-white' : 'bg-[#A5D6A7]/20' }} border-b border-[#A5D6A7] hover:bg-[#A5D6A7]/30 transition">
                        <td class="px-6 py-4 text-sm font-semibold text-[#333333] uppercase">
                            {{ $hor->vehiculo?->name ?? __('Sin vehículo') }}
                        </td>
                        <td class="px-6 py-4 text-sm text-[#333333]">
                            {{ $hor->responsable?->first_name }} {{ $hor->responsable?->last_name }}
                        </td>
                        <td class="px-6 py-4">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                {{ $hor->tipo === 'Preventivo' ? 'bg-green-100 text-green-800' : '' }}
                                {{ $hor->tipo === 'Limpieza' ? 'bg-blue-100 text-blue-800' : '' }}
                                {{ $hor->tipo === 'Reparacion' ? 'bg-yellow-100 text-yellow-800' : '' }}">
                                {{ $hor->tipo }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-sm text-[#333333]">
                            {{ $hor->dia_semana }}
                        </td>
                        <td class="px-6 py-4 text-sm text-[#333333]">
                            {{ \Carbon\Carbon::parse($hor->hora_inicio)->format('H:i') }} - {{ \Carbon\Carbon::parse($hor->hora_fin)->format('H:i') }}
                        </td>
                        <td class="px-6 py-4 text-center">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                                {{ $hor->detalles_count }} {{ __('día(s)') }}
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex justify-end gap-2">
                                <button wire:click="showDetalle({{ $hor->id }})"
                                    class="inline-flex h-8 w-8 items-center justify-center rounded-md text-[#1976D2] hover:bg-[#1976D2]/20 transition"
                                    title="{{ __('Ver detalle') }}" aria-label="{{ __('Ver detalle') }}">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                </button>
                                <button wire:click="openHorEdit({{ $hor->id }})"
                                    class="inline-flex h-8 w-8 items-center justify-center rounded-md text-[#F4C542] hover:bg-[#F4C542]/20 transition"
                                    title="{{ __('Editar') }}" aria-label="{{ __('Editar') }}">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16.862 3.487a2.25 2.25 0 013.182 3.182l-9.193 9.193a4.5 4.5 0 01-1.897 1.13l-3.17 1.056 1.056-3.17a4.5 4.5 0 011.13-1.897l9.193-9.193z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.5 7.5L16.5 4.5" />
                                    </svg>
                                </button>
                                <button wire:click="confirmHorDelete({{ $hor->id }})"
                                    class="inline-flex h-8 w-8 items-center justify-center rounded-md text-[#E53935] hover:bg-[#E53935]/20 transition"
                                    title="{{ __('Eliminar') }}" aria-label="{{ __('Eliminar') }}">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 7h12M9 7V5a1 1 0 011-1h4a1 1 0 011 1v2M7 7l1 12a2 2 0 002 2h4a2 2 0 002-2l1-12" />
                                    </svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-6 py-10 text-center text-sm text-[#333333]">
                            {{ __('No hay horarios registrados para este mantenimiento.') }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
