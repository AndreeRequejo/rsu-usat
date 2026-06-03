<?php

namespace App\Livewire\Pages\Personnel;

use App\Models\Employee as EmployeeModel;
use App\Models\EmployeeType;
use App\Models\User;
use Flux\Flux;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class Employee extends Component
{
    use WithFileUploads;
    use WithPagination;

    public string $search = '';

    public bool $showFormModal = false;

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
                Rule::unique('users', 'email')->ignore($this->editingId ? EmployeeModel::find($this->editingId)?->user_id : null),
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
        return EmployeeModel::query()
            ->with(['employeeType', 'user', 'employeeImages'])
            ->when($this->search !== '', function ($query) {
                $query->where(function ($q) {
                    $q->where('dni', 'like', '%'.$this->search.'%')
                        ->orWhere('first_name', 'like', '%'.$this->search.'%')
                        ->orWhere('last_name', 'like', '%'.$this->search.'%')
                        ->orWhereHas('user', fn ($uq) => $uq->where('email', 'like', '%'.$this->search.'%'));
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
        $this->editingId = null;
        $this->showFormModal = true;
        Flux::modal('employee-form')->show();
    }

    public function openEdit(int $id): void
    {
        $employee = EmployeeModel::with('user')->findOrFail($id);
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
        $this->photo = null;
        $this->showFormModal = true;
        Flux::modal('employee-form')->show();
    }

    public function closeFormModal(): void
    {
        $this->resetValidation();
        $this->reset(['dni', 'employee_type_id', 'first_name', 'last_name', 'birthdate', 'email', 'password', 'address', 'phone', 'photo', 'active', 'editingId']);
        $this->showFormModal = false;
        Flux::modal('employee-form')->close();
    }

    public function save(): void
    {
        $this->validate();

        if ($this->editingId) {
            $this->updateEmployee();
        } else {
            $this->createEmployee();
        }

        $this->closeFormModal();
    }

    private function createEmployee(): void
    {
        $user = User::create([
            'name' => "{$this->first_name} {$this->last_name}",
            'email' => $this->email,
            'password' => bcrypt($this->password),
        ]);

        $employee = EmployeeModel::create([
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
            $this->savePhoto($employee);
        }

        Flux::toast(variant: 'success', text: 'Empleado registrado correctamente.');
    }

    private function updateEmployee(): void
    {
        $employee = EmployeeModel::with('user')->findOrFail($this->editingId);

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
            $this->savePhoto($employee);
        }

        Flux::toast(variant: 'success', text: 'Empleado actualizado correctamente.');
    }

    private function savePhoto(EmployeeModel $employee): void
    {
        $path = $this->photo->storeAs('employees', "{$employee->id}.{$this->photo->getClientOriginalExtension()}", 'public');

        $employee->employeeImages()->where('profile', true)->update(['profile' => false]);
        $employee->employeeImages()->create([
            'image' => $path,
            'profile' => true,
        ]);
    }

    public function confirmDelete(int $id): void
    {
        $this->deletingId = $id;
        Flux::modal('confirm-delete')->show();
    }

    public function delete(): void
    {
        if (! $this->deletingId) {
            return;
        }

        $employee = EmployeeModel::findOrFail($this->deletingId);

        $activeContractsCount = $employee->contracts()->where('is_active', true)->count();
        if ($activeContractsCount > 0) {
            $employeeName = trim("{$employee->last_name} {$employee->first_name}");
            Flux::toast(
                variant: 'warning',
                text: "El empleado {$employeeName} tiene contratos activos."
            );
            $this->deletingId = null;
            Flux::modal('confirm-delete')->close();

            return;
        }

        $hasAnyContracts = $employee->contracts()->exists();
        if ($hasAnyContracts) {
            $employee->update(['active' => false]);
            Flux::toast(variant: 'success', text: 'Empleado desactivado correctamente.');
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

    public function render()
    {
        return view('pages.personnel.personnel.index');
    }
}
