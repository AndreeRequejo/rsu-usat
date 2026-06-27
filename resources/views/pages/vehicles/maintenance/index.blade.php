<?php

use App\Models\VehicleMaintenanceProgram;
use Flux\Flux;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    public string $search = '';
    public ?int $editingProgramId = null;
    public ?int $deletingProgramId = null;
    public string $name = '';
    public string $start_date = '';
    public string $end_date = '';

    #[Computed]
    public function programs()
    {
        return VehicleMaintenanceProgram::query()
            ->withCount('schedules')
            ->when($this->search !== '', fn ($query) => $query->where('name', 'like', '%'.$this->search.'%'))
            ->orderByDesc('start_date')
            ->paginate(10);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function openProgramForm(): void
    {
        $this->resetProgramForm();
        Flux::modal('program-form')->show();
    }

    public function editProgram(int $id): void
    {
        $program = VehicleMaintenanceProgram::findOrFail($id);
        $this->editingProgramId = $program->id;
        $this->name = $program->name;
        $this->start_date = $program->start_date->format('Y-m-d');
        $this->end_date = $program->end_date->format('Y-m-d');
        Flux::modal('program-form')->show();
    }

    public function saveProgram(): void
    {
        $this->validate([
            'name' => ['required', 'string', 'max:150', Rule::unique('vehicle_maintenance_programs', 'name')->ignore($this->editingProgramId)],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
        ], [
            'name.required' => 'Ingrese el nombre del mantenimiento.',
            'name.unique' => 'Ya existe un mantenimiento con ese nombre.',
            'start_date.required' => 'Ingrese la fecha de inicio.',
            'end_date.required' => 'Ingrese la fecha de fin.',
            'end_date.after_or_equal' => 'La fecha de inicio no puede ser mayor a la fecha de fin.',
        ]);

        $overlap = VehicleMaintenanceProgram::query()
            ->when($this->editingProgramId, fn ($query) => $query->where('id', '!=', $this->editingProgramId))
            ->whereDate('start_date', '<=', $this->end_date)
            ->whereDate('end_date', '>=', $this->start_date)
            ->exists();

        if ($overlap) {
            $this->addError('start_date', 'Las fechas del mantenimiento no se pueden solapar con otro mantenimiento registrado.');
            return;
        }

        $program = VehicleMaintenanceProgram::updateOrCreate(
            ['id' => $this->editingProgramId],
            [
                'name' => $this->name,
                'start_date' => $this->start_date,
                'end_date' => $this->end_date,
            ]
        );

        Flux::toast(variant: 'success', text: $this->editingProgramId ? 'Mantenimiento actualizado.' : 'Mantenimiento registrado.');
        $this->resetProgramForm();
        Flux::modal('program-form')->close();
    }

    public function confirmDeleteProgram(int $id): void
    {
        $this->deletingProgramId = $id;
        Flux::modal('delete-program')->show();
    }

    public function deleteProgram(): void
    {
        if (! $this->deletingProgramId) {
            return;
        }

        VehicleMaintenanceProgram::findOrFail($this->deletingProgramId)->delete();
        $this->deletingProgramId = null;
        Flux::modal('delete-program')->close();
        Flux::toast(variant: 'success', text: 'Mantenimiento eliminado.');
    }

    private function resetProgramForm(): void
    {
        $this->reset(['editingProgramId', 'name', 'start_date', 'end_date']);
        $this->resetErrorBag();
        $this->resetValidation();
    }
}; ?>

