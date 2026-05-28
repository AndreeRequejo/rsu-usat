<?php

use App\Models\Brand;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Computed;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Flux\Flux;

new class extends Component {
    use WithFileUploads, WithPagination;

    public string $search = '';
    public bool $showModal = false;
    public ?int $editingId = null;

    public string $name = '';
    public string $description = '';
    public $logo = null;
    public ?string $currentLogo = null;

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string'],
            'logo' => ['nullable', 'image', 'max:2048'],
        ];
    }

    public function save(): void
    {
        $validated = $this->validate();

        if ($this->editingId) {
            $brand = Brand::findOrFail($this->editingId);
            $logoPath = $brand->logo;

            if ($this->logo) {
                if ($brand->logo) {
                    Storage::disk('public')->delete($brand->logo);
                }
                $logoPath = $this->logo->store('brands', 'public');
            }

            $brand->update([
                'name' => $validated['name'],
                'description' => $validated['description'],
                'logo' => $logoPath,
            ]);

            Flux::toast(variant: 'success', text: __('Marca actualizada.'));
        } else {
            $logoPath = $this->logo ? $this->logo->store('brands', 'public') : null;

            Brand::create([
                'name' => $validated['name'],
                'description' => $validated['description'],
                'logo' => $logoPath,
            ]);

            Flux::toast(variant: 'success', text: __('Marca creada.'));
        }

        $this->resetForm();
        $this->showModal = false;
        Flux::modal('brand-form')->close();
    }

    public function openCreate(): void
    {
        $this->resetForm();
        $this->showModal = true;
        Flux::modal('brand-form')->show();
    }

    public function openEdit(int $id): void
    {
        $brand = Brand::findOrFail($id);
        $this->editingId = $brand->id;
        $this->name = $brand->name;
        $this->description = $brand->description ?? '';
        $this->currentLogo = $brand->logo;
        $this->logo = null;
        $this->showModal = true;
        Flux::modal('brand-form')->show();
    }

    public function closeModal(): void
    {
        $this->resetForm();
        $this->showModal = false;
        Flux::modal('brand-form')->close();
    }

    public function delete(int $id): void
    {
        $brand = Brand::findOrFail($id);
        if ($brand->logo) {
            Storage::disk('public')->delete($brand->logo);
        }
        $brand->delete();
        Flux::toast(variant: 'success', text: __('Marca eliminada.'));

        if ($this->editingId === $id) {
            $this->resetForm();
        }
    }

    #[Computed]
    public function brands()
    {
        return Brand::query()
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
        $this->reset(['name', 'description', 'logo', 'editingId', 'currentLogo']);
        $this->resetErrorBag();
        $this->resetValidation();
    }
}; ?>

