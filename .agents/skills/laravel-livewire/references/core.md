# Core Livewire Concepts

## Installation & Setup

### Install Livewire

```bash
composer require livewire/livewire
```

### Create Default Layout

```bash
php artisan livewire:layout
```

This creates `resources/views/layouts/app.blade.php`:

```blade
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>{{ $title ?? config('app.name') }}</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @livewireStyles
    </head>
    <body>
        {{ $slot }}
        @livewireScripts
    </body>
</html>
```

### Configuration

```bash
php artisan livewire:config  # Publish config file
php artisan livewire:stubs    # Publish customizable stubs
```

## Creating Components

### Single-File Components (Default)

```bash
php artisan make:livewire CreatePost
# Creates: resources/views/components/post/⚡create.blade.php
```

```php
<?php
use Livewire\Component;
new class extends Component {
    public $title = '';
    public function save()
    {
        Post::create(['title' => $this->title]);
    }
};
?>
<div>
    <input wire:model="title">
    <button wire:click="save">Save</button>
</div>
```

### Page Components

```bash
php artisan make:livewire pages::post.create
# Creates: resources/views/pages/post/⚡create.blade.php
```

```php
// routes/web.php
Route::livewire('/posts/create', 'pages::post.create');
```

### Multi-File Components

```bash
php artisan make:livewire CreatePost --mfc
```

Creates a directory structure:
```
resources/views/components/post/⚡create/
├── create.php          # PHP class
├── create.blade.php    # Blade template
├── create.js           # JavaScript (optional)
├── create.css          # Scoped styles (optional)
└── create.test.php     # Pest test (optional with --test)
```

### Class-Based Components (Traditional)

```bash
php artisan make:livewire CreatePost --class
```

Creates:
- `app/Livewire/CreatePost.php`
- `resources/views/livewire/create-post.blade.php`

```php
// app/Livewire/CreatePost.php
namespace App\Livewire;
use Livewire\Component;

class CreatePost extends Component
{
    public $title = '';
    public function render()
    {
        return view('livewire.create-post');
    }
}
```

### Converting Between Formats

```bash
# Auto-detect and convert
php artisan livewire:convert post.create

# Explicitly convert to multi-file
php artisan livewire:convert post.create --mfc

# Explicitly convert to single-file
php artisan livewire:convert post.create --sfc
```

## Rendering Components

### Basic Rendering

```blade
<livewire:component-name />

<!-- With namespace -->
<livewire:pages::post.create />

<!-- Nested in directory -->
<livewire:post.create />
```

### Passing Props

```blade
<!-- Static prop -->
<livewire:component-name title="Initial Title" />

<!-- Dynamic prop -->
<livewire:component-name :title="$initialTitle" />

<!-- Multiple props -->
<livewire:post.create :$post :title="$post->title" />

<!-- Boolean shorthand -->
<livewire:todos inline />
```

### Receiving Props

```php
// In component's mount() method
public function mount($title = null)
{
    $this->title = $title;
}

// Or auto-matched property names (no mount() needed)
public $title; // Automatically set from :title prop
```

### Route Parameters as Props

```php
// routes/web.php
Route::livewire('/posts/{id}', 'pages::post.show');

// In component
public $postId;
public function mount($id)
{
    $this->postId = $id;
}

// With route model binding
Route::livewire('/posts/{post}', 'pages::post.edit');
public Post $post; // Automatically bound
```

## Properties

### Property Types

```php
// Primitive types
public string $name = '';
public int $count = 0;
public bool $active = false;
public ?string $optional = null;

// Arrays
public array $items = [];

// Typed arrays
public array $todos = [];

// Eloquent models
public Post $post;  // Auto-locks the ID
public ?Post $post = null;

// Collections
use Illuminate\Support\Collection;
public Collection $users;

// Enums
use App\Enums\Status;
public Status $status;
```

### Supported Property Types

