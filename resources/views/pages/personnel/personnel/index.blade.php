<?php

use App\Models\Employee;
use App\Models\EmployeeType;
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
    public ?int $deletingId = null;

    public string $dni = '';
    public int $employee_type_id = 0;
    public string $first_name = '';
    public string $last_name = '';
    public string $birthdate = '';
    public string $email = '';
    public string $password = '';
    public string $address = '';
    public string $phone = '';
    public $photo = null;
    public bool $active = true;

    protected function rules(): array
    {
        return [
            'dni' => [
                'required',
                'regex:/^\d{8}$/',
                Rule::unique('employees', 'dni')->ignore($this->editingId),
            ],
            'employee_type_id' => 'required|exists:employee_types,id',
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'birthdate' => 'required|date|before:today',
            'email' => [
                'required',
                'email',
                Rule::unique('users', 'email')->ignore($this->editingId ? Employee::find($this->editingId)?->user_id : null),
            ],
            'password' => $this->editingId ? 'nullable|string|min:6' : 'required|string|min:6',
            'address' => 'nullable|string|max:255',
            'phone' => 'nullable|regex:/^\d{9}$/',
            'photo' => 'nullable|image|max:2048',
            'active' => 'boolean',
        ];
    }

    protected function messages(): array
    {
        return [
            'dni.required' => 'El DNI es obligatorio.',
            'dni.regex' => 'El DNI debe contener exactamente 8 digitos numericos.',
            'dni.unique' => 'Ya existe un empleado con este DNI.',
            'employee_type_id.required' => 'El tipo de personal es obligatorio.',
            'employee_type_id.exists' => 'El tipo de personal seleccionado no existe.',
            'first_name.required' => 'Los nombres son obligatorios.',
            'last_name.required' => 'Los apellidos son obligatorios.',
            'birthdate.required' => 'La fecha de nacimiento es obligatoria.',
            'birthdate.before' => 'La fecha de nacimiento debe ser anterior a hoy.',
            'email.required' => 'El correo electronico es obligatorio.',
            'email.email' => 'El correo electronico debe tener un formato valido.',
            'email.unique' => 'Ya existe un usuario con este correo electronico.',
            'password.required' => 'La contrasena es obligatoria.',
            'password.min' => 'La contrasena debe tener al menos 6 caracteres.',
            'phone.regex' => 'El telefono debe contener exactamente 9 digitos numericos.',
            'photo.image' => 'La fotografia debe ser un archivo de imagen valido.',
            'photo.max' => 'La fotografia no debe exceder los 2MB.',
        ];
    }

    #[Computed]
    public function employees()
    {
        return Employee::query()
            ->with(['employeeType', 'user'])
            ->when($this->search !== '', function ($query) {
                $query->where(function ($q) {
                    $q->where('dni', 'like', '%' . $this->search . '%')
                        ->orWhere('first_name', 'like', '%' . $this->search . '%')
                        ->orWhere('last_name', 'like', '%' . $this->search . '%')
                        ->orWhereHas('user', fn($uq) => $uq->where('email', 'like', '%' . $this->search . '%'));
                });
            })
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->paginate(10);
    }

    #[Computed]
    public function employeeTypes()
    {
        return EmployeeType::orderBy('name')->get();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function openCreate(): void
    {
        $this->resetValidation();
        $this->reset(['dni', 'employee_type_id', 'first_name', 'last_name', 'birthdate', 'email', 'password', 'address', 'phone', 'photo', 'active', 'editingId']);
        $this->active = true;
        $this->showModal = true;
        Flux::modal('employee-form')->show();
    }

    public function openEdit(int $id): void
    {
        $employee = Employee::with('user')->findOrFail($id);
        $this->editingId = $id;
        $this->dni = $employee->dni;
        $this->employee_type_id = $employee->employee_type_id;
        $this->first_name = $employee->first_name;
        $this->last_name = $employee->last_name;
        $this->birthdate = $employee->birthdate?->format('Y-m-d') ?? '';
        $this->email = $employee->user->email;
        $this->address = $employee->address ?? '';
        $this->phone = $employee->phone ?? '';
        $this->active = $employee->active;
        $this->showModal = true;
        Flux::modal('employee-form')->show();
    }

    public function closeModal(): void
    {
        $this->resetValidation();
        $this->reset(['dni', 'employee_type_id', 'first_name', 'last_name', 'birthdate', 'email', 'password', 'address', 'phone', 'photo', 'active', 'editingId']);
        $this->showModal = false;
        Flux::modal('employee-form')->close();
    }

    public function save(): void
    {
        $this->validate();

        if ($this->editingId) {
            $employee = Employee::with('user')->findOrFail($this->editingId);
            $employee->update([
                'employee_type_id' => $this->employee_type_id,
                'dni' => $this->dni,
                'first_name' => $this->first_name,
                'last_name' => $this->last_name,
                'birthdate' => $this->birthdate,
                'phone' => $this->phone ?: null,
                'address' => $this->address ?: null,
                'active' => $this->active,
            ]);

            $employee->user->update([
                'name' => "{$this->first_name} {$this->last_name}",
                'email' => $this->email,
            ]);

            if ($this->password) {
                $employee->user->update(['password' => bcrypt($this->password)]);
            }

            if ($this->photo) {
                $extension = $this->photo->getClientOriginalExtension();
                $this->photo->storeAs('employees', "{$employee->id}.{$extension}", 'public');

                $employee->employeeImages()->where('profile', true)->update(['profile' => false]);
                $employee->employeeImages()->create([
                    'image' => "employees/{$employee->id}.{$extension}",
                    'profile' => true,
                ]);
            }

            Flux::toast(variant: 'success', text: 'Empleado actualizado correctamente.');
        } else {
            $user = \App\Models\User::create([
                'name' => "{$this->first_name} {$this->last_name}",
                'email' => $this->email,
                'password' => bcrypt($this->password),
            ]);

            $employee = Employee::create([
                'user_id' => $user->id,
                'employee_type_id' => $this->employee_type_id,
                'dni' => $this->dni,
                'first_name' => $this->first_name,
                'last_name' => $this->last_name,
                'birthdate' => $this->birthdate,
                'phone' => $this->phone ?: null,
                'address' => $this->address ?: null,
                'active' => $this->active,
            ]);

            if ($this->photo) {
                $extension = $this->photo->getClientOriginalExtension();
                $this->photo->storeAs('employees', "{$employee->id}.{$extension}", 'public');
                $employee->employeeImages()->create([
                    'image' => "employees/{$employee->id}.{$extension}",
                    'profile' => true,
                ]);
            }

            Flux::toast(variant: 'success', text: 'Empleado registrado correctamente.');
        }

        $this->closeModal();
    }

    public function confirmDelete(int $id): void
    {
        $this->deletingId = $id;
        Flux::modal('confirm-delete')->show();
    }

    public function delete(): void
    {
        if (!$this->deletingId) return;

        $employee = Employee::findOrFail($this->deletingId);

        if ($employee->contracts()->exists()) {
            Flux::toast(variant: 'warning', text: 'No se puede eliminar el empleado porque tiene contratos asociados.');
            $this->deletingId = null;
            Flux::modal('confirm-delete')->close();
            return;
        }

        $employee->user()->delete();
        $employee->delete();

        Flux::toast(variant: 'success', text: 'Empleado eliminado correctamente.');
        $this->deletingId = null;
        Flux::modal('confirm-delete')->close();
    }

    private function getProfileImageUrl(Employee $employee): ?string
    {
        $profileImage = $employee->employeeImages()->where('profile', true)->first();
        return $profileImage ? asset('storage/' . $profileImage->image) : null;
    }
}; ?>

