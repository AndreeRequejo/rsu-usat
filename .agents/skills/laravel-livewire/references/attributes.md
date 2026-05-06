# PHP Attributes Reference

Livewire v4 uses PHP attributes for component configuration. Import attributes from `Livewire\Attributes\`.

## #[Validate]

Add validation rules to properties.

```php
use Livewire\Attributes\Validate;

#[Validate('required|min:5')]
public $title = '';

#[Validate('required|email', message: 'Please enter a valid email')]
public $email = '';

#[Validate('required', as: 'date of birth')]
public $dob = '';

// Multiple rules with different messages
#[Validate('required', message: 'Please enter a title.')]
#[Validate('min:5', message: 'Your title is too short.')]
public $title = '';

// Array validation
#[Validate([
    'todos' => 'required',
    'todos.*' => ['required', 'min:3', new Uppercase],
])]
public $todos = [];

// With custom messages and attributes
#[Validate([
    'titles' => 'required',
    'titles.*' => 'required|min:5',
], message: [
    'required' => 'The :attribute is missing.',
    'min' => 'The :attribute is too short.',
], attribute: [
    'titles.*' => 'title',
])]
public $titles = [];

// Disable auto-validation
#[Validate('required|min:5', onUpdate: false)]
public $title = '';

// Enable real-time with rules() method
#[Validate]
public $title = '';

protected function rules()
{
    return ['title' => 'required|min:5'];
}
```

### Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| `rules` | string\|array | Validation rules |
| `message` | string\|array | Custom error message(s) |
| `as` | string | Custom attribute name |
| `attribute` | array | Custom attribute names for array rules |
| `onUpdate` | bool | Auto-validate on property updates (default: true) |
| `translate` | bool | Translate messages (default: true) |

## #[Computed]

Create memoized computed properties.

```php
use Livewire\Attributes\Computed;

#[Computed]
public function posts()
{
    return Post::all(); // Runs once per request
}

// Persist across requests
#[Computed(persist: true)]
public function user()
{
    return User::find($this->userId);
}

// Custom cache duration
#[Computed(persist: true, seconds: 7200)]
public function cachedData()
{
    return ExpensiveModel::all();
}

// Global cache (shared across all components)
#[Computed(cache: true)]
public function posts()
{
    return Post::all();
}

// With custom cache key
#[Computed(cache: true, key: 'homepage-posts')]
public function posts()
{
    return Post::all();
}
```

### Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| `persist` | bool | Cache across component requests |
| `seconds` | int | Cache duration in seconds (default: 3600) |
| `cache` | bool | Use global application cache |
| `key` | string | Custom cache key |

## #[Locked]

Prevent client-side modification of properties.

```php
use Livewire\Attributes\Locked;

#[Locked]
public $postId = 1;
```

Eloquent models are automatically locked:

```php
public Post $post; // ID is auto-locked
```

## #[On]

Listen for dispatched events.

```php
use Livewire\Attributes\On;

#[On('post-created')]
public function handlePostCreated($title)
{
    // Handle event
}

// Dynamic event names
#[On('post-updated.{post.id}')]
public function refreshPost()
{
    // Only triggered for specific post ID
}

// Laravel Echo events
#[On('echo:orders,OrderShipped')]
public function notifyNewOrder()
{
    $this->showNotification = true;
}

// Dynamic Echo channels
#[On('echo:orders.{order.id},OrderShipped')]
public function notifyNewOrder($event)
{
    $order = Order::find($event['orderId']);
}

// Custom broadcast names (dot prefix required)
#[On('echo:scores,.score.submitted')]
public function handleScoreSubmitted($event)
{
    $this->scores[] = $event['score'];
}

// Presence channels
#[On('echo-presence:orders,here')]
public function userHere($event)
{
    // User is present
}

#[On('echo-presence:orders,joining')]
public function userJoining($event)
{
    // User joined
}

#[On('echo-presence:orders,leaving')]
public function userLeaving($event)
{
    // User left
}
```

### Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| `name` | string | Event name (supports wildcards) |

## #[Reactive]

Make props reactive in nested components.

```php
use Livewire\Attributes\Reactive;

#[Reactive]
public $todos;
```

Now when parent's `$todos` changes, child automatically updates.

## #[Modelable]

Allow parent to bind to child property via `wire:model`.

```php
use Livewire\Attributes\Modelable;

new class extends Component {
    #[Modelable]
    public $value = '';
};
?>
<div>
    <input type="text" wire:model="value">
</div>
```

```blade
{{-- Parent usage --}}
<livewire:todo-input wire:model="todo" />
```

## #[Lazy]

Defer component loading until needed.

```php
use Livewire\Attributes\Lazy;

#[Lazy]
class RevenueChart extends Component
{
    public function placeholder()
    {
        return view('livewire.placeholders.skeleton');
    }

