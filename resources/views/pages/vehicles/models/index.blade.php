<?php

use App\Models\BrandModel;
use App\Models\Brand;
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
    public ?int $brand_id = null;

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string'],
            'brand_id' => ['required', 'exists:brands,id'],
        ];
    }

    protected function messages(): array
    {
        return [
            'name.required' => __('El nombre es obligatorio.'),
            'name.max' => __('El nombre no puede tener mas de 150 caracteres.'),
            'brand_id.required' => __('La marca es obligatoria.'),
            'brand_id.exists' => __('La marca seleccionada no es válida.'),
        ];
    }

    public function save(): void
    {
        $validated = $this->validate();

        if ($this->editingId) {
            $model = BrandModel::findOrFail($this->editingId);
            $model->update($validated);
            Flux::toast(variant: 'success', text: __('Modelo actualizado.'));
        } else {
            // Auto-generación del código
            $brand = Brand::find($this->brand_id);
            $validated['code'] = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $this->name), 0, 3) . '-' . substr(preg_replace('/[^A-Za-z0-9]/', '', $brand->name), 0, 3) . '-' . date('y'));

            BrandModel::create($validated);
            Flux::toast(variant: 'success', text: __('Modelo creado.'));
        }

        $this->resetForm();
        $this->showModal = false;
        Flux::modal('model-form')->close();
    }

    public function openCreate(): void
    {
        $this->resetForm();
        $this->showModal = true;
        // If there's only one brand available, preselect it so validation
        // doesn't consider the field empty when the user doesn't interact.
        $brandCount = \App\Models\Brand::count();
        if ($brandCount === 1) {
            $onlyBrand = \App\Models\Brand::orderBy('name')->first();
            $this->brand_id = $onlyBrand ? $onlyBrand->id : null;
        }

        Flux::modal('model-form')->show();
    }

    public function openEdit(int $id): void
    {
        $model = BrandModel::findOrFail($id);
        $this->editingId = $model->id;
        $this->name = $model->name;
        $this->description = $model->description ?? '';
        $this->brand_id = $model->brand_id;
        $this->showModal = true;
        Flux::modal('model-form')->show();
    }

    public function closeModal(): void
    {
        $this->resetForm();
        $this->showModal = false;
        Flux::modal('model-form')->close();
    }

    public function delete(int $id): void
    {
        $model = BrandModel::findOrFail($id);
        $model->delete();
        Flux::toast(variant: 'success', text: __('Modelo eliminado.'));

        if ($this->editingId === $id) {
            $this->resetForm();
        }
    }

    #[Computed]
    public function models()
    {
        return BrandModel::query()
            ->with('brand')
            ->when($this->search !== '', function ($query) {
                $query->where('name', 'like', '%'.$this->search.'%')
                    ->orWhere('code', 'like', '%'.$this->search.'%')
                    ->orWhereHas('brand', function ($q) {
                        $q->where('name', 'like', '%'.$this->search.'%');
                    });
            })
            ->orderBy('name')
            ->paginate(10);
    }

    #[Computed]
    public function brands()
    {
        return Brand::orderBy('name')->get();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    private function resetForm(): void
    {
        $this->reset(['name', 'description', 'brand_id', 'editingId']);
        $this->resetErrorBag();
        $this->resetValidation();
    }
}; ?>

