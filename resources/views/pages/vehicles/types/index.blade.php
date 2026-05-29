<?php

use App\Models\VehicleType;
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

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
        ];
    }

    public function save(): void
    {
        $validated = $this->validate();

        if ($this->editingId) {
            $vehicleType = VehicleType::findOrFail($this->editingId);
            $vehicleType->update($validated);
            Flux::toast(variant: 'success', text: __('Tipo de vehiculo actualizado.'));
        } else {
            VehicleType::create($validated);
            Flux::toast(variant: 'success', text: __('Tipo de vehiculo creado.'));
        }

        $this->resetForm();
        $this->showModal = false;
        Flux::modal('vehicle-type-form')->close();
    }

    public function openCreate(): void
    {
        $this->resetForm();
        $this->showModal = true;
        Flux::modal('vehicle-type-form')->show();
    }

    public function openEdit(int $id): void
    {
        $vehicleType = VehicleType::findOrFail($id);
        $this->editingId = $vehicleType->id;
        $this->name = $vehicleType->name;
        $this->description = $vehicleType->description ?? '';
        $this->showModal = true;
        Flux::modal('vehicle-type-form')->show();
    }

    public function closeModal(): void
    {
        $this->resetForm();
        $this->showModal = false;
        Flux::modal('vehicle-type-form')->close();
    }

    public function delete(int $id): void
    {
        $vehicleType = VehicleType::findOrFail($id);
        $vehicleType->delete();
        Flux::toast(variant: 'success', text: __('Tipo de vehiculo eliminado.'));

        if ($this->editingId === $id) {
            $this->resetForm();
        }
    }

    #[Computed]
    public function vehicleTypes()
    {
        return VehicleType::query()
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

    private function resetForm(): void
    {
        $this->reset(['name', 'description', 'editingId']);
        $this->resetErrorBag();
        $this->resetValidation();
    }
}; ?>

<div class="min-h-screen bg-white p-6 text-[#333333]">
    <div class="flex items-start justify-between mb-6">
        <div>
            <h1 class="text-3xl font-bold text-[#2E8B57]">
                {{ __('Gestion de tipos de vehiculos') }}
            </h1>
            <p class="text-sm text-[#333333] mt-1">
                {{ __('Administracion de tipos de vehiculos disponibles para el parque automotor.') }}
            </p>
        </div>

        <flux:button
            wire:click="openCreate"
            variant="primary"
            icon="plus-circle"
            class="bg-[#2E8B57] text-white"
        >
            {{ __('Anadir Tipo') }}
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
            <button class="px-6 py-2.5 bg-[#2E8B57] text-white text-sm font-medium rounded-lg">
                {{ __('Filtrar') }}
            </button>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-[#A5D6A7] overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="bg-[#2E8B57] text-white text-xs font-bold uppercase tracking-wider">
                        <th class="px-6 py-4 text-left">{{ __('Nombre') }}</th>
                        <th class="px-6 py-4 text-left">{{ __('Descripcion') }}</th>
                        <th class="px-6 py-4 text-right">{{ __('Acciones') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($this->vehicleTypes as $i => $vehicleType)
                        <tr wire:key="vehicle-type-{{ $vehicleType->id }}"
                            class="{{ $i % 2 === 0 ? 'bg-white' : 'bg-[#A5D6A7]/20' }} border-b border-[#A5D6A7] hover:bg-[#A5D6A7]/30 transition">
                            <td class="px-6 py-4 text-sm font-bold text-[#333333] uppercase">
                                {{ $vehicleType->name }}
                            </td>
                            <td class="px-6 py-4 text-sm text-[#333333]">
                                {{ $vehicleType->description ?: __('Sin descripcion') }}
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex justify-end gap-2">
                                    <button wire:click="openEdit({{ $vehicleType->id }})"
                                            class="inline-flex h-9 w-9 items-center justify-center rounded-md bg-[#F4C542] text-[#333333] hover:bg-[#D8AC34]"
                                            title="{{ __('Editar') }}"
                                            aria-label="{{ __('Editar') }}">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16.862 3.487a2.25 2.25 0 013.182 3.182l-9.193 9.193a4.5 4.5 0 01-1.897 1.13l-3.17 1.056 1.056-3.17a4.5 4.5 0 011.13-1.897l9.193-9.193z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.5 7.5L16.5 4.5" />
                                        </svg>
                                    </button>
                                    <button wire:click="delete({{ $vehicleType->id }})"
                                            wire:confirm="{{ __('¿Eliminar este tipo de vehiculo?') }}"
                                            class="inline-flex h-9 w-9 items-center justify-center rounded-md bg-[#E53935] text-white hover:bg-[#C62828]"
                                            title="{{ __('Eliminar') }}"
                                            aria-label="{{ __('Eliminar') }}">
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
                                {{ __('No hay tipos de vehiculos registrados.') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="px-6 py-4 border-t border-[#A5D6A7]">
            {{ $this->vehicleTypes->links() }}
        </div>
    </div>

    <flux:modal name="vehicle-type-form" wire:close="closeModal" class="md:w-[520px]">
        <form wire:submit="save" class="space-y-6">
            <div>
                <flux:heading size="lg">
                    {{ $editingId ? __('Editar tipo de vehiculo') : __('Nuevo tipo de vehiculo') }}
                </flux:heading>
                <flux:text class="mt-2">
                    {{ __('Ingresa el nombre y una descripcion opcional.') }}
                </flux:text>
            </div>

            <flux:input
                wire:model="name"
                :label="__('Nombre')"
                placeholder="{{ __('Nombre del tipo de vehiculo') }}"
                required
            />

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
</div>
