@if ($this->viewingChange)
<flux:modal
    name="change-viewer"
    class="w-[900px] max-w-[98vw] max-h-[95vh] overflow-hidden"
    wire:close="closeViewer"
>
    <div class="flex flex-col h-full">
        <div class="px-6 py-4 border-b border-[#A5D6A7] shrink-0 flex items-center justify-between" style="background-color: #1976D2;">
            <div class="flex items-center gap-2 text-white">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7.5 21L3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5" />
                </svg>
                <div>
                    <flux:heading size="lg" class="text-white">
                        {{ __('Detalles del Cambio') }} #{{ $this->viewingChange->id }}
                    </flux:heading>
                    <div class="text-sm text-white/80 flex items-center gap-2">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" /></svg>
                        {{ $this->viewingChange->created_at->format('d/m/Y H:i') }}
                    </div>
                </div>
            </div>
            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium text-white" style="background-color: {{ $this->viewingChange->type_badge_color }}">
                {{ $this->viewingChange->type_label }}
            </span>
        </div>

        <div class="overflow-y-auto px-6 py-5 space-y-4 flex-1">
            {{-- Valores Anteriores y Nuevos --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="bg-red-50 rounded-xl border border-red-200 p-4">
                    <div class="flex items-center gap-2 mb-3">
                        <div class="w-8 h-8 rounded-full bg-red-100 flex items-center justify-center">
                            <svg class="h-4 w-4 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" /></svg>
                        </div>
                        <div>
                            <h4 class="text-sm font-bold text-red-700">{{ __('Valores Anteriores') }}</h4>
                            <p class="text-xs text-red-600">{{ __('Estado previo al cambio') }}</p>
                        </div>
                    </div>
                    <div class="space-y-2">
                        @if ($this->viewingChange->change_type === 'turn')
                            <div class="flex items-center gap-2 text-sm"><svg class="h-4 w-4 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" /></svg><span class="text-[#666666]">{{ __('TURNO') }}</span></div>
                            <div class="text-red-700 font-semibold">{{ $this->viewingChange->oldShift?->name ?? '-' }}</div>
                            <div class="text-xs text-[#666666]">{{ $this->viewingChange->oldShift?->hour_in }} - {{ $this->viewingChange->oldShift?->hour_out }}</div>
                        @elseif ($this->viewingChange->change_type === 'vehicle')
                            <div class="flex items-center gap-2 text-sm"><svg class="h-4 w-4 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.25 18.75a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 01-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 01-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 01-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 01-1.125-1.125V14.25" /></svg><span class="text-[#666666]">{{ __('VEHICULO') }}</span></div>
                            <div class="text-red-700 font-semibold">{{ $this->viewingChange->oldVehicle?->name ?? '-' }}</div>
                        @elseif (in_array($this->viewingChange->change_type, ['driver', 'helper']))
                            <div class="flex items-center gap-2 text-sm"><svg class="h-4 w-4 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" /></svg><span class="text-[#666666]">{{ strtoupper($this->viewingChange->person_role) }}</span></div>
                            <div class="text-red-700 font-semibold">{{ ($this->viewingChange->oldPerson?->first_name ?? '') . ' ' . ($this->viewingChange->oldPerson?->last_name ?? '') }}</div>
                        @endif
                    </div>
                </div>

                <div class="bg-green-50 rounded-xl border border-green-200 p-4">
                    <div class="flex items-center gap-2 mb-3">
                        <div class="w-8 h-8 rounded-full bg-green-100 flex items-center justify-center">
                            <svg class="h-4 w-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" /></svg>
                        </div>
                        <div>
                            <h4 class="text-sm font-bold text-green-700">{{ __('Valores Nuevos') }}</h4>
                            <p class="text-xs text-green-600">{{ __('Estado despues del cambio') }}</p>
                        </div>
                    </div>
                    <div class="space-y-2">
                        @if ($this->viewingChange->change_type === 'turn')
                            <div class="flex items-center gap-2 text-sm"><svg class="h-4 w-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" /></svg><span class="text-[#666666]">{{ __('TURNO') }}</span></div>
                            <div class="text-green-700 font-semibold">{{ $this->viewingChange->newShift?->name ?? '-' }}</div>
                            <div class="text-xs text-[#666666]">{{ $this->viewingChange->newShift?->hour_in }} - {{ $this->viewingChange->newShift?->hour_out }}</div>
                        @elseif ($this->viewingChange->change_type === 'vehicle')
                            <div class="flex items-center gap-2 text-sm"><svg class="h-4 w-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.25 18.75a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 01-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 01-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 01-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 01-1.125-1.125V14.25" /></svg><span class="text-[#666666]">{{ __('VEHICULO') }}</span></div>
                            <div class="text-green-700 font-semibold">{{ $this->viewingChange->newVehicle?->name ?? '-' }}</div>
                        @elseif (in_array($this->viewingChange->change_type, ['driver', 'helper']))
                            <div class="flex items-center gap-2 text-sm"><svg class="h-4 w-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" /></svg><span class="text-[#666666]">{{ strtoupper($this->viewingChange->person_role) }}</span></div>
                            <div class="text-green-700 font-semibold">{{ ($this->viewingChange->newPerson?->first_name ?? '') . ' ' . ($this->viewingChange->newPerson?->last_name ?? '') }}</div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Información del Cambio --}}
            <div class="bg-blue-50 rounded-xl border border-blue-200 p-4">
                <div class="flex items-center gap-2 mb-4">
                    <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center">
                        <svg class="h-4 w-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" /></svg>
                    </div>
                    <h4 class="text-sm font-bold text-blue-700">{{ __('Informacion del Cambio') }}</h4>
                </div>

                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div class="text-center">
                        <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center mx-auto mb-2">
                            <svg class="h-5 w-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7.5 21L3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5" /></svg>
                        </div>
                        <div class="text-xs text-[#666666] uppercase">{{ __('Tipo de Cambio') }}</div>
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium text-white mt-1" style="background-color: {{ $this->viewingChange->type_badge_color }}">
                            {{ $this->viewingChange->type_label }}
                        </span>
                    </div>
                    <div class="text-center">
                        <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center mx-auto mb-2">
                            <svg class="h-5 w-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" /></svg>
                        </div>
                        <div class="text-xs text-[#666666] uppercase">{{ __('Realizado por') }}</div>
                        <div class="text-sm font-semibold text-[#333333] mt-1">{{ $this->viewingChange->user?->name ?? '-' }}</div>
                    </div>
                    <div class="text-center">
                        <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center mx-auto mb-2">
                            <svg class="h-5 w-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" /></svg>
                        </div>
                        <div class="text-xs text-[#666666] uppercase">{{ __('Fecha del Cambio') }}</div>
                        <div class="text-sm font-semibold text-[#333333] mt-1">{{ $this->viewingChange->created_at->format('d/m/Y H:i') }}</div>
                    </div>
                    <div class="text-center">
                        <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center mx-auto mb-2">
                            <svg class="h-5 w-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h3.75M9 15H12.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25zM6.75 12h.008v.008H6.75V12zm0 3h.008v.008H6.75V15zm0 3h.008v.008H6.75V18z" /></svg>
                        </div>
                        <div class="text-xs text-[#666666] uppercase">{{ __('Programacion') }}</div>
                        <div class="text-sm font-semibold text-[#333333] mt-1">#{{ $this->viewingChange->affected_count }}</div>
                    </div>
                </div>

                <div class="mt-4 bg-white rounded-lg p-3 border border-blue-100">
                    <div class="flex items-center gap-2 mb-1">
                        <svg class="h-4 w-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7.5 8.25h9m-9 3H12m-9.75 1.51c0 1.6 1.123 2.994 2.707 3.227 1.129.166 2.27.293 3.423.379.35.027.67.137.952.322.29.19.539.446.733.767.227.366.358.777.358 1.204 0 1.07-.4 2.09-1.062 2.875-.668.79-1.591 1.305-2.625 1.305-1.05 0-1.987-.53-2.65-1.322-.662-.792-1.062-1.812-1.062-2.875 0-.427.13-.838.358-1.204.194-.321.443-.577.733-.767.282-.185.602-.295.952-.322 1.153-.086 2.294-.213 3.423-.379 1.584-.233 2.707-1.627 2.707-3.227V6.42" /></svg>
                        <span class="text-sm font-semibold text-blue-700">{{ __('Motivo del Cambio') }}</span>
                    </div>
                    <p class="text-sm text-[#333333]">{{ $this->viewingChange->reason_full }}</p>
                </div>
            </div>

            {{-- Programaciones afectadas --}}
            @if ($this->viewingChange->items->count() > 0)
                <div>
                    <h4 class="text-sm font-bold text-[#333333] mb-2">{{ __('Programaciones afectadas') }} ({{ $this->viewingChange->items->count() }})</h4>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm border border-[#A5D6A7] rounded-lg overflow-hidden">
                            <thead class="bg-[#2E8B57] text-white">
                                <tr>
                                    <th class="px-3 py-2 text-left">{{ __('Fecha') }}</th>
                                    <th class="px-3 py-2 text-left">{{ __('Zona') }}</th>
                                    <th class="px-3 py-2 text-left">{{ __('Turno') }}</th>
                                    <th class="px-3 py-2 text-left">{{ __('Vehiculo') }}</th>
                                    <th class="px-3 py-2 text-left">{{ __('Personal') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($this->viewingChange->items as $item)
                                    <tr class="border-b border-[#A5D6A7]">
                                        <td class="px-3 py-2">{{ $item->scheduling?->date?->format('d/m/Y') ?? '-' }}</td>
                                        <td class="px-3 py-2">{{ $item->scheduling?->zone?->name ?? '-' }}</td>
                                        <td class="px-3 py-2">{{ $item->scheduling?->shift?->name ?? '-' }}</td>
                                        <td class="px-3 py-2">{{ $item->scheduling?->vehicle?->name ?? '-' }}</td>
                                        <td class="px-3 py-2">{{ $item->scheduling?->groupDetails?->map(fn($gd) => ($gd->employee?->first_name ?? '') . ' ' . ($gd->employee?->last_name ?? ''))->implode(', ') ?? '-' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </div>

        <div class="px-6 py-4 bg-[#F5F5F5] border-t border-[#E0E0E0] flex justify-end gap-3 shrink-0">
            <flux:button type="button" variant="ghost" wire:click="closeViewer" class="text-[#333333]">
                {{ __('Cerrar') }}
            </flux:button>
        </div>
    </div>
</flux:modal>
@endif
