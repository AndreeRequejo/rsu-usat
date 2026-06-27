@php
    $days = config('maintenance.days_of_week', []);
    $types = config('maintenance.types', []);
@endphp

<table class="w-full">
    <thead>
        <tr class="bg-[#2E8B57] text-white text-xs font-bold uppercase tracking-wider">
            <th class="px-4 py-3 text-left">{{ __('Día') }}</th>
            <th class="px-4 py-3 text-left">{{ __('Vehículo') }}</th>
            <th class="px-4 py-3 text-left">{{ __('Responsable') }}</th>
            <th class="px-4 py-3 text-center">{{ __('Tipo') }}</th>
            <th class="px-4 py-3 text-center">{{ __('Hora Inicio') }}</th>
            <th class="px-4 py-3 text-center">{{ __('Hora Fin') }}</th>
            <th class="px-4 py-3 text-center">{{ __('Acciones') }}</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($this->schedules as $i => $schedule)
            <tr wire:key="schedule-{{ $schedule->id }}"
                class="{{ $i % 2 === 0 ? 'bg-white' : 'bg-[#A5D6A7]/20' }} border-b border-[#A5D6A7] hover:bg-[#A5D6A7]/30 transition">
                <td class="px-4 py-3 text-sm font-medium">{{ $days[$schedule->day_of_week] ?? $schedule->day_of_week }}</td>
                <td class="px-4 py-3 text-sm">{{ $schedule->vehicle->name ?? '-' }}</td>
                <td class="px-4 py-3 text-sm">{{ $schedule->responsible->first_name . ' ' . $schedule->responsible->last_name ?? '-' }}</td>
                <td class="px-4 py-3 text-sm text-center">
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
                        {{ $schedule->maintenance_type === 'preventive' ? 'bg-blue-100 text-blue-800' :
                           ($schedule->maintenance_type === 'cleaning' ? 'bg-green-100 text-green-800' : 'bg-orange-100 text-orange-800') }}">
                        {{ $types[$schedule->maintenance_type] ?? $schedule->maintenance_type }}
                    </span>
                </td>
                <td class="px-4 py-3 text-sm text-center">{{ $schedule->start_time }}</td>
                <td class="px-4 py-3 text-sm text-center">{{ $schedule->end_time }}</td>
                <td class="px-4 py-3 text-sm text-center">
                    <div class="flex items-center justify-center gap-2">
                        <button
                            wire:click="openDetail({{ $schedule->id }})"
                            class="p-1.5 text-[#1976D2] hover:bg-blue-50 rounded-lg cursor-pointer transition"
                            title="{{ __('Ver días') }}"
                        >
                            <flux:icon.eye class="w-4 h-4" />
                        </button>
                        <button
                            wire:click="openEdit({{ $schedule->id }})"
                            class="p-1.5 text-[#2E8B57] hover:bg-[#A5D6A7]/30 rounded-lg cursor-pointer transition"
                            title="{{ __('Editar') }}"
                        >
                            <flux:icon.pencil-square class="w-4 h-4" />
                        </button>
                        <button
                            wire:click="confirmDelete({{ $schedule->id }})"
                            class="p-1.5 text-[#E53935] hover:bg-red-50 rounded-lg cursor-pointer transition"
                            title="{{ __('Eliminar') }}"
                        >
                            <flux:icon.trash class="w-4 h-4" />
                        </button>
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="7" class="px-4 py-10 text-center text-sm text-[#666666]">
                    {{ __('No se encontraron horarios programados.') }}
                </td>
            </tr>
        @endforelse
    </tbody>
</table>
<div class="px-4 py-3 border-t border-[#A5D6A7]">{{ $this->schedules->links() }}</div>