<div class="min-h-screen bg-white p-6 text-[#333333]">
    <div class="flex items-start justify-between mb-6">
        <div>
            <h1 class="text-3xl font-bold text-[#2E8B57]">Gestion de personal</h1>
            <p class="text-sm text-[#333333] mt-1">Administracion de empleados de la organizacion.</p>
        </div>
        <flux:button wire:click="openCreate" variant="primary" icon="plus-circle" class="bg-[#2E8B57] text-white">
            Nuevo Empleado
        </flux:button>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-[#A5D6A7] p-5 mb-6">
        <div class="flex gap-3">
            <div class="relative flex-1">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 h-5 w-5 text-[#333333]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="Buscar por DNI, nombre, apellido o email..." class="w-full pl-10 pr-4 py-2.5 border border-[#A5D6A7] rounded-lg bg-white text-sm focus:outline-none focus:ring-2 focus:ring-[#2E8B57]" />
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-[#A5D6A7] overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="bg-[#2E8B57] text-white text-xs font-bold uppercase tracking-wider">
                        <th class="px-4 py-3 text-left">Foto</th>
                        <th class="px-4 py-3 text-left">DNI</th>
                        <th class="px-4 py-3 text-left">Nombre</th>
                        <th class="px-4 py-3 text-left">Tipo</th>
                        <th class="px-4 py-3 text-left">Email</th>
                        <th class="px-4 py-3 text-center">Estado</th>
                        <th class="px-4 py-3 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($this->employees as $i => $employee)
                        @php $profileUrl = $this->getProfileImageUrl($employee); @endphp
                        <tr wire:key="employee-{{ $employee->id }}" class="{{ $i % 2 === 0 ? 'bg-white' : 'bg-[#A5D6A7]/20' }} border-b border-[#A5D6A7] hover:bg-[#A5D6A7]/30 transition">
                            <td class="px-4 py-3">
                                <div class="flex items-center justify-center w-10 h-10 rounded-full bg-[#A5D6A7] overflow-hidden">
                                    @if ($profileUrl)
                                        <img src="{{ $profileUrl }}" alt="{{ $employee->first_name }}" class="w-full h-full object-cover">
                                    @else
                                        <span class="text-sm font-bold text-[#2E8B57]">{{ strtoupper(substr($employee->first_name, 0, 1)) }}</span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-4 py-3 text-sm font-mono font-medium text-[#333333]">{{ $employee->dni }}</td>
                            <td class="px-4 py-3 text-sm font-medium text-[#333333]">{{ $employee->last_name }} {{ $employee->first_name }}</td>
                            <td class="px-4 py-3 text-sm text-[#333333]">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-[#A5D6A7]/50 text-[#2E8B57]">{{ $employee->employeeType->name }}</span>
                            </td>
                            <td class="px-4 py-3 text-sm text-[#333333]">{{ $employee->user->email }}</td>
                            <td class="px-4 py-3 text-center">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $employee->active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                    {{ $employee->active ? 'Activo' : 'Inactivo' }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex justify-end gap-2">
                                    <button wire:click="openEdit({{ $employee->id }})" class="inline-flex h-8 w-8 items-center justify-center rounded-md bg-[#F4C542] text-[#333333] hover:bg-[#D8AC34] transition" title="Editar" aria-label="Editar">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16.862 3.487a2.25 2.25 0 013.182 3.182l-9.193 9.193a4.5 4.5 0 01-1.897 1.13l-3.17 1.056 1.056-3.17a4.5 4.5 0 011.13-1.897l9.193-9.193z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.5 7.5L16.5 4.5" />
                                        </svg>
                                    </button>
                                    <button wire:click="confirmDelete({{ $employee->id }})" class="inline-flex h-8 w-8 items-center justify-center rounded-md bg-[#E53935] text-white hover:bg-[#C62828] transition" title="Eliminar" aria-label="Eliminar">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 7h12M9 7V5a1 1 0 011-1h4a1 1 0 011 1v2M7 7l1 12a2 2 0 002 2h4a2 2 0 002-2l1-12" />
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-10 text-center text-sm text-[#333333]">No hay empleados registrados.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-4 py-3 border-t border-[#A5D6A7]">{{ $this->employees->links() }}</div>
    </div>

    <flux:modal name="employee-form" class="md:w-[600px] max-h-[90vh] overflow-y-auto">
        <form wire:submit="save" class="space-y-5" novalidate>
            <div class="flex items-center justify-between px-6 pt-4 pb-2">
                <div>
                    <flux:heading size="lg">{{ $editingId ? 'Editar Empleado' : 'Nuevo Empleado' }}</flux:heading>
                    <flux:text class="mt-1 text-sm text-[#666666]">Complete todos los campos obligatorios.</flux:text>
                </div>
                <button type="button" wire:click="closeModal" class="text-[#999999] hover:text-[#333333] transition">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <div class="px-6 space-y-4">
                <div class="flex gap-4">
                    <div class="flex-1">
                        <flux:input wire:model="dni" label="DNI" placeholder="8 digitos" maxlength="8" required />
                        @error('dni') <span class="text-xs text-[#E53935] mt-1">{{ $message }}</span> @enderror
                    </div>
                    <div class="flex-1">
                        <flux:select wire:model="employee_type_id" label="Tipo de Personal" required>
                            <option value="">Seleccionar...</option>
                            @foreach ($this->employeeTypes as $type)
                                <option value="{{ $type->id }}">{{ $type->name }}</option>
                            @endforeach
                        </flux:select>
                        @error('employee_type_id') <span class="text-xs text-[#E53935] mt-1">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div class="flex gap-4">
                    <div class="flex-1">
                        <flux:input wire:model="first_name" label="Nombres" placeholder="Nombres del empleado" required />
                        @error('first_name') <span class="text-xs text-[#E53935] mt-1">{{ $message }}</span> @enderror
                    </div>
                    <div class="flex-1">
                        <flux:input wire:model="last_name" label="Apellidos" placeholder="Apellidos del empleado" required />
                        @error('last_name') <span class="text-xs text-[#E53935] mt-1">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div class="flex gap-4">
                    <div class="flex-1">
                        <flux:input wire:model="birthdate" type="date" label="Fecha de Nacimiento" required />
                        @error('birthdate') <span class="text-xs text-[#E53935] mt-1">{{ $message }}</span> @enderror
                    </div>
                    <div class="flex-1">
                        <flux:input wire:model="phone" label="Telefono" placeholder="9 digitos (opcional)" maxlength="9" />
                        @error('phone') <span class="text-xs text-[#E53935] mt-1">{{ $message }}</span> @enderror
                    </div>
                </div>

                <flux:input wire:model="email" type="email" label="Correo Electronico" placeholder="email@ejemplo.com" required />
                @error('email') <span class="text-xs text-[#E53935] mt-1">{{ $message }}</span> @enderror

                <flux:input wire:model="password" type="password" label="{{ $editingId ? 'Nueva Contrasena (opcional)' : 'Contrasena' }}" placeholder="{{ $editingId ? 'Solo si desea cambiarla' : 'Minimo 6 caracteres' }}" />
                @error('password') <span class="text-xs text-[#E53935] mt-1">{{ $message }}</span> @enderror

                <flux:input wire:model="address" label="Direccion" placeholder="Direccion (opcional)" />

                <div class="flex items-center gap-3">
                    <label class="flex items-center cursor-pointer">
                        <input type="checkbox" wire:model="active" class="sr-only peer">
                        <div class="relative w-11 h-6 bg-[#CCCCCC] peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-[#2E8B57] rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-[#2E8B57]"></div>
                        <span class="ms-3 text-sm font-medium text-[#333333]">Activo</span>
                    </label>
                </div>

                <div>
                    <label class="block text-sm font-medium text-[#333333] mb-2">Fotografia de Perfil</label>
                    <div class="flex items-center gap-4">
                        <div class="relative">
                            @if ($photo)
                                <div class="w-20 h-20 rounded-full overflow-hidden border-2 border-[#A5D6A7]">
                                    <img src="{{ $photo->temporaryUrl() }}" alt="Preview" class="w-full h-full object-cover">
                                </div>
                            @elseif ($editingId)
                                @php $currentEmployee = \App\Models\Employee::with('employeeImages')->find($editingId); $currentProfile = $currentEmployee && $currentEmployee->employeeImages->where('profile', true)->first(); @endphp
                                @if ($currentProfile)
                                    <div class="w-20 h-20 rounded-full overflow-hidden border-2 border-[#A5D6A7]">
                                        <img src="{{ asset('storage/' . $currentProfile->image) }}" alt="Current" class="w-full h-full object-cover">
                                    </div>
                                @else
                                    <div class="w-20 h-20 rounded-full overflow-hidden border-2 border-[#A5D6A7] bg-[#A5D6A7] flex items-center justify-center">
                                        <span class="text-xl font-bold text-[#2E8B57]">{{ strtoupper(substr($first_name, 0, 1)) }}</span>
                                    </div>
                                @endif
                            @else
                                <div class="w-20 h-20 rounded-full overflow-hidden border-2 border-dashed border-[#A5D6A7] bg-[#F5F5F5] flex items-center justify-center">
                                    <svg class="h-8 w-8 text-[#999999]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                </div>
                            @endif
                        </div>
                        <div class="flex-1">
                            <input type="file" wire:model="photo" accept="image/jpeg,image/png" class="hidden" id="photo-input">
                            <label for="photo-input" class="inline-flex items-center px-4 py-2 bg-white border border-[#A5D6A7] rounded-lg text-sm font-medium text-[#333333] hover:bg-[#A5D6A7]/20 cursor-pointer transition">
                                <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                                </svg>
                                {{ $photo ? 'Cambiar' : 'Seleccionar' }}
                            </label>
                            @if ($photo)
                                <button type="button" wire:click="$set('photo', null)" class="ml-2 text-sm text-[#E53935] hover:underline">Quitar</button>
                            @endif
                            <p class="text-xs text-[#999999] mt-1">JPG, JPEG o PNG (max. 2MB)</p>
                        </div>
                    </div>
                    @error('photo') <span class="text-xs text-[#E53935] mt-1 block">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="px-6 py-4 bg-[#F5F5F5] border-t border-[#E0E0E0] flex justify-end gap-3">
                <flux:button type="button" variant="ghost" wire:click="closeModal" class="text-[#333333]">Cancelar</flux:button>
                <flux:button type="submit" variant="primary" class="bg-[#2E8B57] text-white hover:bg-[#257046]">{{ $editingId ? 'Actualizar' : 'Guardar' }}</flux:button>
            </div>
        </form>
    </flux:modal>

    <flux:modal name="confirm-delete" class="md:w-[400px]">
        <div class="space-y-5">
            <div class="flex items-start gap-4 px-6 pt-4">
                <div class="flex-shrink-0 w-10 h-10 rounded-full bg-red-100 flex items-center justify-center">
                    <svg class="h-5 w-5 text-[#E53935]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>
                <div>
                    <flux:heading size="lg" class="text-[#E53935]">Confirmar eliminacion</flux:heading>
                    <flux:text class="mt-1 text-sm text-[#666666]">Esta seguro de que desea eliminar este empleado? Esta accion no se puede deshacer.</flux:text>
                </div>
            </div>
            <div class="px-6 py-4 bg-[#F5F5F5] border-t border-[#E0E0E0] flex justify-end gap-3">
                <flux:button x-on:click="Flux.modal('confirm-delete').close()" type="button" variant="ghost" class="text-[#333333]">Cancelar</flux:button>
                <flux:button wire:click="delete" variant="danger" class="bg-[#E53935] text-white hover:bg-[#C62828]">Eliminar</flux:button>
            </div>
        </div>
    </flux:modal>
</div>