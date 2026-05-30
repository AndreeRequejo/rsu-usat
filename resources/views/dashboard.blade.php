<x-layouts::app :title="__('RSU - USAT')">

    <div class="space-y-6">

        <div class="bg-green-700 text-white rounded-xl p-6">
            <h1 class="text-3xl font-bold">
                Sistema RSU - USAT
            </h1>

            <p class="mt-2 text-green-100">
                Gestión de Residuos Sólidos Universitarios y administración de vehículos de recolección.
            </p>
        </div>



        <div class="bg-white rounded-xl shadow p-6">
            <h2 class="text-xl font-semibold">
                Bienvenido
            </h2>

            <p class="text-gray-600 mt-2">
                Desde este panel podrá administrar la información relacionada con los vehículos utilizados en la recolección y gestión de residuos sólidos de la Universidad Católica Santo Toribio de Mogrovejo.
            </p>
        </div>
        <div class="bg-white rounded-xl shadow p-6">
            <h2 class="text-xl font-semibold mb-4">
                Gestión de Vehículos
            </h2>

            <div class="grid gap-4 md:grid-cols-4">
                <a href="{{ route('vehicles.colors') }}"
                   class="bg-gray-100 rounded-xl shadow p-5 hover:bg-green-50 transition flex flex-col items-center justify-center gap-2">
                    <i class="fas fa-palette fa-2x text-green-500"></i>
                    <span>Colores</span>
                </a>

                <a href="{{ route('vehicles.brands.index') }}"
                   class="bg-gray-100 rounded-xl shadow p-5 hover:bg-green-50 transition flex flex-col items-center justify-center gap-2">
                    <i class="fas fa-tags fa-2x text-green-500"></i>
                    <span>Marcas</span>
                </a>

                <a href="{{ route('vehicles.models.index') }}"
                   class="bg-gray-100 rounded-xl shadow p-5 hover:bg-green-50 transition flex flex-col items-center justify-center gap-2">
                    <i class="fas fa-car-side fa-2x text-green-500"></i>
                    <span>Modelos</span>
                </a>

                <a href="{{ route('vehicles.types.index') }}"
                   class="bg-gray-100 rounded-xl shadow p-5 hover:bg-green-50 transition flex flex-col items-center justify-center gap-2">
                    <i class="fas fa-truck fa-2x text-green-500"></i>
                    <span>Tipos</span>
                </a>
            </div>
        </div>
    </div>

</x-layouts::app>