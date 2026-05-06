# RSU-USAT

Aplicación web construida con [Laravel](https://laravel.com) y [Livewire](https://livewire.laravel.com), utilizando [Flux UI](https://fluxui.dev) para componentes de interfaz.

## Requisitos previos

- PHP 8.3 o superior
- [Composer](https://getcomposer.org/)
- [Node.js](https://nodejs.org/) (v18+)
- [NPM](https://www.npmjs.com/) o [Bun](https://bun.sh/)
- PostgreSQL (o el driver de base de datos configurado en `.env.example`)

## Instalación

### 1. Clonar el repositorio

```bash
git clone https://github.com/your-username/rsu-usat.git
cd rsu-usat
```

### 2. Instalar dependencias de PHP

```bash
composer install
```

### 3. Configurar el entorno

Copiar el archivo de entorno de ejemplo:

```bash
cp .env.example .env
```

Generar la clave de aplicación:

```bash
php artisan key:generate
```

> Configura las variables de base de datos en `.env` según tu entorno local.

### 4. Instalar dependencias de JavaScript

```bash
npm install
```

### 5. Ejecutar migraciones

```bash
php artisan migrate
```

Si necesitas incluir datos de prueba:

```bash
php artisan db:seed
```

O migrar y seedear en un solo paso:

```bash
php artisan migrate:fresh --seed
```

### 6. Compilar assets

```bash
npm run build
```

## Instalación rápida

Si prefieres ejecutar todo en un solo comando:

```bash
composer run setup
```

Este script automatiza: `composer install`, copiar `.env`, generar key, migrar, `npm install` y `npm run build`.

## Desarrollo

Iniciar el servidor de desarrollo con Vite, queue worker y el servidor PHP en paralelo:

```bash
composer run dev
```

Esto ejecuta simultáneamente:

- `php artisan serve` - Servidor de Laravel
- `php artisan queue:listen --tries=1` - Worker de colas
- `npm run dev` - Vite con hot-reload

O puedes ejecutar cada proceso en terminales separadas:

```bash
php artisan serve
npm run dev
```

## Livewire

### Crear componentes

Crear un componente nuevo:

```bash
php artisan make:livewire Dashboard
php artisan make:livewire Users --test
php artisan make:livewire ProfileSettings --form
```

### Componentes con acciones

Para crear componentes con un controlador separado:

```bash
php artisan make:livewire ShowPost --inline
```

### Comandos útiles de Livewire

```bash
php artisan livewire:layout          # Generar layout base
php artisan livewire:stubs           # Publicar stubs para personalizar
php artisan livewire:attribute       # Ver información sobre atributos
php artisan cache:clear              # Limpiar caché si los componentes no se actualizan
php artisan livewire:discover        # Descubrer nuevos componentes
php artisan view:clear               # Limpiar vista cacheada
```

## Testing

Ejecutar los tests con [Pest](https://pestphp.com/):

```bash
php artisan test
```

Ejecutar tests con linting incluido:

```bash
composer run test
```

## Limpieza y reinicio

### Resetear base de datos completa

```bash
php artisan migrate:fresh
```

### Resetear base de datos y datos de prueba

```bash
php artisan migrate:fresh --seed
```

### Limpiar cachés

```bash
php artisan optimize:clear
```

### Limpiar caché de configuración

```bash
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

### Resetear por completo (base de datos + caché + dependencias)

```bash
php artisan migrate:fresh --seed
npm run build
composer dump-autoload
```

## Estructura de Livewire

```
app/Livewire/         # Componentes Livewire
tests/Feature/Livewire/  # Tests de componentes
resources/views/livewire/ # Vistas de componentes
resources/views/layouts/  # Layouts principales
```

## Linting y estilo de código

Verificar estilo de código con Pint:

```bash
composer run lint:check
```

Corregir automáticamente problemas de estilo:

```bash
composer run lint
```

## Comandos adicionales útiles

```bash
php artisan make:livewire MyComponent    # Crear componente Livewire
php artisan make:component FormInput     # Crear componente Blade
php artisan make:migration create_table  # Crear migración
php artisan make:seeder UserSeeder       # Crear seeder
php artisan make:factory UserFactory     # Crear factory
php artisan db:seed --class=UserSeeder   # Ejecutar un seeder específico
php artisan storage:link                 # Crear enlace simbólico a storage
php artisan serve --port=8080            # Iniciar servidor en puerto personalizado
```

## Recursos

- [Documentación de Laravel](https://laravel.com/docs)
- [Documentación de Livewire](https://livewire.laravel.com/docs)
- [Documentación de Flux UI](https://fluxui.dev/docs)
- [Documentación de Pest](https://pestphp.com/docs)
