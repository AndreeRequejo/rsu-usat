<?php

use App\Models\Employee;
use App\Models\Vehicle;
use App\Models\VehicleMaintenanceDetail;
use App\Models\VehicleMaintenanceProgram;
use App\Models\VehicleMaintenanceSchedule;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Flux\Flux;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Computed;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;

new class extends Component {
    use WithFileUploads;

    public int $programId;

    public ?int $editingScheduleId = null;
    public ?int $deletingScheduleId = null;
    public ?int $vehicle_id = null;
    public ?int $responsible_id = null;
    public string $maintenance_type = 'Preventivo';
    public ?int $day_of_week = null;
    public string $start_time = '';
    public string $end_time = '';

    public ?int $viewingScheduleId = null;
    public ?int $editingDetailId = null;
    public string $observation = '';
    public bool $completed = false;
    public $image = null;
    public string $previewImageUrl = '';
    public string $previewImageTitle = '';

    public function mount(int $program): void
    {
        $this->programId = $program;
    }

    #[Computed]
    public function program(): VehicleMaintenanceProgram
    {
        return VehicleMaintenanceProgram::findOrFail($this->programId);
    }

    #[Computed]
    public function schedules()
    {
        return VehicleMaintenanceSchedule::query()
            ->with(['vehicle', 'responsible', 'details'])
            ->where('vehicle_maintenance_program_id', $this->programId)
            ->orderBy('day_of_week')
            ->orderBy('start_time')
            ->get();
    }

    #[Computed]
    public function viewingSchedule(): ?VehicleMaintenanceSchedule
    {
        if (! $this->viewingScheduleId) {
            return null;
        }

        return VehicleMaintenanceSchedule::with(['program', 'vehicle', 'details' => fn ($query) => $query->orderBy('maintenance_date')])->find($this->viewingScheduleId);
    }

    #[Computed]
    public function vehicles()
    {
        return Vehicle::orderBy('name')->get();
    }

    #[Computed]
    public function responsibles()
    {
        return Employee::where('active', true)->orderBy('first_name')->orderBy('last_name')->get();
    }

    public function openScheduleForm(): void
    {
        $this->resetScheduleForm();
        Flux::modal('schedule-form')->show();
    }

    public function editSchedule(int $id): void
    {
        $schedule = VehicleMaintenanceSchedule::where('vehicle_maintenance_program_id', $this->programId)->findOrFail($id);
        $this->editingScheduleId = $schedule->id;
        $this->vehicle_id = $schedule->vehicle_id;
        $this->responsible_id = $schedule->responsible_id;
        $this->maintenance_type = $schedule->type;
        $this->day_of_week = $schedule->day_of_week;
        $this->start_time = substr($schedule->start_time, 0, 5);
        $this->end_time = substr($schedule->end_time, 0, 5);
        Flux::modal('schedule-form')->show();
    }

    public function cancelScheduleForm(): void
    {
        $this->resetScheduleForm();
        Flux::modal('schedule-form')->close();
    }

    public function saveSchedule(): void
    {
        $this->validate([
            'vehicle_id' => ['required', 'exists:vehicles,id'],
            'responsible_id' => ['required', 'exists:employees,id'],
            'maintenance_type' => ['required', 'in:Preventivo,Limpieza,Reparacion'],
            'day_of_week' => ['required', 'integer', 'between:1,7'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
        ], [
            'vehicle_id.required' => 'Seleccione un vehiculo.',
            'responsible_id.required' => 'Seleccione un responsable.',
            'day_of_week.required' => 'Seleccione el dia de la semana.',
            'start_time.required' => 'Ingrese la hora de inicio.',
            'end_time.required' => 'Ingrese la hora de fin.',
            'end_time.after' => 'La hora de inicio debe ser menor a la hora de fin.',
        ]);

        $vehicleOverlap = VehicleMaintenanceSchedule::query()
            ->when($this->editingScheduleId, fn ($query) => $query->where('id', '!=', $this->editingScheduleId))
            ->where('vehicle_maintenance_program_id', $this->programId)
            ->where('vehicle_id', $this->vehicle_id)
            ->where('day_of_week', $this->day_of_week)
            ->where('start_time', '<', $this->end_time)
            ->where('end_time', '>', $this->start_time)
            ->exists();

        if ($vehicleOverlap) {
            $this->addError('start_time', 'Los horarios no se pueden solapar en dia, horas y vehiculo.');
            return;
        }

        $responsibleOverlap = VehicleMaintenanceSchedule::query()
            ->when($this->editingScheduleId, fn ($query) => $query->where('id', '!=', $this->editingScheduleId))
            ->where('vehicle_maintenance_program_id', $this->programId)
            ->where('responsible_id', $this->responsible_id)
            ->where('day_of_week', $this->day_of_week)
            ->where('start_time', '<', $this->end_time)
            ->where('end_time', '>', $this->start_time)
            ->exists();

        if ($responsibleOverlap) {
            $this->addError('responsible_id', 'El responsable no puede estar en dos vehiculos al mismo tiempo.');
            return;
        }

        DB::transaction(function () {
            $schedule = VehicleMaintenanceSchedule::updateOrCreate(
                ['id' => $this->editingScheduleId],
                [
                    'vehicle_maintenance_program_id' => $this->programId,
                    'vehicle_id' => $this->vehicle_id,
                    'responsible_id' => $this->responsible_id,
                    'type' => $this->maintenance_type,
                    'day_of_week' => $this->day_of_week,
                    'start_time' => $this->start_time,
                    'end_time' => $this->end_time,
                ]
            );

            $this->regenerateDetails($schedule);
        });

        Flux::toast(variant: 'success', text: $this->editingScheduleId ? 'Horario actualizado.' : 'Horario registrado.');
        $this->resetScheduleForm();
        Flux::modal('schedule-form')->close();
    }

    public function confirmDeleteSchedule(int $id): void
    {
        $this->deletingScheduleId = $id;
        Flux::modal('delete-schedule')->show();
    }

    public function deleteSchedule(): void
    {
        if (! $this->deletingScheduleId) {
            return;
        }

        $id = $this->deletingScheduleId;
        $schedule = VehicleMaintenanceSchedule::where('vehicle_maintenance_program_id', $this->programId)->findOrFail($id);
        $schedule->delete();

        if ($this->viewingScheduleId === $id) {
            $this->viewingScheduleId = null;
        }
        if ($this->editingScheduleId === $id) {
            $this->resetScheduleForm();
            Flux::modal('schedule-form')->close();
        }

        $this->deletingScheduleId = null;
        Flux::modal('delete-schedule')->close();
        Flux::toast(variant: 'success', text: 'Horario y dias generados eliminados.');
    }

    public function viewDetails(int $id): void
    {
        $this->viewingScheduleId = VehicleMaintenanceSchedule::where('vehicle_maintenance_program_id', $this->programId)->findOrFail($id)->id;
        Flux::modal('details-manager')->show();
    }

    public function closeDetailsManager(): void
    {
        $this->viewingScheduleId = null;
        Flux::modal('details-manager')->close();
    }

    public function previewImage(int $id): void
    {
        $detail = VehicleMaintenanceDetail::with('schedule.vehicle')->findOrFail($id);
        if (! $detail->image_path) {
            return;
        }

        $this->previewImageUrl = Storage::url($detail->image_path);
        $this->previewImageTitle = ($detail->schedule?->vehicle?->name ?? 'Imagen').' - '.$detail->maintenance_date->format('d/m/Y');
        Flux::modal('image-preview')->show();
    }

    public function closeImagePreview(): void
    {
        $this->reset(['previewImageUrl', 'previewImageTitle']);
        Flux::modal('image-preview')->close();
    }

    public function editDetail(int $id): void
    {
        $detail = VehicleMaintenanceDetail::findOrFail($id);
        $this->editingDetailId = $detail->id;
        $this->observation = $detail->observation ?? '';
        $this->completed = (bool) $detail->completed;
        $this->image = null;
        Flux::modal('detail-form')->show();
    }

    public function saveDetail(): void
    {
        $this->validate([
            'observation' => ['nullable', 'string', 'max:1000'],
            'completed' => ['boolean'],
            'image' => ['nullable', 'image', 'max:2048'],
        ]);

        $detail = VehicleMaintenanceDetail::findOrFail($this->editingDetailId);
        $data = [
            'observation' => $this->observation ?: null,
            'completed' => $this->completed,
        ];

        if ($this->image) {
            if ($detail->image_path) {
                Storage::disk('public')->delete($detail->image_path);
            }
            $data['image_path'] = $this->image->store('maintenance-details', 'public');
        }

        $detail->update($data);
        $this->resetDetailForm();
        Flux::modal('detail-form')->close();
        Flux::toast(variant: 'success', text: 'Dia actualizado.');
    }

    private function regenerateDetails(VehicleMaintenanceSchedule $schedule): void
    {
        $program = $schedule->program()->first();
        if (! $program) {
            return;
        }

        $existing = $schedule->details()->get()->keyBy(fn ($detail) => $detail->maintenance_date->format('Y-m-d'));
        $validDates = [];

        foreach (CarbonPeriod::create($program->start_date, $program->end_date) as $date) {
            if ((int) $date->dayOfWeekIso !== (int) $schedule->day_of_week) {
                continue;
            }

            $key = $date->format('Y-m-d');
            $validDates[] = $key;
            if (! $existing->has($key)) {
                $schedule->details()->create(['maintenance_date' => $key]);
            }
        }

        $schedule->details()->whereNotIn('maintenance_date', $validDates ?: ['0001-01-01'])->delete();
    }

    private function resetScheduleForm(): void
    {
        $this->reset(['editingScheduleId', 'vehicle_id', 'responsible_id', 'day_of_week', 'start_time', 'end_time']);
        $this->maintenance_type = 'Preventivo';
        $this->resetErrorBag();
        $this->resetValidation();
    }

    public function cancelDetailEdit(): void
    {
        $this->resetDetailForm();
        Flux::modal('detail-form')->close();
    }

    private function resetDetailForm(): void
    {
        $this->reset(['editingDetailId', 'observation', 'completed', 'image']);
        $this->resetErrorBag();
        $this->resetValidation();
    }

    public function weekdayLabel(?int $day): string
    {
        return [1 => 'Lunes', 2 => 'Martes', 3 => 'Miercoles', 4 => 'Jueves', 5 => 'Viernes', 6 => 'Sabado', 7 => 'Domingo'][$day] ?? '-';
    }

    public function timeLabel(?string $time): string
    {
        if (! $time) {
            return '-';
        }

        return Carbon::createFromFormat('H:i:s', strlen($time) === 5 ? $time.':00' : $time)->format('h:i a');
    }
}; ?>

<div class="min-h-screen bg-white p-6 text-[#333333]">
    <div class="mb-6 flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
        <div>
            <h1 class="text-3xl font-bold text-[#2E8B57]">{{ __('Horarios de mantenimiento') }}</h1>
            <p class="mt-1 text-sm text-[#333333]">{{ $this->program->name }} ({{ $this->program->start_date->format('d/m/Y') }} - {{ $this->program->end_date->format('d/m/Y') }})</p>
        </div>
        <div class="flex gap-2">
            <flux:button :href="route('vehicles.maintenance.index')" wire:navigate variant="ghost" icon="arrow-left" class="text-[#333333]">
                {{ __('Volver') }}
            </flux:button>
            <flux:button wire:click="openScheduleForm" icon="plus-circle" variant="primary" class="bg-[#2E8B57]! text-white cursor-pointer hover:bg-[#257046]!">
                {{ __('Nuevo Horario') }}
            </flux:button>
        </div>
    </div>

    <div class="overflow-hidden rounded-xl border border-[#A5D6A7] bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="bg-[#2E8B57] text-white text-xs font-bold uppercase tracking-wider">
                        <th class="px-4 py-4 text-left">{{ __('Dia') }}</th>
                        <th class="px-4 py-4 text-left">{{ __('Vehiculo') }}</th>
                        <th class="px-4 py-4 text-left">{{ __('Responsable') }}</th>
                        <th class="px-4 py-4 text-left">{{ __('Tipo') }}</th>
                        <th class="px-4 py-4 text-center">{{ __('Inicio') }}</th>
                        <th class="px-4 py-4 text-center">{{ __('Fin') }}</th>
                        <th class="px-4 py-4 text-center">{{ __('Dias') }}</th>
                        <th class="px-4 py-4 text-right">{{ __('Acciones') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($this->schedules as $i => $schedule)
                        <tr wire:key="schedule-{{ $schedule->id }}" class="{{ $i % 2 === 0 ? 'bg-white' : 'bg-[#A5D6A7]/20' }} border-b border-[#A5D6A7] hover:bg-[#A5D6A7]/30 transition">
                            <td class="px-4 py-4 text-sm font-bold text-[#333333]">{{ $this->weekdayLabel($schedule->day_of_week) }}</td>
                            <td class="px-4 py-4 text-sm text-[#333333]">{{ $schedule->vehicle?->name ?? '-' }}</td>
                            <td class="px-4 py-4 text-sm text-[#333333]">{{ trim(($schedule->responsible?->first_name ?? '').' '.($schedule->responsible?->last_name ?? '')) ?: '-' }}</td>
                            <td class="px-4 py-4 text-sm text-[#333333]">{{ $schedule->type }}</td>
                            <td class="px-4 py-4 text-center text-sm text-[#333333]">{{ $this->timeLabel($schedule->start_time) }}</td>
                            <td class="px-4 py-4 text-center text-sm text-[#333333]">{{ $this->timeLabel($schedule->end_time) }}</td>
                            <td class="px-4 py-4 text-center">
                                <button wire:click="viewDetails({{ $schedule->id }})" class="inline-flex h-8 w-8 items-center justify-center rounded-md text-[#2E8B57] hover:bg-[#2E8B57]/15 transition" title="{{ __('Ver dias generados') }}" aria-label="{{ __('Ver dias generados') }}">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.25 12s3.75-6.75 9.75-6.75S21.75 12 21.75 12 18 18.75 12 18.75 2.25 12 2.25 12z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                                </button>
                            </td>
                            <td class="px-4 py-4">
                                <div class="flex justify-end gap-2">
                                    <button wire:click="editSchedule({{ $schedule->id }})" class="inline-flex h-8 w-8 items-center justify-center rounded-md text-[#F4C542] hover:bg-[#F4C542]/20 transition" title="{{ __('Editar') }}" aria-label="{{ __('Editar') }}">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16.862 3.487a2.25 2.25 0 013.182 3.182l-9.193 9.193a4.5 4.5 0 01-1.897 1.13l-3.17 1.056 1.056-3.17a4.5 4.5 0 011.13-1.897l9.193-9.193z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.5 7.5L16.5 4.5" /></svg>
                                    </button>
                                    <button wire:click="confirmDeleteSchedule({{ $schedule->id }})" class="inline-flex h-8 w-8 items-center justify-center rounded-md text-[#E53935] hover:bg-[#E53935]/20 transition" title="{{ __('Eliminar') }}" aria-label="{{ __('Eliminar') }}">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 7h12M9 7V5a1 1 0 011-1h4a1 1 0 011 1v2M7 7l1 12a2 2 0 002 2h4a2 2 0 002-2l1-12" /></svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="px-6 py-10 text-center text-sm text-[#333333]">{{ __('No hay horarios registrados para este mantenimiento.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>


    <flux:modal name="schedule-form" wire:close="cancelScheduleForm" class="md:w-[760px]">
        <form wire:submit="saveSchedule" class="space-y-5" novalidate>
            <div>
                <flux:heading size="lg">{{ $editingScheduleId ? __('Editar horario') : __('Nuevo horario') }}</flux:heading>
                <flux:text class="mt-2">{{ __('Seleccione vehiculo, responsable, tipo, dia y rango horario.') }}</flux:text>
            </div>
            <div class="grid gap-4 md:grid-cols-2">
                <flux:select wire:model="vehicle_id" label="Vehiculo" required>
                    <option value="">Seleccione...</option>
                    @foreach ($this->vehicles as $vehicle)
                        <option value="{{ $vehicle->id }}">{{ $vehicle->name }} - {{ $vehicle->plate }}</option>
                    @endforeach
                </flux:select>
                <flux:select wire:model="responsible_id" label="Responsable" required>
                    <option value="">Seleccione...</option>
                    @foreach ($this->responsibles as $employee)
                        <option value="{{ $employee->id }}">{{ $employee->first_name }} {{ $employee->last_name }}</option>
                    @endforeach
                </flux:select>
                <flux:select wire:model="maintenance_type" label="Tipo de mantenimiento" required>
                    <option value="Preventivo">Preventivo</option>
                    <option value="Limpieza">Limpieza</option>
                    <option value="Reparacion">Reparacion</option>
                </flux:select>
                <flux:select wire:model="day_of_week" label="Dia de la semana" required>
                    <option value="">Seleccione...</option>
                    @foreach ([1 => 'Lunes', 2 => 'Martes', 3 => 'Miercoles', 4 => 'Jueves', 5 => 'Viernes', 6 => 'Sabado', 7 => 'Domingo'] as $day => $label)
                        <option value="{{ $day }}">{{ $label }}</option>
                    @endforeach
                </flux:select>
                <flux:input wire:model="start_time" type="time" label="Hora de inicio" required />
                <flux:input wire:model="end_time" type="time" label="Hora de fin" required />
            </div>
            <div class="flex justify-end gap-3">
                <flux:button type="button" wire:click="cancelScheduleForm" variant="ghost" class="text-[#333333]">{{ __('Cancelar') }}</flux:button>
                <flux:button type="submit" class="bg-[#2E8B57] text-white hover:bg-[#257046]">{{ $editingScheduleId ? __('Actualizar') : __('Guardar') }}</flux:button>
            </div>
        </form>
    </flux:modal>

    <flux:modal name="delete-schedule" class="md:w-100">
        <div class="space-y-5">
            <div class="flex items-start gap-4 px-6 pt-4">
                <div class="shrink-0 w-10 h-10 rounded-full bg-red-100 flex items-center justify-center">
                    <svg class="h-5 w-5 text-[#E53935]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>
                <div>
                    <flux:heading size="lg" class="text-[#E53935]">{{ __('Confirmar eliminacion') }}</flux:heading>
                    <flux:text class="mt-1 text-sm text-[#666666]">{{ __('Se eliminara el horario y todos los dias generados automaticamente.') }}</flux:text>
                </div>
            </div>
            <div class="px-6 py-4 bg-[#F5F5F5] border-t border-[#E0E0E0] flex justify-end gap-3">
                <flux:button x-on:click="Flux.modal('delete-schedule').close()" type="button" variant="ghost" class="text-[#333333]">{{ __('Cancelar') }}</flux:button>
                <flux:button wire:click="deleteSchedule" variant="danger" class="bg-[#E53935] text-white hover:bg-[#C62828]">{{ __('Eliminar') }}</flux:button>
            </div>
        </div>
    </flux:modal>

    <flux:modal name="details-manager" wire:close="closeDetailsManager" class="w-[96vw]! md:w-[980px]! max-w-none! max-h-[92vh] overflow-y-auto">
        @if ($this->viewingSchedule)
            <div class="space-y-5">
                <div class="bg-[#2E8B57] px-6 py-4 text-white">
                    <flux:heading size="lg" class="text-white">{{ $this->viewingSchedule->program?->name }} - {{ $this->weekdayLabel($this->viewingSchedule->day_of_week) }} - {{ $this->viewingSchedule->vehicle?->name }}</flux:heading>
                    <flux:text class="mt-1 text-white/90">{{ __('Dias generados para el horario seleccionado.') }}</flux:text>
                </div>
                <div class="px-5 pb-5">
                    <div class="overflow-hidden rounded-xl border border-[#A5D6A7] bg-white shadow-sm">
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead><tr class="bg-[#2E8B57] text-white text-xs font-bold uppercase tracking-wider"><th class="px-4 py-4 text-center">{{ __('Fecha') }}</th><th class="px-4 py-4 text-left">{{ __('Observacion') }}</th><th class="px-4 py-4 text-center">{{ __('Imagen') }}</th><th class="px-4 py-4 text-center">{{ __('Edit') }}</th><th class="px-4 py-4 text-center">{{ __('Est') }}</th></tr></thead>
                                <tbody>
                                    @foreach ($this->viewingSchedule->details as $i => $detail)
                                        <tr wire:key="detail-{{ $detail->id }}" class="{{ $i % 2 === 0 ? 'bg-white' : 'bg-[#A5D6A7]/20' }} border-b border-[#A5D6A7] hover:bg-[#A5D6A7]/30 transition">
                                            <td class="px-4 py-4 text-center text-sm font-bold text-[#333333]">{{ $detail->maintenance_date->format('d/m/Y') }}</td>
                                            <td class="px-4 py-4 text-sm text-[#333333]">{{ $detail->observation ?: '-' }}</td>
                                            <td class="px-4 py-4 text-center text-sm">
                                                @if ($detail->image_path)
                                                    <button wire:click="previewImage({{ $detail->id }})" class="inline-flex h-8 w-8 items-center justify-center rounded-md text-[#2E8B57] hover:bg-[#2E8B57]/15 transition" title="{{ __('Previsualizar imagen') }}" aria-label="{{ __('Previsualizar imagen') }}"><svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.25 12s3.75-6.75 9.75-6.75S21.75 12 21.75 12 18 18.75 12 18.75 2.25 12 2.25 12z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg></button>
                                                @else
                                                    -
                                                @endif
                                            </td>
                                            <td class="px-4 py-4 text-center"><button wire:click="editDetail({{ $detail->id }})" class="inline-flex h-8 w-8 items-center justify-center rounded-md text-[#F4C542] hover:bg-[#F4C542]/20 transition" title="{{ __('Editar') }}" aria-label="{{ __('Editar') }}"><svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16.862 3.487a2.25 2.25 0 013.182 3.182l-9.193 9.193a4.5 4.5 0 01-1.897 1.13l-3.17 1.056 1.056-3.17a4.5 4.5 0 011.13-1.897l9.193-9.193z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.5 7.5L16.5 4.5" /></svg></button></td>
                                            <td class="px-4 py-4 text-center">
                                                @if ($detail->completed)
                                                    <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-[#2E8B57]/10 text-[#2E8B57]"><svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" /></svg></span>
                                                @else
                                                    <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-[#E53935]/10 text-[#E53935]"><svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12" /></svg></span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </flux:modal>


    <flux:modal name="detail-form" wire:close="cancelDetailEdit" class="md:w-[560px]">
        <form wire:submit="saveDetail" class="space-y-5" novalidate>
            <div>
                <flux:heading size="lg">{{ __('Actualizar dia de mantenimiento') }}</flux:heading>
                <flux:text class="mt-2">{{ __('Registre observacion, imagen y estado del dia.') }}</flux:text>
            </div>
            <flux:textarea wire:model="observation" label="Observacion" rows="3" placeholder="Ingrese una observacion" />
            <div>
                <label class="mb-2 block text-sm font-medium text-[#333333]">{{ __('Imagen') }}</label>
                <input type="file" wire:model="image" accept="image/*" class="block w-full text-sm file:mr-4 file:rounded-lg file:border-0 file:bg-[#2E8B57] file:px-4 file:py-2 file:text-sm file:font-semibold file:text-white hover:file:bg-[#257046]" />
                @error('image') <span class="mt-1 block text-xs text-[#E53935]">{{ $message }}</span> @enderror
                @if ($image)
                    <img src="{{ $image->temporaryUrl() }}" class="mt-3 h-28 w-28 rounded-lg object-cover ring-1 ring-[#A5D6A7]" alt="Preview">
                @endif
            </div>
            <label class="flex items-center gap-3 text-sm font-medium">
                <input type="checkbox" wire:model="completed" class="rounded border-gray-300 text-[#2E8B57] focus:ring-[#2E8B57]">
                {{ __('Mantenimiento realizado') }}
            </label>
            <div class="flex justify-end gap-3">
                <flux:button type="button" wire:click="cancelDetailEdit" variant="ghost" class="text-[#333333]">{{ __('Cancelar') }}</flux:button>
                <flux:button type="submit" class="bg-[#2E8B57] text-white hover:bg-[#257046]">{{ __('Guardar') }}</flux:button>
            </div>
        </form>
    </flux:modal>

    <flux:modal name="image-preview" wire:close="closeImagePreview" class="w-[94vw]! md:w-[760px]! max-w-none!">
        <div class="space-y-5"><div class="flex items-start justify-between gap-4 bg-[#2E8B57] px-6 py-4 text-white"><div><flux:heading size="lg" class="text-white">{{ __('Previsualizacion de imagen') }}</flux:heading><flux:text class="mt-1 text-white/90">{{ $previewImageTitle }}</flux:text></div></div><div class="px-5 pb-5">@if ($previewImageUrl)<div class="overflow-hidden rounded-xl border border-[#A5D6A7] bg-[#F5F5F5] p-3"><img src="{{ $previewImageUrl }}" alt="{{ $previewImageTitle }}" class="mx-auto max-h-[70vh] w-auto max-w-full rounded-lg object-contain"></div>@endif<div class="mt-4 flex justify-end"><flux:button type="button" wire:click="closeImagePreview" variant="ghost" class="text-[#333333]">{{ __('Cerrar') }}</flux:button></div></div></div>
    </flux:modal>

</div>
