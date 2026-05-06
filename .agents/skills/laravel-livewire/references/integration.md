# Integration: Alpine.js, JavaScript, Security

## Alpine.js Integration

Livewire ships with Alpine.js built-in. No separate installation needed.

### Basic Alpine in Livewire

```blade
<div x-data="{ expanded: false }">
    <button @click="expanded = !expanded">Toggle</button>
    <div x-show="expanded">
        {{ $content }}
    </div>
</div>
```

### Accessing Livewire from Alpine

Livewire exposes `$wire` object to Alpine as a JavaScript representation of your component.

#### Access Properties

```blade
<div x-data="{ count: $wire.title.length }">
    <h2 x-text="count"></h2>
</div>
```

#### Mutate Properties

```blade
<button x-on:click="$wire.title = ''">Clear</button>
```

This updates client-side immediately, syncs to server on next request.

#### Call Methods

```blade
<button x-on:click="$wire.save()">Save Post</button>

<!-- With parameters -->
<button x-on:click="$wire.deletePost({{ $post->id }})">Delete</button>

<!-- Using blur -->
<input x-on:blur="$wire.save()">
```

#### Refresh Component

```blade
<button x-on:click="$wire.$refresh()">Refresh</button>
```

#### Magic Actions

```blade
<!-- Toggle boolean -->
<button x-on:click="$wire.$toggle('showModal')">Toggle</button>

<!-- Set property -->
<button x-on:click="$wire.$set('query', '')">Reset</button>

<!-- Dispatch event -->
<button x-on:click="$wire.$dispatch('post-saved', { id: {{ $post->id }} })">Save</button>
```

### Alpine Entanglement (Deprecated)

The `@entangle` directive is deprecated. Use direct `$wire` access instead.

```blade
<!-- DEPRECATED - Don't use -->
<div x-data="{ open: @entangle('showDropdown') }">

<!-- Instead use direct access -->
<div x-data="{ open: $wire.showDropdown }">
```

### Using @js Directive

```blade
<div>
    <button wire:click="$js.bookmark">Bookmark</button>
</div>
<script>
    this.$js.bookmark = () => {
        $wire.bookmarked = !$wire.bookmarked
        $wire.bookmarkPost()
    }
</script>
```

### Alpine Stores

```blade
<div x-data="notificationStore">
    <button @click="notify('Hello')">Notify</button>
</div>
```

### Alpine Components

```blade
<div x-data="counter()">
    <button @click="increment()">+</button>
    <span x-text="count"></span>
</div>
```

## JavaScript in Livewire

### Component Scripts

For single-file and multi-file components:

```blade
<div>
    <button wire:click="save">Save</button>
</div>

<script>
    // Listen for events
    this.$on('post-created', () => {
        console.log('Post created');
    });

    // Dispatch events
    this.$dispatch('post-created');

    // Dispatch to self only
    this.$dispatchSelf('post-created');

    // Access element
    this.$el.querySelector('button').focus();

    // JavaScript actions
    this.$js.bookmark = () => {
        $wire.bookmarked = !$wire.bookmarked
        $wire.bookmarkPost()
    }
</script>
```

For class-based components, wrap with `@script`:

```blade
@script
<script>
    this.$on('post-created', () => {
        console.log('Post created');
    });
</script>
@endscript
```

### File Upload API

```blade
<script>
    // Upload single file
    let file = $wire.el.querySelector('input[type="file"]').files[0]
    $wire.upload('photo', file,
        (uploadedFilename) => {
            // Success callback
        },
        () => {
            // Error callback
        },
        (event) => {
            // Progress callback
            // event.detail.progress = 0-100
        },
        () => {
            // Cancelled callback
        }
    )

    // Upload multiple files
    $wire.uploadMultiple('photos', [file], successCallback, errorCallback, progressCallback, cancelledCallback)

    // Remove single file
    $wire.removeUpload('photos', uploadedFilename, successCallback)

    // Cancel upload
    $wire.cancelUpload('photo')
</script>
```

### Custom JavaScript Bundling

Add `@livewireScriptConfig` to layout:

```blade
<!DOCTYPE html>
<html>
<head>
    @livewireStyles
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    {{ $slot }}
    @livewireScriptConfig
</body>
</html>
```

Import Livewire and Alpine in `resources/js/app.js`:

```javascript
import { Livewire, Alpine } from '../../vendor/livewire/livewire/dist/livewire.esm';

// Register custom Alpine directive
Alpine.directive('clipboard', (el) => {
    let text = el.textContent
    el.addEventListener('click', () => {
        navigator.clipboard.writeText(text)
    })
})

Livewire.start()
```

## Event Integration

### Dispatching from Alpine

```blade
<button x-on:click="$dispatch('post-created', { title: 'Post Title' })">
    Create Post
</button>
```

### Listening in Alpine