| Type | Notes |
|------|-------|
| `string`, `int`, `float`, `bool`, `array`, `null` | Primitives - always supported |
| `BackedEnum` | Enum types with scalar values |
| `Collection` | Laravel collections |
| `Eloquent\Collection` | Eloquent query results |
| `Model` | Eloquent models (ID auto-locked) |
| `DateTime`, `Carbon` | Date objects |
| `Stringable` | Laravel string objects |

### Initializing Properties

```php
public function mount()
{
    $this->todos = ['Buy groceries', 'Walk the dog'];
}

// Bulk assignment
public function mount(Post $post)
{
    $this->post = $post;
    $this->fill(
        $post->only(['title', 'description'])
    );
}
```

### Resetting Properties

```php
// Reset single property
$this->reset('title');

// Reset multiple properties
$this->reset(['title', 'content']);

// Reset and retrieve value
$value = $this->pull('title');
$data = $this->pull(['title', 'content']);
```

### Property Visibility

```php
// Public - accessible in template as $property, sent to client
public $title = '';

// Protected - accessible as $this->property, NOT sent to client
protected $apiKey = 'secret';

// Protected for sensitive data that shouldn't be exposed
protected function getSecureData()
{
    return $this->apiKey;
}
```

## Actions (Methods)

### Basic Actions

```php
public function save()
{
    Post::create($this->only(['title', 'content']));
}

public function delete($id)
{
    Post::findOrFail($id)->delete();
}
```

### Passing Parameters

```blade
<!-- From template -->
<button wire:click="delete({{ $post->id }})">Delete</button>

<!-- With model binding -->
<button wire:click="delete({{ $post }})">Delete</button>
```

```php
// Receiving parameters
public function delete($id)
{
    $post = Post::findOrFail($id);
    $post->delete();
}

// With route model binding
public function delete(Post $post)
{
    $this->authorize('delete', $post);
    $post->delete();
}
```

### Dependency Injection

```php
use App\Repositories\PostRepository;

public function delete(PostRepository $posts, $postId)
{
    $posts->deletePost($postId);
}
```

### Event Listeners

```blade
<button wire:click="save">Save</button>
<input wire:keydown.enter="search">
<input wire:keydown.shift.enter="advancedSearch">
<form wire:submit="submitForm">
```

### Event Modifiers

```blade
<!-- Key modifiers -->
<input wire:keydown.enter="search">
<input wire:keydown.shift.enter="...">
<input wire:keydown.escape="cancel">

<!-- Event modifiers -->
<button wire:click.prevent="save">
<button wire:click.stop="...">
<button wire:click.window="...">
<button wire:click.outside="...">
<button wire:click.once="...">
<button wire:click.debounce.250ms="...">
<button wire:click.throttle.100ms="...">
<button wire:click.self="...">
```

### Security for Actions

```php
// Always authorize action parameters
public function delete($id)
{
    $post = Post::find($id);
    $this->authorize('delete', $post);
    $post->delete();
}

// Keep dangerous methods protected/private
protected function dangerousOperation()
{
    // Cannot be called from client
}
```

## Lifecycle Hooks

### Hook Execution Order

```
Initial Request:
mount() → boot() → render() → rendering() → rendered() → dehydrate()

Subsequent Request:
hydrate() → boot() → updating() → [property update] → updated() → render() → rendering() → rendered() → dehydrate()
```

### Available Hooks

| Hook | When It Runs | Usage |
|------|--------------|-------|
| `mount()` | First load only | Receive props, initialize state |
| `boot()` | Every request | Initialize protected properties |
| `hydrate()` | Start of subsequent requests | Custom deserialization logic |
| `dehydrate()` | End of every request | Custom serialization logic |
| `updating($prop)` | Before property update | Validate/authorize property changes |
| `updated($prop)` | After property update | Transform/format updated values |
| `rendering($view, $data)` | Before render() | Modify view data |
| `rendered($view, $html)` | After render() | Post-render processing |
| `exception($e, $stopPropagation)` | When exception thrown | Custom error handling |

### Hook Examples

