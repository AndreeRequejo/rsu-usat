<x-layouts::app :title="__('RSU - USAT')">

    <div class="space-y-6">

        <div class="bg-green-700 text-white rounded-xl p-6">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center">
                <img src="{{ asset('logo-municipalidad.png') }}" alt="Logo Municipalidad Distrital de José Leonardo Ortiz" class="h-16 w-auto brightness-0 invert">

                <div>
                    <h1 class="text-3xl font-bold">
                        Sistema RSU - USAT
                    </h1>

                    <p class="mt-2 text-green-100">
                        Gestión de Residuos Sólidos Urbanos - Municipalidad Distrital de José Leonardo Ortiz
                    </p>
                </div>
            </div>
        </div>



        <div class="bg-white rounded-xl shadow border border-green-100 border-t-4 border-t-green-700 p-6">
            <h2 class="text-xl font-semibold">
                Bienvenido
            </h2>

            <p class="text-gray-600 mt-2">
                Desde este panel podrá administrar la información relacionada con los vehículos utilizados en la recolección y gestión de residuos sólidos de la Municipalidad de José Leonardo Ortiz.
            </p>
        </div>
        <div class="bg-white rounded-xl shadow border border-green-100 border-t-4 border-t-green-700 p-6">
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold">
                    Gestión de Vehículos
                </h2>
                <span class="text-xs font-semibold uppercase tracking-wide text-green-700 bg-green-100 px-2 py-1 rounded-full">Vehículos</span>
            </div>

            <p class="text-gray-600 mt-2">
                Administre catálogos y configuraciones clave del parque automotor.
            </p>

                 <div class="grid gap-4 mt-4 sm:grid-cols-2 xl:grid-cols-4">
                <a href="{{ route('vehicles.colors') }}"
                     class="bg-green-50 border border-green-200 rounded-xl p-5 hover:border-green-500 hover:shadow-md hover:-translate-y-0.5 transition flex flex-col gap-2">
                    <i class="fas fa-palette fa-2x text-green-600"></i>
                    <span class="font-semibold text-gray-900">Colores</span>
                    <span class="text-sm text-gray-500">Gestione la paleta y el uso por vehículo.</span>
                </a>

                <a href="{{ route('vehicles.brands.index') }}"
                         class="bg-green-50 border border-green-200 rounded-xl p-5 hover:border-green-500 hover:shadow-md hover:-translate-y-0.5 transition flex flex-col gap-2">
                    <i class="fas fa-tags fa-2x text-green-600"></i>
                    <span class="font-semibold text-gray-900">Marcas</span>
                    <span class="text-sm text-gray-500">Organice las marcas registradas.</span>
                </a>

                <a href="{{ route('vehicles.models.index') }}"
                         class="bg-green-50 border border-green-200 rounded-xl p-5 hover:border-green-500 hover:shadow-md hover:-translate-y-0.5 transition flex flex-col gap-2">
                    <i class="fas fa-car-side fa-2x text-green-600"></i>
                    <span class="font-semibold text-gray-900">Modelos</span>
                    <span class="text-sm text-gray-500">Controle los modelos disponibles.</span>
                </a>

                <a href="{{ route('vehicles.types.index') }}"
                         class="bg-green-50 border border-green-200 rounded-xl p-5 hover:border-green-500 hover:shadow-md hover:-translate-y-0.5 transition flex flex-col gap-2">
                    <i class="fas fa-truck fa-2x text-green-600"></i>
                    <span class="font-semibold text-gray-900">Tipos</span>
                    <span class="text-sm text-gray-500">Clasifique los tipos de vehículos.</span>
                </a>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow border border-green-100 border-t-4 border-t-green-700 p-6">
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold">
                    Gestión de Personal
                </h2>
                <span class="text-xs font-semibold uppercase tracking-wide text-green-700 bg-green-100 px-2 py-1 rounded-full">Personal</span>
            </div>

            <p class="text-gray-600 mt-2">
                Administre perfiles, contratos y control de asistencia del personal.
            </p>

                 <div class="grid gap-4 mt-4 sm:grid-cols-2 xl:grid-cols-4">
                <a href="{{ route('personnel.types.index') }}"
                     class="bg-green-50 border border-green-200 rounded-xl p-5 hover:border-green-500 hover:shadow-md hover:-translate-y-0.5 transition flex flex-col gap-2">
                    <i class="fas fa-id-badge fa-2x text-green-600"></i>
                    <span class="font-semibold text-gray-900">Tipos de Personal</span>
                    <span class="text-sm text-gray-500">Defina roles y categorías.</span>
                </a>

                <a href="{{ route('personnel.personnel.index') }}"
                         class="bg-green-50 border border-green-200 rounded-xl p-5 hover:border-green-500 hover:shadow-md hover:-translate-y-0.5 transition flex flex-col gap-2">
                    <i class="fas fa-users fa-2x text-green-600"></i>
                    <span class="font-semibold text-gray-900">Personal</span>
                    <span class="text-sm text-gray-500">Gestione datos del personal activo.</span>
                </a>

                <a href="{{ route('personnel.attendance.index') }}"
                         class="bg-green-50 border border-green-200 rounded-xl p-5 hover:border-green-500 hover:shadow-md hover:-translate-y-0.5 transition flex flex-col gap-2">
                    <i class="fas fa-user-check fa-2x text-green-600"></i>
                    <span class="font-semibold text-gray-900">Asistencia</span>
                    <span class="text-sm text-gray-500">Controle ingresos y salidas.</span>
                </a>

                <a href="{{ route('personnel.contracts.index') }}"
                         class="bg-green-50 border border-green-200 rounded-xl p-5 hover:border-green-500 hover:shadow-md hover:-translate-y-0.5 transition flex flex-col gap-2">
                    <i class="fas fa-file-contract fa-2x text-green-600"></i>
                    <span class="font-semibold text-gray-900">Contratos</span>
                    <span class="text-sm text-gray-500">Administre contratos vigentes.</span>
                </a>

                <a href="{{ route('personnel.vacations.index') }}"
                         class="bg-green-50 border border-green-200 rounded-xl p-5 hover:border-green-500 hover:shadow-md hover:-translate-y-0.5 transition flex flex-col gap-2">
                    <i class="fas fa-umbrella-beach fa-2x text-green-600"></i>
                    <span class="font-semibold text-gray-900">Vacaciones</span>
                    <span class="text-sm text-gray-500">Registre solicitudes y periodos.</span>
                </a>

            </div>
        </div>
    </div>

</x-layouts::app>