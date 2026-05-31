<?php

use App\Models\VehicleColor;
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
    public string $code = '#104CAD';
    public string $description = '';

    public ?int $deletingId = null;

    protected function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('vehiclecolors', 'name')->ignore($this->editingId),
            ],
            'code' => [
                'required',
                'string',
                'max:7',
                'regex:/^#([A-Fa-f0-9]{6})$/',
                Rule::unique('vehiclecolors', 'code')->ignore($this->editingId),
            ],
            'description' => ['nullable', 'string'],
        ];
    }

    protected function messages(): array
    {
        return [
            'name.required' => __('El nombre es obligatorio.'),
            'name.string' => __('El nombre debe ser un texto.'),
            'name.max' => __('El nombre no puede tener más de 100 caracteres.'),
            'name.unique' => __('Ya existe un color con ese nombre.'),
            'code.required' => __('El código del color es obligatorio.'),
            'code.string' => __('El código del color debe ser un texto.'),
            'code.max' => __('El código del color no puede tener más de 7 caracteres.'),
            'code.regex' => __('El código debe tener formato hexadecimal, por ejemplo #104CAD.'),
            'code.unique' => __('Ya existe un color con ese código.'),
            'description.string' => __('La descripción debe ser un texto.'),
        ];
    }

    public function save(): void
    {
        $validated = $this->validate();
        $validated['code'] = strtoupper($validated['code']);

        if ($this->editingId) {
            $vehicleColor = VehicleColor::findOrFail($this->editingId);
            $vehicleColor->update($validated);
            Flux::toast(variant: 'success', text: __('Color actualizado.'));
        } else {
            VehicleColor::create($validated);
            Flux::toast(variant: 'success', text: __('Color creado.'));
        }

        $this->resetForm();
        $this->showModal = false;
        Flux::modal('vehicle-color-form')->close();
    }

    public function openCreate(): void
    {
        $this->resetForm();
        $this->showModal = true;
        Flux::modal('vehicle-color-form')->show();
    }

    public function openEdit(int $id): void
    {
        $vehicleColor = VehicleColor::findOrFail($id);
        $this->editingId = $vehicleColor->id;
        $this->name = $vehicleColor->name;
        $this->code = $vehicleColor->code;
        $this->description = $vehicleColor->description ?? '';
        $this->showModal = true;
        Flux::modal('vehicle-color-form')->show();
    }

    public function closeModal(): void
    {
        $this->resetForm();
        $this->showModal = false;
        Flux::modal('vehicle-color-form')->close();
    }

    public function confirmDelete(int $id): void
    {
        $this->deletingId = $id;
        Flux::modal('confirm-delete')->show();
    }

    public function delete(): void
    {
        if (!$this->deletingId) return;

        $vehicleColor = VehicleColor::findOrFail($this->deletingId);
        $vehicleColor->delete();
        Flux::toast(variant: 'success', text: __('Color eliminado.'));

        if ($this->editingId === $this->deletingId) {
            $this->resetForm();
        }
        $this->deletingId = null;
        Flux::modal('confirm-delete')->close();
    }

    #[Computed]
    public function colors()
    {
        return VehicleColor::query()
            ->when($this->search !== '', function ($query) {
                $query->where('name', 'like', '%'.$this->search.'%')
                    ->orWhere('code', 'like', '%'.$this->search.'%')
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
        $this->reset(['name', 'code', 'description', 'editingId']);
        $this->code = '#104CAD';
        $this->resetErrorBag();
        $this->resetValidation();
    }
}; ?>

<div class="min-h-screen bg-white p-6 text-[#333333]">
    <div class="flex items-start justify-between mb-6">
        <div>
            <h1 class="text-3xl font-bold text-[#2E8B57]">
                {{ __('Gestión de Colores') }}
            </h1>
            <p class="text-sm text-[#333333] mt-1">
                {{ __('Administración de colores registrados para la flota vehicular.') }}
            </p>
        </div>

        <flux:button
            wire:click="openCreate"
            variant="primary"
            icon="plus-circle"
            class="bg-[#2E8B57]! text-white cursor-pointer hover:bg-[#257046]!"
        >
            {{ __('Nuevo Color') }}
        </flux:button>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-[#A5D6A7] p-5 mb-6">
        <label class="block text-sm font-medium text-[#333333] mb-2">
            {{ __('Buscar por nombre, código o descripción') }}
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
                        <th class="px-6 py-4 text-left">{{ __('Color') }}</th>
                        <th class="px-6 py-4 text-left">{{ __('Nombre') }}</th>
                        <th class="px-6 py-4 text-left">{{ __('Código') }}</th>
                        <th class="px-6 py-4 text-left">{{ __('Descripción') }}</th>
                        <th class="px-6 py-4 text-right">{{ __('Acciones') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($this->colors as $i => $color)
                        <tr wire:key="vehicle-color-{{ $color->id }}"
                            class="{{ $i % 2 === 0 ? 'bg-white' : 'bg-[#A5D6A7]/20' }} border-b border-[#A5D6A7] hover:bg-[#A5D6A7]/30 transition">
                            <td class="px-6 py-4">
                                <div class="h-8 w-8 rounded-md ring-1 ring-black/10" style="background-color: {{ $color->code }};"></div>
                            </td>
                            <td class="px-6 py-4 text-sm font-bold text-[#333333] uppercase">
                                {{ $color->name }}
                            </td>
                            <td class="px-6 py-4 text-sm text-[#666666] font-mono">
                                {{ $color->code }}
                            </td>
                            <td class="px-6 py-4 text-sm text-[#333333]">
                                {{ $color->description ?: __('Sin descripción') }}
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex justify-end gap-2">
                                    <button wire:click="openEdit({{ $color->id }})"
                                            class="inline-flex h-9 w-9 items-center justify-center rounded-md bg-[#F4C542] text-[#333333] hover:bg-[#D8AC34]"
                                            title="{{ __('Editar') }}"
                                            aria-label="{{ __('Editar') }}">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16.862 3.487a2.25 2.25 0 013.182 3.182l-9.193 9.193a4.5 4.5 0 01-1.897 1.13l-3.17 1.056 1.056-3.17a4.5 4.5 0 011.13-1.897l9.193-9.193z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.5 7.5L16.5 4.5" />
                                        </svg>
                                    </button>
                                    <button wire:click="confirmDelete({{ $color->id }})"
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
                            <td colspan="5" class="px-6 py-10 text-center text-sm text-[#333333]">
                                {{ __('No hay colores registrados.') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="px-6 py-4 border-t border-[#A5D6A7]">
            {{ $this->colors->links() }}
        </div>
    </div>

    <flux:modal name="vehicle-color-form" wire:close="closeModal" class="md:w-130">
        <form wire:submit="save" class="space-y-6" novalidate>
            <div>
                <flux:heading size="lg">
                    {{ $editingId ? __('Editar color') : __('Nuevo color') }}
                </flux:heading>
                <flux:text class="mt-2">
                    {{ __('Ingresa el nombre, el código hexadecimal y una descripción opcional.') }}
                </flux:text>
            </div>

            <flux:input
                wire:model.live="name"
                :label="__('Nombre del Color')"
                placeholder="{{ __('Ej. Azul, Gris, Blanco') }}"
            />

            <div class="grid gap-3 sm:grid-cols-[1fr_auto] items-end">
                <flux:input
                    wire:model.live="code"
                    :label="__('Código del Color (RGB)')"
                    placeholder="#104CAD"
                />

                <label class="block">
                    <span class="sr-only">{{ __('Selector de color') }}</span>
                    <input
                        type="color"
                        wire:model.live="code"
                        class="h-10 w-14 cursor-pointer rounded-md border border-zinc-200 bg-white p-1"
                        aria-label="{{ __('Selector de color') }}"
                    />
                </label>
            </div>

            <div class="rounded-lg border border-[#A5D6A7] p-3" x-data="{ code: @entangle('code').live }">
                <div class="text-sm font-medium text-[#333333] mb-2">{{ __('Vista Previa del Color:') }}</div>
                <div class="h-16 rounded-lg flex items-center justify-center text-white font-bold shadow-sm" :style="`background-color: ${code}`">
                    <span x-text="code"></span>
                </div>
            </div>

            <flux:textarea
                wire:model.live="description"
                :label="__('Descripción')"
                placeholder="{{ __('Agregue una descripción del color') }}"
            />

            <div class="flex justify-end gap-2 pt-2">
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

    {{-- Modal confirmar eliminación --}}
    <flux:modal name="confirm-delete" class="md:w-[400px]">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg" class="text-red-500">
                    {{ __('Confirmar eliminación') }}
                </flux:heading>
                <flux:text class="mt-2 text-sm text-[#333333]">
                    {{ __('¿Estás seguro de que deseas eliminar este color? Esta acción no se puede deshacer.') }}
                </flux:text>
            </div>

            <div class="flex gap-3 justify-end pt-4 border-t border-[#E0E0E0]">
                <flux:button x-on:click="Flux.modal('confirm-delete').close()" type="button">
                    {{ __('Cancelar') }}
                </flux:button>
                <flux:button wire:click="delete" variant="danger" class="bg-[#E53935] text-white">
                    {{ __('Eliminar') }}
                </flux:button>
            </div>
        </div>
    </flux:modal>
</div>