```blade
<!-- Listen for Livewire events -->
<div x-on:post-created="notify('New post!')"></div>

<!-- Listen on window -->
<div x-on:post-created.window="notify('New post!')"></div>

<!-- Access event data -->
<div x-on:post-created="notify('New post: ' + $event.detail.title)"></div>
```

### Global JavaScript Events

```blade
<script>
    document.addEventListener('livewire:init', () => {
        Livewire.on('post-created', (event) => {
            console.log('Post created', event);
        });

        // With cleanup
        let cleanup = Livewire.on('post-created', (event) => {
            console.log('Post created', event);
        });

        // Call cleanup() when needed
        cleanup();
    });
</script>
```

## Security

### Authorization

Always authorize action parameters - they are user input:

```php
public function delete($id)
{
    $post = Post::find($id);
    $this->authorize('delete', $post);
    $post->delete();
}
```

### Property Validation

Treat public properties as user input - validate and authorize:

```php
public function save()
{
    $this->validate();
    $this->authorize('create', Post::class);
    Post::create($this->only(['title', 'content']));
}
```

### Locking Properties

Use `#[Locked]` for sensitive IDs:

```php
use Livewire\Attributes\Locked;

#[Locked]
public $postId = 1;

// Eloquent models auto-lock
public Post $post; // ID is locked
```

### Method Visibility

Keep dangerous methods protected or private:

```php
public function deleteUser()
{
    $this->authorize('delete', $this->user);
    $this->performDelete(); // Protected method
}

protected function performDelete()
{
    // Cannot be called from client
    $this->user->delete();
}
```

### CSRF Protection

Livewire includes CSRF token on all requests. The `@csrf` Blade directive is automatically added to Livewire requests.

### Mass Assignment

Always use `only()` or `except()` when persisting:

```php
Post::create($this->only(['title', 'content']));
// Or
Post::create($this->except(['remember_token']));
```

### File Upload Security

```php
use Livewire\WithFileUploads;
use Livewire\Attributes\Validate;

new class extends Component {
    use WithFileUploads;

    #[Validate('image|max:1024')] // Validate file type and size
    public $photo;

    public function save()
    {
        // Additional validation
        $this->validate();
        $this->photo->store(path: 'photos');
    }
};
```

### Security Best Practices

1. **Always authorize** action parameters and properties
2. **Use `#[Locked]`** for sensitive IDs
3. **Keep dangerous methods protected/private**
4. **Validate all input** before persisting
5. **Use `only()`/`except()`** for mass assignment
6. **Never trust client-side data**
7. **Sanitize output** when displaying user input
8. **Rate limit file uploads** via middleware

## CSP (Content Security Policy)

Livewire supports CSP. Configure in `config/livewire.php`:

```php
'csp' => [
    'enabled' => true,
    'nonce' => true, // Add nonce to all scripts
],
```

## Morphing

Smooth DOM transitions using Morphdom:

```blade
<div wire:transition.duration.500ms>
    {{ $content }}
</div>
```

### Transition Modifiers

```blade
<div wire:transition.fade>
<div wire:transition.duration.500ms>
<div wire:transition.scale>
```

## Hydration/Dehydration

Understanding how Livewire serializes data between server and client.

### Wireable Interface

For custom types:

```php
use Livewire\Wireable;

class Customer implements Wireable
{
    protected $name;
    protected $age;

    public function toLivewire()
    {
        return [
            'name' => $this->name,
            'age' => $this->age,
        ];
    }

    public static function fromLivewire($value)
    {
        return new self($value['name'], $value['age']);
    }
}
```

### Synthesizers

For advanced custom type handling (package-level):

```php
use Livewire\Mechanisms\HandleComponents\Synthesizers\Synthesizer;

class CustomerSynthesizer implements Synthesizer
{
    public static function key() { return 'customer'; }

    public static function set($value, $store) { /* ... */ }

    public static function get($key, $store) { /* ... */ }

    public static function isSet($key, $store) { /* ... */ }

    public static function unset($key, $store) { /* ... */ }
}
```

## Error Handling

### Exception Hook

```php
public function exception($e, $stopPropagation)
{
    if ($e instanceof NotFoundException) {
        $this->notify('Resource not found');
        $stopPropagation();
    }
}
```

### Custom Error Pages

Configure in `config/livewire.php`:

```php
'render_on_redirect' => true,
'redirect middleware' => ['web'],
```

## Performance Optimization

1. **Use computed properties** for expensive queries
2. **Lazy load** components below the fold
3. **Use `.blur` instead of `.live`** when possible
4. **Avoid storing large Eloquent collections**
5. **Use `wire:key`** for list items
6. **Debounce/throttle** live updates
7. **Cache** with `#[Computed(persist: true)]`
8. **Use `#[Locked]`** to reduce data transfer
9. **Use `#[Renderless]`** for side-effect actions
10. **Use islands** instead of nested components when appropriate
