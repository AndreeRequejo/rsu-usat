<?php

use App\Models\DetalleHorarioMantenimiento;
use App\Models\Employee;
use App\Models\HorarioMantenimiento;
use App\Models\Mantenimiento;
use App\Models\Vehicle;
use Carbon\Carbon;
use Flux\Flux;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination, WithFileUploads;

    public string $search = '';

    public ?int $mantEditingId = null;
    public ?int $mantDeletingId = null;
    public ?int $mantViewId = null;

    public string $mantNombre = '';
    public string $mantFechaInicio = '';
    public string $mantFechaFin = '';

    public ?int $horEditingId = null;
    public ?int $horDeletingId = null;
    public ?int $detViewHorarioId = null;
    public ?int $detEditingId = null;

    public ?int $horVehiculoId = null;
    public ?int $horResponsableId = null;
    public string $horTipo = '';
    public string $horDiaSemana = '';
    public string $horHoraInicio = '';
    public string $horHoraFin = '';

    public string $detObservacion = '';
    public bool $detRealizado = false;
    public $detImagen = null;
    public bool $detRemoveImage = false;
    public ?string $detPreviewUrl = null;

    public int $perPage = 10;

    protected function rules(): array
    {
        return [
            'mantNombre' => [
                'required',
                'string',
                'max:255',
                Rule::unique('mantenimientos', 'nombre')->ignore($this->mantEditingId),
            ],
            'mantFechaInicio' => ['required', 'date'],
            'mantFechaFin' => ['required', 'date', 'after_or_equal:mantFechaInicio'],
        ];
    }

    protected function messages(): array
    {
        return [
            'mantNombre.required' => 'El nombre del mantenimiento es obligatorio.',
            'mantNombre.max' => 'El nombre no puede tener más de 255 caracteres.',
            'mantNombre.unique' => 'Ya existe un mantenimiento con ese nombre.',
            'mantFechaInicio.required' => 'La fecha de inicio es obligatoria.',
            'mantFechaInicio.date' => 'La fecha de inicio no es válida.',
            'mantFechaFin.required' => 'La fecha de fin es obligatoria.',
            'mantFechaFin.date' => 'La fecha de fin no es válida.',
            'mantFechaFin.after_or_equal' => 'La fecha de fin debe ser igual o posterior a la fecha de inicio.',
        ];
    }

    protected function horarioRules(): array
    {
        return [
            'horVehiculoId' => ['required', 'exists:vehicles,id'],
            'horResponsableId' => ['required', 'exists:employees,id'],
            'horTipo' => ['required', Rule::in(['Preventivo', 'Limpieza', 'Reparacion'])],
            'horDiaSemana' => ['required', Rule::in(['Lunes', 'Martes', 'Miercoles', 'Jueves', 'Viernes', 'Sabado', 'Domingo'])],
            'horHoraInicio' => ['required', 'date_format:H:i'],
            'horHoraFin' => ['required', 'date_format:H:i', 'after:horHoraInicio'],
        ];
    }

    protected function horarioMessages(): array
    {
        return [
            'horVehiculoId.required' => 'El vehículo es obligatorio.',
            'horVehiculoId.exists' => 'El vehículo seleccionado no existe.',
            'horResponsableId.required' => 'El responsable es obligatorio.',
            'horResponsableId.exists' => 'El responsable seleccionado no existe.',
            'horTipo.required' => 'El tipo de mantenimiento es obligatorio.',
            'horTipo.in' => 'El tipo de mantenimiento no es válido.',
            'horDiaSemana.required' => 'El día de la semana es obligatorio.',
            'horDiaSemana.in' => 'El día de la semana no es válido.',
            'horHoraInicio.required' => 'La hora de inicio es obligatoria.',
            'horHoraInicio.date_format' => 'La hora de inicio no es válida.',
            'horHoraFin.required' => 'La hora de fin es obligatoria.',
            'horHoraFin.date_format' => 'La hora de fin no es válida.',
            'horHoraFin.after' => 'La hora de fin debe ser posterior a la hora de inicio.',
        ];
    }

    protected function detalleRules(): array
    {
        return [
            'detObservacion' => ['nullable', 'string', 'max:1000'],
            'detRealizado' => ['boolean'],
            'detImagen' => ['nullable', 'image', 'max:2048'],
        ];
    }

    protected function detalleMessages(): array
    {
        return [
            'detObservacion.max' => 'La observación no puede tener más de 1000 caracteres.',
            'detImagen.image' => 'El archivo debe ser una imagen.',
            'detImagen.max' => 'La imagen no puede superar los 2MB.',
        ];
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function mantenimientos()
    {
        return Mantenimiento::query()
            ->when($this->search !== '', function ($query) {
                $query->where('nombre', 'like', '%' . $this->search . '%');
            })
            ->withCount('horarios')
            ->orderBy('fecha_inicio', 'desc')
            ->paginate($this->perPage);
    }

    #[Computed]
    public function vehicles()
    {
        return Vehicle::orderBy('name')->get();
    }

    #[Computed]
    public function employees()
    {
        return Employee::orderBy('first_name')->get();
    }

    #[Computed]
    public function horarios()
    {
        if (!$this->mantViewId) {
            return collect();
        }

        return HorarioMantenimiento::query()
            ->where('mantenimiento_id', $this->mantViewId)
            ->when($this->search !== '', function ($query) {
                $query->where(function ($q) {
                    $q->where('dia_semana', 'like', '%' . $this->search . '%')
                        ->orWhere('tipo', 'like', '%' . $this->search . '%')
                        ->orWhereHas('vehiculo', fn($v) => $v->where('name', 'like', '%' . $this->search . '%'))
                        ->orWhereHas('responsable', fn($r) => $r->where('first_name', 'like', '%' . $this->search . '%')
                            ->orWhere('last_name', 'like', '%' . $this->search . '%'));
                });
            })
            ->with(['vehiculo', 'responsable'])
            ->withCount('detalles')
            ->orderBy('dia_semana')
            ->orderBy('hora_inicio')
            ->get();
    }

    #[Computed]
    public function currentMantenimiento()
    {
        if (!$this->mantViewId) {
            return null;
        }

        return Mantenimiento::find($this->mantViewId);
    }

    #[Computed]
    public function detalles()
    {
        if (!$this->detViewHorarioId) {
            return collect();
        }

        return DetalleHorarioMantenimiento::query()
            ->where('horario_id', $this->detViewHorarioId)
            ->orderBy('fecha')
            ->get();
    }

    public function openCreate(): void
    {
        $this->resetMantForm();
        Flux::modal('mantenimiento-form')->show();
    }

    public function openEdit(int $id): void
    {
        $mant = Mantenimiento::findOrFail($id);
        $this->mantEditingId = $mant->id;
        $this->mantNombre = $mant->nombre;
        $this->mantFechaInicio = $mant->fecha_inicio->format('Y-m-d');
        $this->mantFechaFin = $mant->fecha_fin->format('Y-m-d');
        Flux::modal('mantenimiento-form')->show();
    }

    public function closeMantForm(): void
    {
        $this->resetMantForm();
        Flux::modal('mantenimiento-form')->close();
    }

    public function save(): void
    {
        $this->validate();

        if ($this->mantEditingId) {
            $mant = Mantenimiento::findOrFail($this->mantEditingId);

            $overlap = $this->checkMantenimientoOverlap($this->mantFechaInicio, $this->mantFechaFin, $this->mantEditingId);
            if ($overlap) {
                $this->addError('mantFechaInicio', 'El período se solapa con otro mantenimiento registrado: ' . $overlap->nombre . ' (' . $overlap->fecha_inicio->format('d/m/Y') . ' - ' . $overlap->fecha_fin->format('d/m/Y') . ').');
                return;
            }

            $mant->update([
                'nombre' => $this->mantNombre,
                'fecha_inicio' => $this->mantFechaInicio,
                'fecha_fin' => $this->mantFechaFin,
            ]);
            Flux::toast(variant: 'success', text: 'Mantenimiento actualizado correctamente.');
        } else {
            $overlap = $this->checkMantenimientoOverlap($this->mantFechaInicio, $this->mantFechaFin);
            if ($overlap) {
                $this->addError('mantFechaInicio', 'El período se solapa con otro mantenimiento registrado: ' . $overlap->nombre . ' (' . $overlap->fecha_inicio->format('d/m/Y') . ' - ' . $overlap->fecha_fin->format('d/m/Y') . ').');
                return;
            }

            Mantenimiento::create([
                'nombre' => $this->mantNombre,
                'fecha_inicio' => $this->mantFechaInicio,
                'fecha_fin' => $this->mantFechaFin,
            ]);
            Flux::toast(variant: 'success', text: 'Mantenimiento creado correctamente.');
        }

        $this->closeMantForm();
    }

    public function confirmDelete(int $id): void
    {
        $this->mantDeletingId = $id;
        Flux::modal('confirm-delete-mantenimiento')->show();
    }

    public function delete(): void
    {
        if (!$this->mantDeletingId) {
            return;
        }

        $mant = Mantenimiento::findOrFail($this->mantDeletingId);

        $horariosCount = $mant->horarios()->count();
        if ($horariosCount > 0) {
            Flux::toast(variant: 'danger', text: 'No se puede eliminar el mantenimiento porque tiene ' . $horariosCount . ' horario(s) asignados.');
            $this->mantDeletingId = null;
            Flux::modal('confirm-delete-mantenimiento')->close();
            return;
        }

        $mant->delete();

        Flux::toast(variant: 'success', text: 'Mantenimiento eliminado correctamente.');
        $this->mantDeletingId = null;
        Flux::modal('confirm-delete-mantenimiento')->close();
    }

    public function showHorarios(int $id): void
    {
        $this->mantViewId = $id;
        $this->resetPage();
    }

    public function backToMantenimientos(): void
    {
        $this->mantViewId = null;
        $this->horEditingId = null;
        $this->horDeletingId = null;
        $this->detViewHorarioId = null;
        $this->detEditingId = null;
    }

    public function openHorCreate(): void
    {
        $this->resetHorForm();
        Flux::modal('horario-form')->show();
    }

    public function openHorEdit(int $id): void
    {
        $hor = HorarioMantenimiento::findOrFail($id);
        $this->horEditingId = $hor->id;
        $this->horVehiculoId = $hor->vehiculo_id;
        $this->horResponsableId = $hor->responsable_id;
        $this->horTipo = $hor->tipo;
        $this->horDiaSemana = $hor->dia_semana;
        $this->horHoraInicio = Carbon::parse($hor->hora_inicio)->format('H:i');
        $this->horHoraFin = Carbon::parse($hor->hora_fin)->format('H:i');
        Flux::modal('horario-form')->show();
    }

    public function closeHorForm(): void
    {
        $this->resetHorForm();
        Flux::modal('horario-form')->close();
    }

    public function saveHor(): void
    {
        $this->validate($this->horarioRules(), $this->horarioMessages());

        if ($this->horEditingId) {
            $vehiculoConflict = $this->checkHorarioConflict(
                $this->horVehiculoId,
                $this->horDiaSemana,
                $this->horHoraInicio,
                $this->horHoraFin,
                $this->horEditingId
            );
            if ($vehiculoConflict) {
                $this->addError('horHoraInicio', 'El horario entra en conflicto con otro horario registrado para el mismo vehículo, día y rango horario.');
                return;
            }

            $responsableConflict = $this->checkResponsableConflict(
                $this->horResponsableId,
                $this->horDiaSemana,
                $this->horHoraInicio,
                $this->horHoraFin,
                $this->horEditingId
            );
            if ($responsableConflict) {
                $this->addError('horHoraInicio', 'El responsable ya tiene un horario asignado en otro mantenimiento que se cruza con el mismo día y rango horario.');
                return;
            }

            $hor = HorarioMantenimiento::findOrFail($this->horEditingId);

            $mant = Mantenimiento::findOrFail($hor->mantenimiento_id);
            $existingDates = $hor->detalles()->pluck('fecha')->map(fn($d) => $d->format('Y-m-d'));

            $hor->update([
                'vehiculo_id' => $this->horVehiculoId,
                'responsable_id' => $this->horResponsableId,
                'tipo' => $this->horTipo,
                'dia_semana' => $this->horDiaSemana,
                'hora_inicio' => $this->horHoraInicio,
                'hora_fin' => $this->horHoraFin,
            ]);

            $this->regenerateDetalles($hor, $mant, $existingDates);

            Flux::toast(variant: 'success', text: 'Horario actualizado correctamente.');
        } else {
            $vehiculoConflict = $this->checkHorarioConflict(
                $this->horVehiculoId,
                $this->horDiaSemana,
                $this->horHoraInicio,
                $this->horHoraFin
            );
            if ($vehiculoConflict) {
                $this->addError('horHoraInicio', 'El horario entra en conflicto con otro horario registrado para el mismo vehículo, día y rango horario.');
                return;
            }

            $responsableConflict = $this->checkResponsableConflict(
                $this->horResponsableId,
                $this->horDiaSemana,
                $this->horHoraInicio,
                $this->horHoraFin
            );
            if ($responsableConflict) {
                $this->addError('horHoraInicio', 'El responsable ya tiene un horario asignado en otro mantenimiento que se cruza con el mismo día y rango horario.');
                return;
            }

            $mant = Mantenimiento::findOrFail($this->mantViewId);

            $hor = HorarioMantenimiento::create([
                'mantenimiento_id' => $this->mantViewId,
                'vehiculo_id' => $this->horVehiculoId,
                'responsable_id' => $this->horResponsableId,
                'tipo' => $this->horTipo,
                'dia_semana' => $this->horDiaSemana,
                'hora_inicio' => $this->horHoraInicio,
                'hora_fin' => $this->horHoraFin,
            ]);

            $this->generateDetalles($hor, $mant);

            Flux::toast(variant: 'success', text: 'Horario creado correctamente.');
        }

        $this->closeHorForm();
    }

    public function confirmHorDelete(int $id): void
    {
        $this->horDeletingId = $id;
        Flux::modal('confirm-delete-horario')->show();
    }

    public function deleteHor(): void
    {
        if (!$this->horDeletingId) {
            return;
        }

        $hor = HorarioMantenimiento::findOrFail($this->horDeletingId);

        foreach ($hor->detalles as $detalle) {
            if ($detalle->imagen) {
                Storage::disk('public')->delete($detalle->imagen);
            }
        }

        $hor->delete();

        Flux::toast(variant: 'success', text: 'Horario eliminado correctamente.');
        $this->horDeletingId = null;
        Flux::modal('confirm-delete-horario')->close();
    }

    public function showDetalle(int $horarioId): void
    {
        $this->detViewHorarioId = $horarioId;
        $this->detEditingId = null;
        Flux::modal('detalle-view')->show();
    }

    public function closeDetalleView(): void
    {
        $this->detViewHorarioId = null;
        $this->detEditingId = null;
        Flux::modal('detalle-view')->close();
    }

    public function openDetEdit(int $id): void
    {
        $det = DetalleHorarioMantenimiento::findOrFail($id);
        $this->detEditingId = $det->id;
        $this->detObservacion = $det->observacion ?? '';
        $this->detRealizado = $det->realizado;
        $this->detImagen = null;
        $this->detRemoveImage = false;
        Flux::modal('detalle-edit')->show();
    }

    public function closeDetEdit(): void
    {
        $this->resetDetForm();
        Flux::modal('detalle-edit')->close();
    }

    public function saveDet(): void
    {
        $this->validate($this->detalleRules(), $this->detalleMessages());

        if (!$this->detEditingId) {
            return;
        }

        $det = DetalleHorarioMantenimiento::findOrFail($this->detEditingId);

        $data = [
            'observacion' => $this->detObservacion ?: null,
            'realizado' => $this->detRealizado,
        ];

        if ($this->detRemoveImage && $det->imagen) {
            Storage::disk('public')->delete($det->imagen);
            $data['imagen'] = null;
        }

        if ($this->detImagen) {
            if ($det->imagen) {
                Storage::disk('public')->delete($det->imagen);
            }
            $data['imagen'] = $this->detImagen->store('detalle-horarios', 'public');
        }

        $det->update($data);

        $this->closeDetEdit();
        Flux::toast(variant: 'success', text: 'Detalle actualizado correctamente.');
    }

    public function openImagePreview(string $url): void
    {
        $this->detPreviewUrl = $url;
        Flux::modal('image-preview')->show();
    }

    public function closeImagePreview(): void
    {
        $this->detPreviewUrl = null;
        Flux::modal('image-preview')->close();
    }

    private function checkMantenimientoOverlap(string $fechaInicio, string $fechaFin, ?int $excludeId = null): ?Mantenimiento
    {
        $query = Mantenimiento::where(function ($q) use ($fechaInicio, $fechaFin) {
            $q->where(function ($q) use ($fechaInicio, $fechaFin) {
                $q->where('fecha_inicio', '<=', $fechaFin)
                    ->where('fecha_fin', '>=', $fechaInicio);
            });
        });

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->first();
    }

    private function checkHorarioConflict(int $vehiculoId, string $diaSemana, string $horaInicio, string $horaFin, ?int $excludeId = null): ?HorarioMantenimiento
    {
        $query = HorarioMantenimiento::where('vehiculo_id', $vehiculoId)
            ->where('dia_semana', $diaSemana)
            ->where(function ($q) use ($horaInicio, $horaFin) {
                $q->where(function ($q) use ($horaInicio, $horaFin) {
                    $q->where('hora_inicio', '<', $horaFin)
                        ->where('hora_fin', '>', $horaInicio);
                });
            });

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->first();
    }

    private function checkResponsableConflict(int $responsableId, string $diaSemana, string $horaInicio, string $horaFin, ?int $excludeId = null): ?HorarioMantenimiento
    {
        $query = HorarioMantenimiento::where('responsable_id', $responsableId)
            ->where('dia_semana', $diaSemana)
            ->where(function ($q) use ($horaInicio, $horaFin) {
                $q->where(function ($q) use ($horaInicio, $horaFin) {
                    $q->where('hora_inicio', '<', $horaFin)
                        ->where('hora_fin', '>', $horaInicio);
                });
            });

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->first();
    }

    private function generateDetalles(HorarioMantenimiento $horario, Mantenimiento $mantenimiento): void
    {
        $dayMap = [
            'Lunes' => Carbon::MONDAY,
            'Martes' => Carbon::TUESDAY,
            'Miercoles' => Carbon::WEDNESDAY,
            'Jueves' => Carbon::THURSDAY,
            'Viernes' => Carbon::FRIDAY,
            'Sabado' => Carbon::SATURDAY,
            'Domingo' => Carbon::SUNDAY,
        ];

        $targetDay = $dayMap[$horario->dia_semana] ?? Carbon::MONDAY;
        $start = Carbon::parse($mantenimiento->fecha_inicio->format('Y-m-d'));
        $end = Carbon::parse($mantenimiento->fecha_fin->format('Y-m-d'));

        $current = $start->copy()->next($targetDay);
        if ($current->gt($end)) {
            return;
        }

        if ($start->dayOfWeek === $targetDay) {
            $current = $start->copy();
        }

        $existingDates = $horario->detalles()->pluck('fecha')->map(fn($d) => $d->format('Y-m-d'))->toArray();

        while ($current->lte($end)) {
            $dateStr = $current->format('Y-m-d');
            if (!in_array($dateStr, $existingDates)) {
                $horario->detalles()->create([
                    'fecha' => $dateStr,
                    'realizado' => false,
                ]);
            }
            $current->addWeek();
        }
    }

    private function regenerateDetalles(HorarioMantenimiento $horario, Mantenimiento $mantenimiento, $existingDates): void
    {
        $dayMap = [
            'Lunes' => Carbon::MONDAY,
            'Martes' => Carbon::TUESDAY,
            'Miercoles' => Carbon::WEDNESDAY,
            'Jueves' => Carbon::THURSDAY,
            'Viernes' => Carbon::FRIDAY,
            'Sabado' => Carbon::SATURDAY,
            'Domingo' => Carbon::SUNDAY,
        ];

        $targetDay = $dayMap[$horario->dia_semana] ?? Carbon::MONDAY;
        $start = Carbon::parse($mantenimiento->fecha_inicio->format('Y-m-d'));
        $end = Carbon::parse($mantenimiento->fecha_fin->format('Y-m-d'));

        $current = $start->copy()->next($targetDay);
        if ($start->dayOfWeek === $targetDay) {
            $current = $start->copy();
        }

        $newDates = [];
        while ($current->lte($end)) {
            $newDates[] = $current->format('Y-m-d');
            $current->addWeek();
        }

        $existingDateStrings = $existingDates->toArray();
        $datesToAdd = array_diff($newDates, $existingDateStrings);
        $datesToRemove = array_diff($existingDateStrings, $newDates);

        foreach ($datesToAdd as $dateStr) {
            $horario->detalles()->create([
                'fecha' => $dateStr,
                'realizado' => false,
            ]);
        }

        if (!empty($datesToRemove)) {
            $horario->detalles()
                ->whereIn('fecha', $datesToRemove)
                ->where('realizado', false)
                ->delete();
        }
    }

    private function resetMantForm(): void
    {
        $this->reset([
            'mantNombre', 'mantFechaInicio', 'mantFechaFin', 'mantEditingId',
        ]);
        $this->resetErrorBag();
        $this->resetValidation();
    }

    private function resetHorForm(): void
    {
        $this->reset([
            'horEditingId', 'horVehiculoId', 'horResponsableId',
            'horTipo', 'horDiaSemana', 'horHoraInicio', 'horHoraFin',
        ]);
        $this->resetErrorBag();
        $this->resetValidation();
    }

    private function resetDetForm(): void
    {
        $this->reset([
            'detEditingId', 'detObservacion', 'detRealizado', 'detImagen', 'detRemoveImage',
        ]);
        $this->detRealizado = false;
        $this->resetErrorBag();
        $this->resetValidation();
    }
}; ?>