<div class="min-h-screen bg-white p-6 text-[#333333]">
    <div class="flex items-start justify-between mb-6">
        <div>
            <h1 class="text-3xl font-bold text-[#2E8B57]">{{ __('Mantenimiento de vehiculos') }}</h1>
            <p class="text-sm text-[#333333] mt-1">{{ __('Programa mantenimientos y accede a sus horarios.') }}</p>
        </div>

        <flux:button wire:click="openProgramForm" variant="primary" icon="plus-circle" class="bg-[#2E8B57]! text-white cursor-pointer hover:bg-[#257046]!">
            {{ __('Nuevo Mantenimiento') }}
        </flux:button>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-[#A5D6A7] p-5 mb-6">
        <label class="block text-sm font-medium text-[#333333] mb-2">{{ __('Buscar por nombre del mantenimiento') }}</label>
        <div class="relative">
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 h-5 w-5 text-[#333333]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
            <input type="text" wire:model.live.debounce.300ms="search" placeholder="{{ __('Buscar...') }}" class="w-full pl-10 pr-4 py-2.5 border border-[#A5D6A7] rounded-lg bg-white text-sm focus:outline-none focus:ring-2 focus:ring-[#2E8B57]" />
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-[#A5D6A7] overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="bg-[#2E8B57] text-white text-xs font-bold uppercase tracking-wider">
                        <th class="px-6 py-4 text-left">{{ __('Nombre') }}</th>
                        <th class="px-6 py-4 text-center">{{ __('Inicio') }}</th>
                        <th class="px-6 py-4 text-center">{{ __('Fin') }}</th>
                        <th class="px-6 py-4 text-center">{{ __('Horarios') }}</th>
                        <th class="px-6 py-4 text-right">{{ __('Acciones') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($this->programs as $i => $program)
                        <tr wire:key="program-{{ $program->id }}" class="{{ $i % 2 === 0 ? 'bg-white' : 'bg-[#A5D6A7]/20' }} border-b border-[#A5D6A7] hover:bg-[#A5D6A7]/30 transition">
                            <td class="px-6 py-4 text-sm font-bold text-[#333333] uppercase">
                                {{ $program->name }}
                                <div class="text-xs font-normal normal-case text-[#666666]">{{ __(':count horario(s) registrado(s)', ['count' => $program->schedules_count]) }}</div>
                            </td>
                            <td class="px-6 py-4 text-center text-sm text-[#333333]">{{ $program->start_date->format('d/m/Y') }}</td>
                            <td class="px-6 py-4 text-center text-sm text-[#333333]">{{ $program->end_date->format('d/m/Y') }}</td>
                            <td class="px-6 py-4 text-center">
                                <a href="{{ route('vehicles.maintenance.schedules', $program) }}" wire:navigate class="inline-flex h-8 w-8 items-center justify-center rounded-md text-[#2E8B57] hover:bg-[#2E8B57]/15 transition" title="{{ __('Ver horarios') }}" aria-label="{{ __('Ver horarios') }}">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3M5 11h14M6 5h12a2 2 0 012 2v12a2 2 0 01-2 2H6a2 2 0 01-2-2V7a2 2 0 012-2z" /></svg>
                                </a>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex justify-end gap-2">
                                    <button wire:click="editProgram({{ $program->id }})" class="inline-flex h-8 w-8 items-center justify-center rounded-md text-[#F4C542] hover:bg-[#F4C542]/20 transition" title="{{ __('Editar') }}" aria-label="{{ __('Editar') }}"><svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16.862 3.487a2.25 2.25 0 013.182 3.182l-9.193 9.193a4.5 4.5 0 01-1.897 1.13l-3.17 1.056 1.056-3.17a4.5 4.5 0 011.13-1.897l9.193-9.193z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.5 7.5L16.5 4.5" /></svg></button>
                                    <button wire:click="confirmDeleteProgram({{ $program->id }})" class="inline-flex h-8 w-8 items-center justify-center rounded-md text-[#E53935] hover:bg-[#E53935]/20 transition" title="{{ __('Eliminar') }}" aria-label="{{ __('Eliminar') }}"><svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 7h12M9 7V5a1 1 0 011-1h4a1 1 0 011 1v2M7 7l1 12a2 2 0 002 2h4a2 2 0 002-2l1-12" /></svg></button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-6 py-10 text-center text-sm text-[#333333]">{{ __('No hay mantenimientos registrados.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-6 py-4 border-t border-[#A5D6A7]">{{ $this->programs->links() }}</div>
    </div>

    <flux:modal name="program-form" class="md:w-[520px]">
        <form wire:submit="saveProgram" class="space-y-5" novalidate>
            <div><flux:heading size="lg">{{ $editingProgramId ? __('Editar mantenimiento') : __('Nuevo mantenimiento') }}</flux:heading><flux:text class="mt-2">{{ __('Ingrese el nombre y el rango de fechas del mantenimiento.') }}</flux:text></div>
            <flux:input wire:model="name" label="Nombre" placeholder="Ej: MANT. DICIEMBRE 2025" required />
            <div class="grid gap-4 md:grid-cols-2"><flux:input wire:model="start_date" type="date" label="Fecha de inicio" required /><flux:input wire:model="end_date" type="date" label="Fecha de fin" required /></div>
            <div class="flex justify-end gap-3"><flux:modal.close><flux:button type="button" variant="ghost">{{ __('Cancelar') }}</flux:button></flux:modal.close><flux:button type="submit" class="bg-[#2E8B57] text-white hover:bg-[#257046]">{{ __('Guardar') }}</flux:button></div>
        </form>
    </flux:modal>

    <flux:modal name="delete-program" class="md:w-100">
        <div class="space-y-5"><div class="flex items-start gap-4 px-6 pt-4"><div class="shrink-0 w-10 h-10 rounded-full bg-red-100 flex items-center justify-center"><svg class="h-5 w-5 text-[#E53935]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg></div><div><flux:heading size="lg" class="text-[#E53935]">{{ __('Confirmar eliminacion') }}</flux:heading><flux:text class="mt-1 text-sm text-[#666666]">{{ __('Se eliminara el mantenimiento con sus horarios y dias generados.') }}</flux:text></div></div><div class="px-6 py-4 bg-[#F5F5F5] border-t border-[#E0E0E0] flex justify-end gap-3"><flux:button x-on:click="Flux.modal('delete-program').close()" type="button" variant="ghost" class="text-[#333333]">{{ __('Cancelar') }}</flux:button><flux:button wire:click="deleteProgram" variant="danger" class="bg-[#E53935] text-white hover:bg-[#C62828]">{{ __('Eliminar') }}</flux:button></div></div>
    </flux:modal>
</div>
