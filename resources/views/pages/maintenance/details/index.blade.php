@php
    $days = config('maintenance.days_of_week', []);
    $types = config('maintenance.types', []);
    $schedule = $this->schedule;
@endphp

<div class="min-h-screen bg-white p-6 text-[#333333]">
    <div class="flex items-start justify-between mb-6">
        <div>
            <h1 class="text-3xl font-bold text-[#2E8B57]">
                {{ __('MANT.') }} {{ strtoupper($schedule->maintenance->name) }}
            </h1>
            <p class="text-sm text-[#333333] mt-1">
                {{ strtoupper($days[$schedule->day_of_week] ?? $schedule->day_of_week) }} —
                {{ $schedule->vehicle->name ?? '-' }}
            </p>
            <p class="text-xs text-[#666666] mt-1">
                {{ __('Responsable:') }} {{ $schedule->responsible->first_name . ' ' . $schedule->responsible->last_name ?? '-' }} |
                {{ __('Tipo:') }} {{ $types[$schedule->maintenance_type] ?? $schedule->maintenance_type }} |
                {{ __('Horario:') }} {{ $schedule->start_time->format('H:i') }} - {{ $schedule->end_time->format('H:i') }}
            </p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('maintenance.schedules.index', $schedule->maintenance_id) }}" wire:navigate
               class="inline-flex items-center gap-1 px-3 py-2 border border-[#A5D6A7] rounded-lg text-sm text-[#333333] hover:bg-[#A5D6A7]/20 cursor-pointer transition">
                <flux:icon.arrow-left class="w-4 h-4" />
                {{ __('Volver') }}
            </a>
        </div>
    </div>

    {{-- Filtros --}}
    <div class="bg-white rounded-xl shadow-sm border border-[#A5D6A7] p-5 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-3 items-end">
            <div>
                <label class="block text-sm font-medium text-[#333333] mb-1">{{ __('Buscar') }}</label>
                <input
                    type="text"
                    wire:model.live="search"
                    placeholder="{{ __('Observación...') }}"
                    class="w-full px-4 py-2.5 border border-[#A5D6A7] rounded-lg bg-white text-sm focus:outline-none focus:ring-2 focus:ring-[#2E8B57]"
                />
            </div>
            <div class="flex gap-2">
                <flux:button
                    wire:click="$refresh"
                    variant="filled"
                    icon="funnel"
                    class="text-white cursor-pointer hover:bg-gray-600"
                >
                    {{ __('Filtrar') }}
                </flux:button>
                <flux:button
                    wire:click="$set('search', '')"
                    variant="ghost"
                    icon="x-mark"
                    class="text-[#333333] cursor-pointer"
                >
                    {{ __('Limpiar') }}
                </flux:button>
            </div>
        </div>
    </div>

    {{-- Tabla --}}
    <div class="bg-white rounded-xl shadow-sm border border-[#A5D6A7] overflow-hidden">
        @include('pages.maintenance.details.components.table')
    </div>

    @include('pages.maintenance.details.components.form')
</div>