<div class="min-h-screen bg-white p-6 text-[#333333]">
    @if ($mantViewId)
        @include('pages.vehicles.maintenance.components.horarios-view')
    @else
        @include('pages.vehicles.maintenance.components.mantenimiento-table')
    @endif

    @include('pages.vehicles.maintenance.components.mantenimiento-form')
    @include('pages.vehicles.maintenance.components.mantenimiento-delete')
    @include('pages.vehicles.maintenance.components.horario-form')
    @include('pages.vehicles.maintenance.components.horario-delete')
    @include('pages.vehicles.maintenance.components.detalle-view')
    @include('pages.vehicles.maintenance.components.detalle-edit')

    <flux:modal name="image-preview" wire:close="closeImagePreview" class="md:w-[600px]">
        <div class="text-center">
            <flux:heading size="lg" class="mb-4">{{ __('Vista previa de la imagen') }}</flux:heading>
            @if ($detPreviewUrl)
                <img src="{{ $detPreviewUrl }}" alt="{{ __('Imagen del detalle') }}"
                    class="max-w-full h-auto rounded-lg mx-auto shadow-lg" />
            @endif
            <div class="flex justify-end pt-4 border-t border-[#E0E0E0] mt-4">
                <flux:button type="button" variant="ghost" wire:click="closeImagePreview" class="text-[#333333]">
                    {{ __('Cerrar') }}
                </flux:button>
            </div>
        </div>
    </flux:modal>
</div>