    public function render()
    {
        return view('livewire.revenue-chart');
    }
}
```

```blade
<livewire:revenue-chart lazy />
```

## #[Session]

Persist properties in session across page refreshes.

```php
use Livewire\Attributes\Session;

#[Session]
public $search = '';

#[Session]
public $filters = [];
```

## #[Url]

Sync properties with URL query string.

```php
use Livewire\Attributes\Url;

#[Url]
public $search = '';

#[Url]
public $category = '';

// With history
#[Url(history: true)]
public $search = '';

// Except default value
#[Url(except: '')]
public $search = '';
```

## #[Renderless]

Skip re-render after action execution.

```php
use Livewire\Attributes\Renderless;

#[Renderless]
public function incrementViewCount()
{
    $this->post->incrementViewCount();
    // Component won't re-render
}
```

Or use the modifier:

```blade
<button wire:click.renderless="incrementViewCount">
```

## #[Async]

Execute action in parallel (bypass request queue).

```php
use Livewire\Attributes\Async;

#[Async]
public function logActivity()
{
    Activity::log('page-viewed');
}
```

Or use the modifier:

```blade
<button wire:click.async="logActivity">Track Event</button>
```

**Warning:** Never use async for actions that modify component state displayed in UI.

## #[Js]

Return JSON for JavaScript consumption (skip re-render, promise-based).

```php
use Livewire\Attributes\Js;

#[Js]
public function fetchSuggestions($query)
{
    return Post::where('title', 'like', "%{$query}%")
        ->limit(5)
        ->pluck('title');
}
```

```blade
<div x-data="{ suggestions: [] }">
    <input x-on:input.debounce="suggestions = await $wire.fetchSuggestions($event.target.value)">
    <template x-for="suggestion in suggestions">
        <div x-text="suggestion"></div>
    </template>
</div>
```

## #[Layout]

Specify custom layout for page components.

```php
use Livewire\Attributes\Layout;

#[Layout('layouts.admin')]
class AdminDashboard extends Component
{
    // ...
}

// With data
#[Layout('layouts.admin', ['title' => 'Admin Dashboard'])]
class AdminDashboard extends Component
{
    // ...
}
```

## #[Title]

Set page title for page components.

```php
use Livewire\Attributes\Title;

#[Title('Create Post')]
class CreatePost extends Component
{
    // ...
}

// Dynamic title
#[Title($this->post->title)]
class ShowPost extends Component
{
    public Post $post;
    // ...
}
```

## #[Locked]

Lock property to prevent client-side manipulation.

```php
use Livewire\Attributes\Locked;

#[Locked]
public $postId = 1;
```

Eloquent models are automatically locked:

```php
public Post $post; // Auto-locked
```

## #[Isolate]

Prevent component from receiving parent updates.

```php
use Livewire\Attributes\Isolate;

#[Isolate]
class IndependentWidget extends Component
{
    // Won't update when parent updates
}
```

## #[Js] (Alternative)

For JavaScript-only actions run entirely on client.

```blade
<script>
    this.$js.bookmark = () => {
        $wire.bookmarked = !$wire.bookmarked
        $wire.bookmarkPost()
    }
</script>
```

```blade
<button wire:click="$js.bookmark()">Bookmark</button>
```

## #[Defer]

Defer property updates until next action.

```php
use Livewire\Attributes\Defer;

#[Defer]
public $expensiveValue = null;
```

## #[Json]

Mark action as JSON-returning (similar to #[Js] but more explicit).

```php
use Livewire\Attributes\Json;

#[Json]
public function getData()
{
    return ['key' => 'value'];
}
```

## #[Form]

Mark property as a form object.

```php
use Livewire\Form;
use Livewire\Attributes\Form;

class CreatePost extends Component
{
    #[Form]
    public PostForm $form;
}
```

## Attribute Combinations

You can combine multiple attributes:

```php
use Livewire\Attributes\{Computed, Locked, Session};

#[Computed(persist: true)]
#[Locked]
#[Session]
public $userId;
```

## Form Object Attributes

Form objects support:

```php
use Livewire\Form;
use Livewire\Attributes\Validate;

class PostForm extends Form
{
    #[Validate('required|min:5')]
    public $title = '';

    #[Validate]
    public $content = ''; // Use rules() method

    protected function rules()
    {
        return [
            'content' => 'required|min:10',
        ];
    }
}
```

## Trait Method Attributes

Prefix lifecycle hooks with trait name for organization:

```php
trait HasPostForm
{
    public $title = '';

    public function mountHasPostForm()
    {
        $this->title = 'Default';
    }

    public function bootHasPostForm()
    {
        // Initialize on every request
    }

    public function updatingHasPostForm($property, $value)
    {
        // Before update
    }

    public function updatedHasPostForm($property, $value)
    {
        // After update
    }
}
```

## Custom Attributes

You can create custom attributes for specific use cases. See [Synthesizers documentation](https://livewire.laravel.com/docs/4.x/synthesizers) for advanced type handling.
