<?php

use App\Models\Shift;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use Flux\Flux;


new class extends Component {
    use WithPagination;

    public string $search = '';
    public bool $showModal = false;
    public ?int $editingId = null;

    public string $name = '';
    public string $description = '';
    public string $hour_in = '';
    public string $hour_out = '';
    public ?int $deletingId = null;

    protected function rules(): array
    {
        return [
            'name' => [
                'required', 
                'string', 
                'max:100', 
                Rule::unique('shifts', 'name')
                ->ignore($this->editingId),],
            'description' => ['nullable', 'string'],
            'hour_in' => ['required', 'date_format:H:i'],
            'hour_out' => ['required', 'date_format:H:i'],
        ];
    }
    protected function messages(): array
    {
        return [
            'name.required' => __('El nombre es obligatorio.'),
            'name.string' => __('El nombre debe ser un texto.'),
            'name.max' => __('El nombre no puede tener más de 100 caracteres.'),
            'name.unique' => __('Ya existe un turno con ese nombre.'), 
            'hour_in.required' => __('La hora de inicio es obligatoria.'),
            'hour_in.date_format' => __('La hora de inicio no es válida.'),
            'hour_out.required' => __('La hora de finalización es obligatoria.'),
            'hour_out.date_format' => __('La hora de finalización no es válida.'),
        ];
    }

    private function hasOverlap(string $hourIn, string $hourOut): bool
    {
        $newIntervals = $this->buildIntervals($hourIn, $hourOut);

        $shifts = Shift::query()
            ->when(
                $this->editingId,
                fn ($query) => $query->where('id', '!=', $this->editingId)
            )
            ->get();

        foreach ($shifts as $shift) {
            $existingIntervals = $this->buildIntervals(
                $shift->hour_in,
                $shift->hour_out
            );

            foreach ($newIntervals as $new) {
                foreach ($existingIntervals as $existing) {

                    // [inicio, fin)
                    if (
                        $new['start'] < $existing['end'] &&
                        $new['end'] > $existing['start']
                    ) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    private function buildIntervals(string $start, string $end): array
    {
        $startMinutes = $this->toMinutes($start);
        $endMinutes = $this->toMinutes($end);

        // mismo día
        if ($startMinutes < $endMinutes) {
            return [[
                'start' => $startMinutes,
                'end' => $endMinutes,
            ]];
        }

        // cruza medianoche
        return [
            [
                'start' => $startMinutes,
                'end' => 1440,
            ],
            [
                'start' => 0,
                'end' => $endMinutes,
            ]
        ];
    }

    private function toMinutes(string $time): int
    {
        [$hour, $minute] = explode(':', substr($time, 0, 5));

        return ((int) $hour * 60) + (int) $minute;
    }

    public function save(): void
    {
        $validated = $this->validate();

        if ($validated['hour_in'] === $validated['hour_out']) {
                $this->addError(
                    'hour_out',
                    __('La hora de inicio y fin no pueden ser iguales.')
                );

                return;
            }

        if ($this->hasOverlap(
            $validated['hour_in'],
            $validated['hour_out']
        )) {
            $this->addError(
                'hour_in',
                __('El turno se solapa con otro turno existente.')
            );

            return;
        }
        
        if ($this->editingId) {
            $shift = Shift::findOrFail($this->editingId);
            $shift->update($validated);
            Flux::toast(variant: 'success', text: __('Turno actualizado.'));
        } else {
            Shift::create($validated);
            Flux::toast(variant: 'success', text: __('Turno creado.'));
        }

        $this->resetForm();
        $this->showModal = false;
        Flux::modal('shift-form')->close();
    }

    public function openCreate(): void
    {
        $this->resetForm();
        $this->showModal = true;
        Flux::modal('shift-form')->show();
    }

    public function openEdit(int $id): void
    {
        $shift = Shift::findOrFail($id);

        $this->editingId = $shift->id;
        $this->name = $shift->name;
        $this->description = $shift->description ?? '';
        $this->hour_in = substr($shift->hour_in, 0, 5);
        $this->hour_out = substr($shift->hour_out, 0, 5);

        $this->showModal = true;

        Flux::modal('shift-form')->show();
    }

    public function closeModal(): void
    {
        $this->resetForm();
        $this->showModal = false;
        Flux::modal('shift-form')->close();
    }

    public function confirmDelete(int $id): void
    {
        $shift = Shift::findOrFail($id);

        $assignedCount = $this->assignedCount($shift);

        if ($assignedCount > 0) {
            Flux::toast(
                variant: 'warning',
                text: __('No se puede eliminar este turno porque tiene :count programacion(es) asignadas.', [
                    'count' => $assignedCount
                ])
            );

            return;
        }

        $this->deletingId = $id;

        Flux::modal('confirm-delete')->show();
    }

    public function delete(): void
    {
        if (!$this->deletingId) return;

        $shift = Shift::findOrFail($this->deletingId);
        $assignedCount = $this->assignedCount($shift);
        if ($assignedCount > 0) {
            Flux::toast(
                variant: 'warning',
                text: __('No se puede eliminar este turno porque tiene :count programacion(es) asignadas.', ['count' => $assignedCount])
            );
            $this->deletingId = null;
            Flux::modal('confirm-delete')->close();
            return;
        }
        $shift->delete();
        Flux::toast(variant: 'success', text: __('Turno eliminado.'));

        if ($this->editingId === $this->deletingId) {
            $this->resetForm();
        }
        $this->deletingId = null;
        Flux::modal('confirm-delete')->close();
    }

    #[Computed]
    public function shifts()
    {
        return Shift::query()
            ->when($this->search !== '', function ($query) {
                $query->where('name', 'like', '%'.$this->search.'%')
                    ->orWhere('description', 'like', '%'.$this->search.'%');
            })
            ->orderBy('name')
            ->paginate(10);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }


    private function assignedCount(Shift $shifts): int
    {
        return $shifts->schedulings()->count();
    }

    private function resetForm(): void
    {
        $this->reset([
            'name',
            'description',
            'hour_in',
            'hour_out',
            'editingId'
        ]);
        $this->resetErrorBag();
        $this->resetValidation();
    }
}; ?>

<div class="min-h-screen bg-white p-6 text-[#333333]">
    <div class="flex items-start justify-between mb-6">
        <div>
            <h1 class="text-3xl font-bold text-[#2E8B57]">
                {{ __('Gestion de turnos') }}
            </h1>
            <p class="text-sm text-[#333333] mt-1">
                {{ __('Administracion de turnos disponibles para la recolección de basura.') }}
            </p>
        </div>

        <flux:button
            wire:click="openCreate"
            variant="primary"
            icon="plus-circle"
            class="bg-[#2E8B57]! text-white cursor-pointer hover:bg-[#257046]!"
        >
            {{ __('Nuevo Turno') }}
        </flux:button>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-[#A5D6A7] p-5 mb-6">
        <label class="block text-sm font-medium text-[#333333] mb-2">
            {{ __('Buscar por nombre o descripcion') }}
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
                        <th class="px-6 py-4 text-left">{{ __('Nombre') }}</th>
                        <th class="px-6 py-4 text-left">{{ __('Hora Inicio') }}</th>
                        <th class="px-6 py-4 text-left">{{ __('Hora Fin') }}</th>
                        <th class="px-6 py-4 text-left">{{ __('Descripcion') }}</th>
                        <th class="px-6 py-4 text-right">{{ __('Acciones') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($this->shifts as $i => $shift)
                        <tr wire:key="shift-{{ $shift->id }}"
                            class="{{ $i % 2 === 0 ? 'bg-white' : 'bg-[#A5D6A7]/20' }} border-b border-[#A5D6A7] hover:bg-[#A5D6A7]/30 transition">
                            <td class="px-6 py-4 text-sm font-bold text-[#333333] uppercase">
                                {{ $shift->name }}
                            </td>
                            <td class="px-6 py-4 text-sm">
                                <span class="inline-flex items-center px-3 py-1 rounded-lg bg-green-100 text-green-800 font-semibold">
                                    {{ substr($shift->hour_in, 0, 5) }}
                                </span>
                            </td>

                            <td class="px-6 py-4 text-sm">
                                <span class="inline-flex items-center px-3 py-1 rounded-lg bg-red-100 text-red-800 font-semibold">
                                    {{ substr($shift->hour_out, 0, 5) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-[#333333]">
                                {{ $shift->description ?: __('Sin descripcion') }}
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex justify-end gap-2">
                                    <button wire:click="openEdit({{ $shift->id }})" class="inline-flex h-8 w-8 items-center justify-center rounded-md text-[#F4C542] hover:bg-[#F4C542]/20 transition" title="Editar" aria-label="Editar">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16.862 3.487a2.25 2.25 0 013.182 3.182l-9.193 9.193a4.5 4.5 0 01-1.897 1.13l-3.17 1.056 1.056-3.17a4.5 4.5 0 011.13-1.897l9.193-9.193z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.5 7.5L16.5 4.5" />
                                        </svg>
                                    </button>
                                    <button wire:click="confirmDelete({{ $shift->id }})" class="inline-flex h-8 w-8 items-center justify-center rounded-md text-[#E53935] hover:bg-[#E53935]/20 transition" title="Eliminar" aria-label="Eliminar">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 7h12M9 7V5a1 1 0 011-1h4a1 1 0 011 1v2M7 7l1 12a2 2 0 002 2h4a2 2 0 002-2l1-12" />
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="px-6 py-10 text-center text-sm text-[#333333]">
                                {{ __('No hay turnos registrados.') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="px-6 py-4 border-t border-[#A5D6A7]">
            {{ $this->shifts->links() }}
        </div>
    </div>

    <flux:modal name="shift-form" wire:close="closeModal" class="md:w-130">
        <form wire:submit="save" class="space-y-6" novalidate>
            <div>
                <flux:heading size="lg">
                    {{ $editingId ? __('Editar turno') : __('Nuevo turno') }}
                </flux:heading>
                <flux:text class="mt-2">
                    {{ __('Ingresa el nombre y una descripcion opcional.') }}
                </flux:text>
            </div>

            <flux:input
                wire:model="name"
                :label="__('Nombre')"
                placeholder="{{ __('Nombre del turno') }}"
                required
            />
            <div class="grid grid-cols-2 gap-4">
                <flux:input
                    type="time"
                    wire:model="hour_in"
                    :label="__('Hora de inicio')"
                    required
                />

                <flux:input
                    type="time"
                    wire:model="hour_out"
                    :label="__('Hora de finalización')"
                    required
                />
            </div>
            <flux:textarea
                wire:model="description"
                :label="__('Descripcion')"
                rows="3"
                placeholder="{{ __('Descripcion') }}"
            />

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button type="button" variant="ghost" wire:click="closeModal" class="text-[#333333]">
                        {{ __('Cancelar') }}
                    </flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary" class="bg-[#2E8B57] text-white hover:bg-[#257046]">
                    {{ $editingId ? __('Actualizar') : __('Guardar') }}
                </flux:button>
            </div>
        </form>
    </flux:modal>

    <flux:modal name="confirm-delete" class="md:w-100">
        <div class="space-y-5">
            <div class="flex items-start gap-4 px-6 pt-4">
                <div class="shrink-0 w-10 h-10 rounded-full bg-red-100 flex items-center justify-center">
                    <svg class="h-5 w-5 text-[#E53935]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>
                <div>
                    <flux:heading size="lg" class="text-[#E53935]">Confirmar eliminacion</flux:heading>
                    <flux:text class="mt-1 text-sm text-[#666666]">¿Está seguro de que desea eliminar este turno? Esta acción no se puede deshacer.</flux:text>
                </div>
            </div>
            <div class="px-6 py-4 bg-[#F5F5F5] border-t border-[#E0E0E0] flex justify-end gap-3">
                <flux:button x-on:click="Flux.modal('confirm-delete').close()" type="button" variant="ghost" class="text-[#333333]">Cancelar</flux:button>
                <flux:button wire:click="delete" variant="danger" class="bg-[#E53935] text-white hover:bg-[#C62828]">Eliminar</flux:button>
            </div>
        </div>
    </flux:modal>
</div>