```php
// Mount - receive props and route parameters
public function mount(Post $post)
{
    $this->post = $post;
    $this->title = $post->title;
}

// Boot - runs every request
public function boot()
{
    // Initialize non-persisted properties
    $this->currentUser = Auth::user();
}

// Property update hooks
public function updating($property, $value)
{
    if ($property === 'postId') {
        throw new Exception('Cannot modify post ID');
    }
}

public function updated($property)
{
    if ($property === 'username') {
        $this->username = strtolower($this->username);
    }
}

// Specific property hooks
public function updatedTitle($value)
{
    $this->title = ucwords($value);
}

// Array update hooks
public function updatedPreferences($value, $key)
{
    // $key = 'theme', $value = 'dark'
}

// Hydrate/Dehydrate for custom types
public function hydrate()
{
    $this->post = new PostDto($this->post);
}

public function dehydrate()
{
    $this->post = $this->post->toArray();
}

// Exception handling
public function exception($e, $stopPropagation)
{
    if ($e instanceof NotFoundException) {
        $this->notify('Post not found');
        $stopPropagation();
    }
}
```

### Hooks in Traits

```php
trait HasPostForm
{
    public function mountHasPostForm()
    {
        // Runs during component mount
    }

    public function bootHasPostForm()
    {
        // Runs during component boot
    }
}
```

### Hooks in Form Objects

```php
namespace App\Livewire\Forms;
use Livewire\Form;

class PostForm extends Form
{
    public $title = '';

    public function updating($property, $value)
    {
        // Before property update
    }

    public function updated($property, $value)
    {
        // After property update
    }

    public function updatedTitle($value)
    {
        // After title update
    }
}
```

## Component Organization

### Component Namespaces

```php
// config/livewire.php
'component_namespaces' => [
    'layouts' => resource_path('views/layouts'),
    'pages' => resource_path('views/pages'),
    'admin' => resource_path('views/admin'),
    'widgets' => resource_path('views/widgets'),
],
```

```bash
# Using custom namespaces
php artisan make:livewire admin::users-table

# Render in templates
<livewire:admin::users-table />

# Route to page
Route::livewire('/admin/users', 'admin::users-table');
```

### Additional Component Locations

```php
// config/livewire.php
'component_locations' => [
    resource_path('views/components'),
    resource_path('views/admin/components'),
    resource_path('views/widgets'),
],
```

### Programmatic Registration

```php
use Livewire\Livewire;

// In service provider's boot() method
Livewire::addComponent(
    name: 'custom-button',
    viewPath: resource_path('views/ui/button.blade.php')
);

Livewire::addLocation(
    viewPath: resource_path('views/admin/components')
);

Livewire::addNamespace(
    namespace: 'ui',
    viewPath: resource_path('views/ui')
);
```

### Class-Based Component Registration

```php
Livewire::addComponent(
    name: 'todos',
    class: \App\Livewire\Todos::class
);

Livewire::addLocation(
    classNamespace: 'App\\Admin\\Livewire'
);

Livewire::addNamespace(
    namespace: 'admin',
    classNamespace: 'App\\Admin\\Livewire',
    classPath: app_path('Admin/Livewire'),
    classViewPath: resource_path('views/admin/livewire')
);
```

## Customizing Stubs

After running `php artisan livewire:stubs`, customize these files:

- `stubs/livewire-sfc.stub` — Single-file components
- `stubs/livewire-mfc-class.stub` — Multi-file PHP class
- `stubs/livewire-mfc-view.stub` — Multi-file Blade view
- `stubs/livewire-mfc-js.stub` — Multi-file JavaScript
- `stubs/livewire-mfc-test.stub` — Multi-file Pest test
- `stubs/livewire.stub` — Class-based PHP class
- `stubs/livewire.view.stub` — Class-based Blade view

## Troubleshooting

### Component Not Found

- Verify file exists at expected path
- Check component name matches file structure
- For namespaced components, verify namespace is defined
- Try `php artisan view:clear`

### Component Shows Blank

- Missing root element in Blade template
- Syntax errors in PHP section
- Check Laravel logs

### Class Name Conflicts

- Rename components to have unique names
- Use namespaces for better organization
