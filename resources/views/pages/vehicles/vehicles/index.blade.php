<?php

use App\Models\Brand;
use App\Models\BrandModel;
use App\Models\Vehicle;
use App\Models\VehicleColor;
use App\Models\VehicleImage;
use App\Models\VehicleType;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Flux\Flux;

new class extends Component {
    use WithPagination;
    use WithFileUploads;

    public string $search = '';
    public bool $showModal = false;
    public ?int $editingId = null;
    public ?int $deletingId = null;

    public string $name = '';
    public string $code = '';
    public string $plate = '';
    public ?int $year = null;
    public ?int $occupant_capacity = null;
    public ?string $load_capacity = null;
    public string $description = '';
    public bool $status = true;
    public ?int $brand_id = null;
    public ?int $model_id = null;
    public ?int $type_id = null;
    public ?int $color_id = null;
    public ?string $selectedColorCode = null;
    public array $photos = [];
    public ?int $profileImageId = null;
    public ?int $profileUploadIndex = null;
    public ?int $deletingImageId = null;

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150', Rule::unique('vehicles', 'name')->ignore($this->editingId)],
            'code' => ['required', 'string', 'max:50', Rule::unique('vehicles', 'code')->ignore($this->editingId)],
            'plate' => ['required', 'string', 'max:20', Rule::unique('vehicles', 'plate')->ignore($this->editingId)],
            'year' => ['required', 'integer', 'min:1900', 'max:' . (date('Y') + 1)],
            'occupant_capacity' => ['required', 'integer', 'min:1'],
            'load_capacity' => ['required', 'numeric', 'min:0'],
            'description' => ['nullable', 'string'],
            'status' => ['boolean'],
            'brand_id' => ['required', 'exists:brands,id'],
            'model_id' => ['required', 'exists:brandmodels,id'],
            'type_id' => ['required', 'exists:vehicletypes,id'],
            'color_id' => ['required', 'exists:vehiclecolors,id'],
            'photos' => ['nullable', 'array'],
            'photos.*' => ['image', 'max:2048'],
        ];
    }

    protected function messages(): array
    {
        return [
            // Nombre
            'name.required' => __('El nombre del vehículo es obligatorio.'),
            'name.max' => __('El nombre no puede tener más de 150 caracteres.'),
            // Código interno
            'code.required' => __('El código interno es obligatorio.'),
            'code.unique' => __('Ya existe un vehículo con ese código interno.'),
            'code.max' => __('El código no puede tener más de 50 caracteres.'),
            // Placa
            'plate.required' => __('La placa es obligatoria.'),
            'plate.unique' => __('Ya existe un vehículo registrado con esa placa.'),
            // Año
            'year.required' => __('El año es obligatorio.'),
            'year.integer' => __('El año debe ser un número válido.'),
            'year.min' => __('El año no puede ser menor a 1900.'),
            'year.max' => __('El año no puede ser mayor al siguiente año calendario.'),
            // Capacidad de ocupantes
            'occupant_capacity.required' => __('La capacidad de ocupantes es obligatoria.'),
            'occupant_capacity.integer' => __('La capacidad de ocupantes debe ser un número entero.'),
            'occupant_capacity.min' => __('La capacidad de ocupantes debe ser mayor a 0.'),
            // Capacidad de carga
            'load_capacity.required' => __('La capacidad de carga es obligatoria.'),
            'load_capacity.numeric' => __('La capacidad de carga debe ser numérica.'),
            'load_capacity.min' => __('La capacidad de carga no puede ser negativa.'),
            // Descripción
            'description.nullable' => __('La descripción debe ser un valor válido.'),
            // Marca
            'brand_id.required' => __('Debe seleccionar una marca.'),
            'brand_id.exists' => __('La marca seleccionada no existe.'),
            // Modelo
            'model_id.required' => __('Debe seleccionar un modelo.'),
            'model_id.exists' => __('El modelo seleccionado no existe.'),
            // Tipo de vehículo
            'type_id.required' => __('Debe seleccionar un tipo de vehículo.'),
            'type_id.exists' => __('El tipo de vehículo seleccionado no existe.'),
            // Color
            'color_id.required' => __('Debe seleccionar un color.'),
            'color_id.exists' => __('El color seleccionado no existe.'),
        ];
    }

    public function save(): void
    {
        $validated = $this->validate();

        if (!empty($validated['model_id'])) {
            $selectedModel = BrandModel::find($validated['model_id']);
            if (!$selectedModel || (int) $selectedModel->brand_id !== (int) $validated['brand_id']) {
                $this->addError('model_id', __('El modelo seleccionado no pertenece a la marca elegida.'));
                return;
            }
        }

        $payload = [
            'name' => $validated['name'],
            'code' => $validated['code'] ?: null,
            'plate' => strtoupper($validated['plate']),
            'year' => $validated['year'] ?? null,
            'occupant_capacity' => $validated['occupant_capacity'] ?? null,
            'load_capacity' => $validated['load_capacity'] ?? null,
            'description' => $validated['description'] ?: null,
            'status' => (bool) $validated['status'],
            'brand_id' => $validated['brand_id'] ?? null,
            'model_id' => $validated['model_id'] ?? null,
            'type_id' => $validated['type_id'] ?? null,
            'color_id' => $validated['color_id'] ?? null,
        ];

        DB::transaction(function () use ($payload) {
            if ($this->editingId) {
                $vehicle = Vehicle::findOrFail($this->editingId);
                $vehicle->update($payload);
                $this->saveImages($vehicle);
                $this->applyProfileImage($vehicle);
                Flux::toast(variant: 'success', text: __('Vehiculo actualizado correctamente.'));
                return;
            }

            $vehicle = Vehicle::create($payload);
            $this->saveImages($vehicle);
            $this->applyProfileImage($vehicle);
            Flux::toast(variant: 'success', text: __('Vehiculo registrado correctamente.'));
        });

        $this->resetForm();
        $this->showModal = false;
        Flux::modal('vehicle-form')->close();
    }

    public function openCreate(): void
    {
        $this->resetForm();
        $this->selectedColorCode = null;
        $this->showModal = true;
        Flux::modal('vehicle-form')->show();
    }

    public function openEdit(int $id): void
    {
        $vehicle = Vehicle::findOrFail($id);

        $this->editingId = $vehicle->id;
        $this->name = $vehicle->name;
        $this->code = $vehicle->code ?? '';
        $this->plate = $vehicle->plate;
        $this->year = $vehicle->year;
        $this->occupant_capacity = $vehicle->occupant_capacity;
        $this->load_capacity = $vehicle->load_capacity !== null ? (string) $vehicle->load_capacity : null;
        $this->description = $vehicle->description ?? '';
        $this->status = (bool) $vehicle->status;
        $this->brand_id = $vehicle->brand_id;
        $this->model_id = $vehicle->model_id;
        $this->type_id = $vehicle->type_id;
        $this->color_id = $vehicle->color_id;
        $this->selectedColorCode = $vehicle->color?->code;
        $this->profileImageId = $vehicle->vehicleImages()->where('profile', true)->value('id');

        $this->showModal = true;
        Flux::modal('vehicle-form')->show();
    }

    public function closeModal(): void
    {
        $this->resetForm();
        $this->showModal = false;
        Flux::modal('vehicle-form')->close();
    }

    public function confirmDelete(int $id): void
    {
        $this->deletingId = $id;
        Flux::modal('confirm-delete')->show();
    }

    public function delete(): void
    {
        if (!$this->deletingId) {
            return;
        }

        $vehicle = Vehicle::findOrFail($this->deletingId);

        $relatedCount =
            DB::table('vehicleoccupants')->where('vehicle_id', $vehicle->id)->count() +
            DB::table('vehicleroutes')->where('vehicle_id', $vehicle->id)->count() +
            // DB::table('vehicleimages')->where('vehicle_id', $vehicle->id)->count() +
            $vehicle->schedulings()->count() +
            $vehicle->maintenanceSchedules()->count();

        if ($relatedCount > 0) {
            Flux::toast(variant: 'warning', text: __('No se puede eliminar el vehiculo porque tiene registros relacionados.'));
            $this->deletingId = null;
            Flux::modal('confirm-delete')->close();
            return;
        }

        $vehicle->delete();

        Flux::toast(variant: 'success', text: __('Vehiculo eliminado correctamente.'));

        if ($this->editingId === $this->deletingId) {
            $this->resetForm();
        }
        $this->deletingId = null;
        Flux::modal('confirm-delete')->close();
    }
    public function deleteImage(int $imageId): void
    {
        $image = VehicleImage::findOrFail($imageId);

        Storage::disk('public')->delete($image->image);

        $wasProfile = $image->profile;
        $vehicleId = $image->vehicle_id;

        $image->delete();

        if ($wasProfile) {
            $newProfile = VehicleImage::where('vehicle_id', $vehicleId)->first();

            if ($newProfile) {
                $newProfile->update(['profile' => true]);
                $this->profileImageId = $newProfile->id;
            } else {
                $this->profileImageId = null;
            }
        }

        Flux::toast(variant: 'success', text: __('Imagen eliminada correctamente.'));
    }
    #[Computed]
    public function vehicles()
    {
        return Vehicle::query()
            ->with(['brand', 'model', 'type', 'color', 'profileImage'])
            ->when($this->search !== '', function ($query) {
                $query->where(function ($q) {
                    $q->where('name', 'like', '%' . $this->search . '%')
                        ->orWhere('plate', 'like', '%' . $this->search . '%')
                        ->orWhere('code', 'like', '%' . $this->search . '%')
                        ->orWhereHas('brand', fn($brandQuery) => $brandQuery->where('name', 'like', '%' . $this->search . '%'))
                        ->orWhereHas('model', fn($modelQuery) => $modelQuery->where('name', 'like', '%' . $this->search . '%'))
                        ->orWhereHas('type', fn($typeQuery) => $typeQuery->where('name', 'like', '%' . $this->search . '%'));
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

    #[Computed]
    public function modelsForBrand()
    {
        if (!$this->brand_id) {
            return collect();
        }

        return BrandModel::query()->where('brand_id', $this->brand_id)->orderBy('name')->get();
    }

    #[Computed]
    public function vehicleTypes()
    {
        return VehicleType::orderBy('name')->get();
    }

    #[Computed]
    public function vehicleColors()
    {
        return VehicleColor::orderBy('name')->get();
    }

    #[Computed]
    public function vehicleImages()
    {
        if (!$this->editingId) {
            return collect();
        }

        return VehicleImage::query()->where('vehicle_id', $this->editingId)->orderByDesc('profile')->get();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedBrandId($value): void
    {
        if (!$value) {
            $this->model_id = null;
            return;
        }

        if ($this->model_id) {
            $isValidModel = BrandModel::query()->where('id', $this->model_id)->where('brand_id', (int) $value)->exists();

            if (!$isValidModel) {
                $this->model_id = null;
            }
        }
    }

    public function updatedColorId($value): void
    {
        if (!$value) {
            $this->selectedColorCode = null;
            return;
        }

        $this->selectedColorCode = VehicleColor::query()->where('id', (int) $value)->value('code');
    }

    private function resetForm(): void
    {
        $this->reset(['name', 'code', 'plate', 'year', 'occupant_capacity', 'load_capacity', 'description', 'brand_id', 'model_id', 'type_id', 'color_id', 'selectedColorCode', 'editingId', 'photos', 'profileImageId', 'profileUploadIndex']);
        $this->status = true;
        $this->resetErrorBag();
        $this->resetValidation();
    }

    private function saveImages(Vehicle $vehicle): void
    {
        if (empty($this->photos)) {
            return;
        }

        foreach ($this->photos as $index => $photo) {
            $path = $photo->store('vehicleimages', 'public');
            $image = $vehicle->vehicleImages()->create([
                'image' => $path,
                'profile' => false,
            ]);

            if ($this->profileUploadIndex !== null && (int) $this->profileUploadIndex === $index) {
                $this->profileImageId = $image->id;
            }
        }
    }

    private function applyProfileImage(Vehicle $vehicle): void
    {
        $profileId = $this->profileImageId;

        if (!$profileId) {
            $profileId = $vehicle->vehicleImages()->value('id');
        }

        if (!$profileId) {
            return;
        }

        $vehicle->vehicleImages()->update(['profile' => false]);
        $vehicle
            ->vehicleImages()
            ->where('id', $profileId)
            ->update(['profile' => true]);
    }
}; ?>

<div class="min-h-screen bg-white p-6 text-[#333333]">
    <div class="flex items-start justify-between mb-6">
        <div>
            <h1 class="text-3xl font-bold text-[#2E8B57]">
                {{ __('Gestion de vehiculos') }}
            </h1>
            <p class="text-sm text-[#333333] mt-1">
                {{ __('Administracion de vehiculos del parque automotor.') }}
            </p>
        </div>

        <flux:button wire:click="openCreate" variant="primary" icon="plus-circle"
            class="bg-[#2E8B57]! text-white cursor-pointer hover:bg-[#257046]!">
            {{ __('Nuevo Vehiculo') }}
        </flux:button>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-[#A5D6A7] p-5 mb-6">
        <label class="block text-sm font-medium text-[#333333] mb-2">
            {{ __('Buscar por nombre, placa, codigo o catalogos relacionados') }}
        </label>
        <div class="flex gap-3">
            <div class="relative flex-1">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 h-5 w-5 text-[#333333]" fill="none"
                    stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="{{ __('Buscar...') }}"
                    class="w-full pl-10 pr-4 py-2.5 border border-[#A5D6A7] rounded-lg bg-white text-sm focus:outline-none focus:ring-2 focus:ring-[#2E8B57]" />
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-[#A5D6A7] overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="bg-[#2E8B57] text-white text-xs font-bold uppercase tracking-wider">
                        <th class="px-4 py-3 text-center">{{ __('Foto') }}</th>
                        <th class="px-4 py-3 text-left">{{ __('Vehiculo') }}</th>
                        <th class="px-4 py-3 text-left">{{ __('Placa') }}</th>
                        <th class="px-4 py-3 text-left">{{ __('Marca / Modelo') }}</th>
                        <th class="px-4 py-3 text-left">{{ __('Tipo') }}</th>
                        <th class="px-4 py-3 text-left">{{ __('Color') }}</th>
                        <th class="px-4 py-3 text-left">{{ __('Capacidades') }}</th>
                        <th class="px-4 py-3 text-center">{{ __('Estado') }}</th>
                        <th class="px-4 py-3 text-right">{{ __('Acciones') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($this->vehicles as $i => $vehicle)
                        <tr wire:key="vehicle-{{ $vehicle->id }}"
                            class="{{ $i % 2 === 0 ? 'bg-white' : 'bg-[#A5D6A7]/20' }} border-b border-[#A5D6A7] hover:bg-[#A5D6A7]/30 transition">
                            <td class="px-4 py-3">
                                @if ($vehicle->profileImage)
                                    <img src="{{ Storage::url($vehicle->profileImage->image) }}"
                                        alt="{{ $vehicle->name }}"
                                        class="h-11 w-11 rounded-xl object-cover ring-1 ring-[#A5D6A7]">
                                @else
                                    <div
                                        class="h-11 w-11 rounded-xl bg-[#A5D6A7]/30 flex items-center justify-center text-[10px] text-[#333333]">
                                        {{ __('Sin logo') }}
                                    </div>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <div class="text-sm font-semibold text-[#333333] uppercase">{{ $vehicle->name }}</div>
                                <div class="text-xs text-[#666666]">
                                    {{ $vehicle->code ?: __('Sin codigo') }}
                                    @if ($vehicle->year)
                                        · Año: {{ $vehicle->year }}
                                    @endif
                                </div>
                            </td>
                            <td class="px-4 py-3 text-sm font-semibold text-[#333333] uppercase">
                                {{ $vehicle->plate }}
                            </td>
                            <td class="px-4 py-3 text-sm text-[#333333]">
                                <div>{{ $vehicle->brand?->name ?: __('Sin marca') }}</div>
                                <div class="text-xs text-[#666666]">{{ $vehicle->model?->name ?: __('Sin modelo') }}
                                </div>
                            </td>
                            <td class="px-4 py-3 text-sm text-[#333333]">{{ $vehicle->type?->name ?: __('Sin tipo') }}
                            </td>
                            <td class="px-4 py-3 text-sm text-[#333333]">
                                @if ($vehicle->color)
                                    <div class="inline-flex items-center gap-2">
                                        <span class="h-4 w-4 rounded-full ring-1 ring-black/10"
                                            style="background-color: {{ $vehicle->color->code }};"></span>
                                        <span>{{ $vehicle->color->name }}</span>
                                    </div>
                                @else
                                    {{ __('Sin color') }}
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm text-[#333333]">
                                <div>{{ __('Ocupantes') }}: {{ $vehicle->occupant_capacity ?? '-' }}</div>
                                <div>{{ __('Carga') }}:
                                    {{ $vehicle->load_capacity !== null ? $vehicle->load_capacity . ' Tn' : '-' }}
                                </div>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span
                                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $vehicle->status ? 'bg-green-100 text-green-800' : 'bg-gray-200 text-gray-700' }}">
                                    {{ $vehicle->status ? __('Activo') : __('Inactivo') }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex justify-end gap-2">
                                    <button wire:click="openEdit({{ $vehicle->id }})"
                                        class="inline-flex h-8 w-8 items-center justify-center rounded-md text-[#F4C542] hover:bg-[#F4C542]/20 transition"
                                        title="{{ __('Editar') }}" aria-label="{{ __('Editar') }}">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M16.862 3.487a2.25 2.25 0 013.182 3.182l-9.193 9.193a4.5 4.5 0 01-1.897 1.13l-3.17 1.056 1.056-3.17a4.5 4.5 0 011.13-1.897l9.193-9.193z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M19.5 7.5L16.5 4.5" />
                                        </svg>
                                    </button>
                                    <button wire:click="confirmDelete({{ $vehicle->id }})"
                                        class="inline-flex h-8 w-8 items-center justify-center rounded-md text-[#E53935] hover:bg-[#E53935]/20 transition"
                                        title="{{ __('Eliminar') }}" aria-label="{{ __('Eliminar') }}">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M6 7h12M9 7V5a1 1 0 011-1h4a1 1 0 011 1v2M7 7l1 12a2 2 0 002 2h4a2 2 0 002-2l1-12" />
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-4 py-10 text-center text-sm text-[#333333]">
                                {{ __('No hay vehiculos registrados.') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="px-4 py-3 border-t border-[#A5D6A7]">
            {{ $this->vehicles->links() }}
        </div>
    </div>

    <flux:modal name="vehicle-form" wire:close="closeModal" class="md:w-[760px] max-h-[90vh] overflow-y-auto">
        <form wire:submit="save" class="space-y-5" novalidate>
            <div>
                <flux:heading size="lg">
                    {{ $editingId ? __('Editar vehiculo') : __('Nuevo vehiculo') }}
                </flux:heading>
                <flux:text class="mt-2">
                    {{ __('Complete la informacion principal y seleccione sus catalogos relacionados.') }}
                </flux:text>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <flux:input wire:model="name" :label="__('Nombre del vehiculo')"
                    placeholder="{{ __('Ej. Compactador N-01') }}" required />

                <flux:input wire:model="plate" :label="__('Placa')" placeholder="{{ __('Ej. ABC-123') }}" required />
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <flux:input wire:model="code" :label="__('Codigo interno')" placeholder="{{ __('Ej. VEH-001') }}"
                    required />

                <flux:input wire:model.number="year" type="number" :label="__('Año')" min="1900"
                    max="{{ date('Y') + 1 }}" placeholder="{{ __('Ej. 2024') }}" required />
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <flux:select wire:model.live="brand_id" :label="__('Marca')">
                    <option value="">{{ __('Seleccionar...') }}</option>
                    @foreach ($this->brands as $brand)
                        <option value="{{ $brand->id }}">{{ $brand->name }}</option>
                    @endforeach
                </flux:select>

                <flux:select wire:model="model_id" :label="__('Modelo')" :disabled="!$brand_id">
                    <option value="">{{ $brand_id ? __('Seleccionar...') : __('Primero seleccione marca') }}
                    </option>
                    @foreach ($this->modelsForBrand as $model)
                        <option value="{{ $model->id }}">{{ $model->name }}</option>
                    @endforeach
                </flux:select>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <flux:select wire:model="type_id" :label="__('Tipo de vehiculo')">
                    <option value="">{{ __('Seleccionar...') }}</option>
                    @foreach ($this->vehicleTypes as $type)
                        <option value="{{ $type->id }}">{{ $type->name }}</option>
                    @endforeach
                </flux:select>

                <flux:select wire:model.live="color_id" :label="__('Color')">
                    <option value="">{{ __('Seleccionar...') }}</option>
                    @foreach ($this->vehicleColors as $color)
                        <option value="{{ $color->id }}">{{ $color->name }} ({{ $color->code }})</option>
                    @endforeach
                </flux:select>
            </div>

            <div class="rounded-lg border border-[#A5D6A7] p-3" x-data="{ code: @entangle('selectedColorCode').live }">
                <div class="text-sm font-medium text-[#333333] mb-2">{{ __('Color seleccionado') }}</div>
                <div class="h-16 rounded-lg flex items-center justify-center text-white font-bold shadow-sm"
                    :style="`background-color: ${code || '#E0E0E0'}`">
                    <span x-text="code || '{{ __('Sin color') }}'"></span>
                </div>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <flux:input wire:model.number="occupant_capacity" type="number" min="1"
                    :label="__('Capacidad de ocupantes')" placeholder="{{ __('Ej. 3') }}" required />

                <flux:input wire:model="load_capacity" type="number" step="0.01" min="0"
                    :label="__('Capacidad de carga (Tn)')" placeholder="{{ __('Ej. 4.50') }}" required />
            </div>

            <flux:textarea wire:model="description" :label="__('Descripcion')"
                placeholder="{{ __('Descripcion opcional del vehiculo') }}" rows="3" />

            <div class="space-y-3">
                <flux:label>{{ __('Fotos del vehiculo') }}</flux:label>
                <input type="file" wire:model="photos" multiple accept="image/*"
                    class="text-sm file:mr-3 file:py-2 file:px-4 file:rounded-md file:border-0 file:bg-[#2E8B57] file:text-white hover:file:bg-[#257046]" />

                @error('photos.*')
                    <flux:text class="mt-2" variant="danger">{{ $message }}</flux:text>
                @enderror

                @if (!empty($photos))
                    <div class="flex gap-3 overflow-x-auto pb-2">
                        @foreach ($photos as $index => $photo)
                            <label class="relative flex-shrink-0 cursor-pointer">
                                <input type="radio" wire:model="profileUploadIndex" value="{{ $index }}"
                                    class="hidden peer">

                                <img src="{{ $photo->temporaryUrl() }}"
                                    class="h-20 w-20 rounded-lg object-cover border-2 border-transparent peer-checked:border-[#2E8B57] ">

                                <div
                                    class=" absolute inset-0 hidden peer-checked:flex items-center justify-center bg-black/30 rounded-lg ">
                                    <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3"
                                            d="M5 13l4 4L19 7" />
                                    </svg>
                                </div>
                            </label>
                        @endforeach
                    </div>
                @endif

                @if ($this->vehicleImages->isNotEmpty())
                    <div>
                        <div class="text-sm font-medium text-[#333333] mb-2">
                            {{ __('Fotos registradas') }}
                        </div>

                        <div class="flex gap-3 pb-2">
                            @foreach ($this->vehicleImages as $image)
                                <label class=" cursor-pointer">
                                    <input type="radio" wire:model.live="profileImageId"
                                        value="{{ $image->id }}" class="hidden peer">
                                    <button type="button" wire:click="deleteImage({{ $image->id }})"
                                        wire:confirm="{{ __('¿Eliminar esta imagen?') }}"
                                        class="absolute -top-2 -right-2 h-6 w-6 rounded-full bg-red-500 text-white flex items-center justify-center">
                                        ×
                                    </button>
                                    <img src="{{ Storage::url($image->image) }}" alt="{{ __('Foto vehiculo') }}"
                                        class="h-20 w-20 rounded-lg object-cover border-2 border-transparent peer-checked:border-[#2E8B57] transition">

                                    @if ((int) $profileImageId === (int) $image->id)
                                        <span
                                            class=" top-1 right-1 bg-[#2E8B57] text-white text-[10px] px-2 py-1 rounded">
                                            Perfil
                                        </span>
                                    @endif
                                </label>
                            @endforeach
                        </div>
                        <p class="text-xs text-gray-500">
                            {{ __('Seleccione una imagen para usarla como foto de perfil.') }}
                        </p>
                    </div>
                @endif
            </div>

            <div class="flex items-center gap-3">
                <label class="flex items-center cursor-pointer">
                    <input type="checkbox" wire:model="status" class="sr-only peer">
                    <div
                        class="relative w-11 h-6 bg-[#CCCCCC] peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-[#2E8B57] rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:inset-s-0.5 after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-[#2E8B57]">
                    </div>
                    <span class="ms-3 text-sm font-medium text-[#333333]">{{ __('Activo') }}</span>
                </label>
            </div>

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

    <flux:modal name="confirm-delete" class="md:w-[400px]">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg" class="text-red-500">
                    {{ __('Confirmar eliminacion') }}
                </flux:heading>
                <flux:text class="mt-2 text-sm text-[#333333]">
                    {{ __('¿Estas seguro de que deseas eliminar este vehiculo? Esta accion no se puede deshacer.') }}
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