<div class="min-h-screen bg-white p-6 text-[#333333]">
    {{-- Header --}}
    <div class="flex items-start justify-between mb-6">
        <div>
            <h1 class="text-3xl font-bold text-[#2E8B57]">
                {{ __('Gestión de Marcas') }}
            </h1>
            <p class="text-sm text-[#333333] mt-1">
                {{ __('Administración de marcas registradas para asignar a vehículos.') }}
            </p>
        </div>

        <flux:button
            wire:click="openCreate"
            variant="primary"
            icon="plus-circle"
            class="bg-[#2E8B57] text-white"
        >
            {{ __('Añadir Marca') }}
        </flux:button>
    </div>

    {{-- Search card --}}
    <div class="bg-white rounded-xl shadow-sm border border-[#A5D6A7] p-5 mb-6">
        <label class="block text-sm font-medium text-[#333333] mb-2">
            {{ __('Buscar por nombre o descripción') }}
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
                        <th class="px-6 py-4 text-left">{{ __('Logo') }}</th>
                        <th class="px-6 py-4 text-left">{{ __('Nombre') }}</th>
                        <th class="px-6 py-4 text-left">{{ __('Descripción') }}</th>
                        <th class="px-6 py-4 text-right">{{ __('Acciones') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($this->brands as $i => $brand)
                        <tr wire:key="brand-{{ $brand->id }}"
                            class="{{ $i % 2 === 0 ? 'bg-white' : 'bg-[#A5D6A7]/20' }} border-b border-[#A5D6A7] hover:bg-[#A5D6A7]/30 transition">
                            <td class="px-6 py-4">
                                @if ($brand->logo)
                                    <img src="{{ Storage::url($brand->logo) }}"
                                         alt="{{ $brand->name }}"
                                         class="h-11 w-11 rounded-xl object-cover ring-1 ring-[#A5D6A7]"/>
                                @else
                                    <div class="h-11 w-11 rounded-xl bg-[#A5D6A7]/30 flex items-center justify-center text-[10px] text-[#333333]">
                                        {{ __('Sin logo') }}
                                    </div>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-sm font-bold text-[#333333] uppercase">
                                {{ $brand->name }}
                            </td>
                            <td class="px-6 py-4 text-sm text-[#333333]">
                                {{ $brand->description ?: __('Sin descripción') }}
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex justify-end gap-2">
                                    <button wire:click="openEdit({{ $brand->id }})"
                                            class="inline-flex h-9 w-9 items-center justify-center rounded-md bg-[#F4C542] text-[#333333] hover:bg-[#D8AC34]"
                                            title="{{ __('Editar') }}"
                                            aria-label="{{ __('Editar') }}">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16.862 3.487a2.25 2.25 0 013.182 3.182l-9.193 9.193a4.5 4.5 0 01-1.897 1.13l-3.17 1.056 1.056-3.17a4.5 4.5 0 011.13-1.897l9.193-9.193z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.5 7.5L16.5 4.5" />
                                        </svg>
                                    </button>
                                    <button wire:click="delete({{ $brand->id }})"
                                            wire:confirm="{{ __('¿Eliminar esta marca?') }}"
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
                            <td colspan="4" class="px-6 py-10 text-center text-sm text-[#333333]">
                                {{ __('No hay marcas registradas.') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="px-6 py-4 border-t border-[#A5D6A7]">
            {{ $this->brands->links() }}
        </div>
    </div>

    {{-- Modal crear/editar --}}
    <flux:modal name="brand-form" wire:close="closeModal" class="md:w-[520px]">
        <form wire:submit="save" class="space-y-6">
            <div>
                <flux:heading size="lg">
                    {{ $editingId ? __('Editar marca') : __('Nueva marca') }}
                </flux:heading>
                <flux:text class="mt-2">
                    {{ __('Ingresa el nombre, descripción y una imagen opcional.') }}
                </flux:text>
            </div>

            <flux:input
                wire:model="name"
                :label="__('Nombre')"
                placeholder="{{ __('Nombre de la marca') }}"
                required
            />

            <flux:textarea
                wire:model="description"
                :label="__('Descripción')"
                rows="3"
                placeholder="{{ __('Descripción') }}"
            />

            <div>
                <flux:label>{{ __('Imagen') }}</flux:label>
                <div class="mt-2 flex items-center gap-4">
                          <input type="file" wire:model="logo" accept="image/*"
                              class="text-sm file:mr-3 file:py-2 file:px-4 file:rounded-md file:border-0 file:bg-[#2E8B57] file:text-white hover:file:bg-[#257046]"/>

                    @if ($logo)
                        <img src="{{ $logo->temporaryUrl() }}" alt="{{ __('Vista previa') }}"
                             class="h-20 w-20 rounded-lg object-cover ring-1 ring-[#A5D6A7]"/>
                    @elseif ($currentLogo)
                        <img src="{{ Storage::url($currentLogo) }}" alt="{{ __('Logo actual') }}"
                             class="h-20 w-20 rounded-lg object-cover ring-1 ring-[#A5D6A7]"/>
                    @else
                        <div class="h-20 w-20 rounded-lg bg-[#A5D6A7]/30 flex items-center justify-center text-[10px] text-[#333333]">
                            {{ __('Sin imagen') }}
                        </div>
                    @endif
                </div>
                @error('logo')
                    <flux:text class="mt-2" variant="danger">{{ $message }}</flux:text>
                @enderror
            </div>

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