<!-- Blade template content copied from original file -->
<div class="min-h-screen bg-white p-6 text-[#333333]">
    {{-- Header --}}
    <div class="flex items-start justify-between mb-6">
        <div>
            <h1 class="text-3xl font-bold text-[#2E8B57]">
                {{ __('Gestión de Modelos') }}
            </h1>
            <p class="text-sm text-[#333333] mt-1">
                {{ __('Administración de modelos de vehículos registrados.') }}
            </p>
        </div>

        <flux:button
            wire:click="openCreate"
            variant="primary"
            icon="plus-circle"
            class="bg-[#2E8B57] text-white"
        >
            {{ __('Añadir Modelo') }}
        </flux:button>
    </div>

    {{-- Search card --}}
    <div class="bg-white rounded-xl shadow-sm border border-[#A5D6A7] p-5 mb-6">
        <label class="block text-sm font-medium text-[#333333] mb-2">
            {{ __('Buscar por nombre, código o marca') }}
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

    {{-- Table card --}}
    <div class="bg-white rounded-xl shadow-sm border border-[#A5D6A7] overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="bg-[#2E8B57] text-white text-xs font-bold uppercase tracking-wider">
                        <th class="px-6 py-4 text-left">{{ __('Nombre') }}</th>
                        <th class="px-6 py-4 text-left">{{ __('Código') }}</th>
                        <th class="px-6 py-4 text-left">{{ __('Marca') }}</th>
                        <th class="px-6 py-4 text-left">{{ __('Descripción') }}</th>
                        <th class="px-6 py-4 text-right">{{ __('Acciones') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($this->models as $i => $model)
                        <tr wire:key="model-{{ $model->id }}"
                            class="{{ $i % 2 === 0 ? 'bg-white' : 'bg-[#A5D6A7]/20' }} border-b border-[#A5D6A7] hover:bg-[#A5D6A7]/30 transition">
                            <td class="px-6 py-4 text-sm font-bold text-[#333333] uppercase">
                                {{ $model->name }}
                            </td>
                            <td class="px-6 py-4 text-sm text-[#666666] font-mono">
                                {{ $model->code }}
                            </td>
                            <td class="px-6 py-4">
                                <span class="inline-flex px-3 py-1 bg-[#2E8B57]/10 text-[#2E8B57] text-xs font-semibold rounded-full">
                                    {{ $model->brand->name }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-[#333333]">
                                {{ $model->description ?: __('Sin descripción') }}
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex justify-end gap-2">
                                    <button wire:click="openEdit({{ $model->id }})"
                                            class="inline-flex h-9 w-9 items-center justify-center rounded-md bg-[#F4C542] text-[#333333] hover:bg-[#D8AC34]"
                                            title="{{ __('Editar') }}"
                                            aria-label="{{ __('Editar') }}">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16.862 3.487a2.25 2.25 0 013.182 3.182l-9.193 9.193a4.5 4.5 0 01-1.897 1.13l-3.17 1.056 1.056-3.17a4.5 4.5 0 011.13-1.897l9.193-9.193z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.5 7.5L16.5 4.5" />
                                        </svg>
                                    </button>
                                    <button wire:click="delete({{ $model->id }})"
                                            wire:confirm="{{ __('¿Eliminar este modelo?') }}"
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
                                {{ __('No hay modelos registrados.') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="px-6 py-4 border-t border-[#A5D6A7]">
            {{ $this->models->links() }}
        </div>
    </div>

    {{-- Modal crear/editar --}}
    <flux:modal name="model-form" wire:close="closeModal" class="md:w-[520px]">
        <form wire:submit="save" class="space-y-6">
            <div>
                <flux:heading size="lg">
                    {{ $editingId ? __('Editar modelo') : __('Nuevo modelo') }}
                </flux:heading>
                <flux:text class="mt-2">
                    {{ __('Ingresa el nombre, marca y descripción opcional.') }}
                </flux:text>
            </div>

            <flux:input
                wire:model="name"
                :label="__('Nombre del Modelo')"
                placeholder="{{ __('Ej. Corolla, F-150') }}"
            />

            <flux:select
                wire:model="brand_id"
                name="brand_id"
                :label="__('Marca')"
            >
                <x-slot:label>
                    {{ __('Marca') }}
                </x-slot:label>
                @if($this->brands->count() > 1)
                    <option value="" disabled>{{ __('Seleccione una marca') }}</option>
                @endif
                @foreach ($this->brands as $brand)
                    <option value="{{ $brand->id }}" @if($this->brands->count() === 1) selected @endif>{{ $brand->name }}</option>
                @endforeach
            </flux:select>

            <flux:textarea
                wire:model="description"
                :label="__('Descripción')"
                placeholder="{{ __('Agregue una descripción del modelo...') }}"
            />

            <div class="flex gap-3 justify-end pt-4 border-t border-[#E0E0E0]">
                <flux:button
                    wire:click="closeModal"
                    type="button"
                >
                    {{ __('Cancelar') }}
                </flux:button>
                <flux:button
                    type="submit"
                    variant="primary"
                    class="bg-[#2E8B57] text-white"
                >
                    {{ __('Guardar') }}
                </flux:button>
            </div>
        </form>
    </flux:modal>
</div>
