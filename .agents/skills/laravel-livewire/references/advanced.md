# Advanced Features

## Nesting Components

### Basic Nesting

```blade
{{-- Parent: dashboard.blade.php --}}
<div>
    <h1>Dashboard</h1>
    <livewire:todos />
</div>
```

### Passing Props to Children

```blade
{{-- Parent --}}
<livewire:todo-count :todos="$this->todos" />

<!-- Or with shortened syntax -->
<livewire:todo-count :$todos />

<!-- Static props -->
<livewire:todo-count label="Todo Count:" />

<!-- Boolean props -->
<livewire:todo-count :$todos inline />
```

```php
// Child - receiving props
public $todos;

public function mount($todos)
{
    $this->todos = $todos;
}

// Or auto-matched (no mount needed)
public $todos;
```

### Rendering Children in Loops

```blade
@foreach ($todos as $todo)
    <livewire:todo-item :$todo :wire:key="$todo->id" />
@endforeach
```

### Reactive Props

By default, props are NOT reactive. Use `#[Reactive]` to make child components update when parent data changes.

```php
use Livewire\Attributes\Reactive;

#[Reactive]
public $todos;
```

```blade
{{-- Parent --}}
<livewire:todo-count :$todos />
```

Now when `$todos` changes in the parent, the child automatically updates.

### Modelable (Binding to Child Data)

```php
// Child component
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
{{-- Parent - bind directly to child --}}
<livewire:todo-input wire:model="todo" />
```

### Slots

```blade
{{-- Parent --}}
@foreach ($comments as $comment)
    <livewire:comment :$comment :wire:key="$comment->id">
        <button wire:click="removeComment({{ $comment->id }})">
            Remove
        </button>
    </livewire:comment>
@endforeach
```

```php
// Child - render slot
<div>
    <p>{{ $comment->author }}</p>
    <p>{{ $comment->body }}</p>
    {{ $slot }}
</div>
```

### Named Slots

```blade
{{-- Parent --}}
<livewire:comment :$comment :wire:key="$comment->id">
    <livewire:slot name="actions">
        <button wire:click="removeComment({{ $comment->id }})">
            Remove
        </button>
    </livewire:slot>
    <span>Posted on {{ $comment->created_at }}</span>
</livewire:comment>
```

```blade
{{-- Child --}}
<div>
    <p>{{ $comment->author }}</p>
    <div class="actions">
        {{ $slots['actions'] }}
    </div>
    <div class="metadata">
        {{ $slot }}
    </div>
</div>
```

### Checking Slot Existence

```blade
@if ($slots->has('actions'))
    <div class="actions">
        {{ $slots['actions'] }}
    </div>
@endif
```

### Forwarding Attributes

```blade
{{-- Parent --}}
<livewire:comment :$comment class="border-b" />
```

```blade
{{-- Child --}}
<div {{ $attributes->class('bg-white rounded-md') }}>
    <p>{{ $comment->body }}</p>
</div>
```

### Dynamic Components

```blade
<livewire:dynamic-component :is="$current" :wire:key="$current" />

<!-- Or alternative syntax -->
<livewire:is :component="$current" :wire:key="$current" />
```

### Recursive Components

```blade
@foreach ($subQuestions as $subQuestion)
    <livewire:survey-question :question="$subQuestion" :wire:key="$subQuestion->id" />
@endforeach
```

### Parent Access from Child

```blade
{{-- Direct parent method call --}}
<button wire:click="$parent.remove({{ $id }})">Remove</button>
```

### Islands vs Nested Components

**Use Islands when:**
- You need performance optimization without overhead
- You want to defer or lazy load content
- You have multiple independent UI regions
- The isolated region doesn't need its own lifecycle

**Use Nested Components when:**
- You need reusable, self-contained functionality
- You need separate lifecycle hooks
- You need encapsulated state and logic
- Building a component library

## Events

### Dispatching Events

```php
// From PHP
$this->dispatch('post-created');

// With data
$this->dispatch('post-created', title: $post->title);

// Direct to specific component
$this->dispatch('post-created')->to(Dashboard::class);

// Only to self
$this->dispatch('post-created')->to(self: true);
```

### Listening for Events

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
```

### Listening from Blade

```blade
<livewire:post-list @post-created="$refresh" />

<!-- With parameters -->
<livewire:edit-post @saved="close($event.detail.postId)">

<!-- Call specific method -->
<livewire:edit-post @saved="handleSave">
```

### Dispatching from Blade (Client-Side)

```blade
<button wire:click="$dispatch('post-created', { id: {{ $post->id }} })">
    Edit Post
</button>

<!-- Direct to component -->
<button wire:click="$dispatchTo('posts', 'show-post-modal', { id: {{ $post->id }} })">
    Edit Post
</button>
```

### Alpine Event Integration

```blade
<!-- Listen in Alpine -->
<div x-on:post-created="notify('New post: ' + $event.detail.title)"></div>

<!-- Dispatch from Alpine -->
<button x-on:click="$dispatch('post-created', { title: 'Post Title' })">
    Dispatch
</button>
```

### Real-Time Events with Laravel Echo

```php
// Listening for broadcasted events
#[On('echo:orders,OrderShipped')]
public function notifyNewOrder()
{
    $this->showNotification = true;
}

// With dynamic channel
#[On('echo:orders.{order.id},OrderShipped')]
public function notifyNewOrder($event)
{
    $order = Order::find($event['orderId']);
}

// With custom broadcast name
#[On('echo:scores,.score.submitted')]
public function handleScoreSubmitted($event)
{
    $this->scores[] = $event['score'];
}
```

### Event Listeners via getListeners()

```php
public function getListeners()
{
    return [
        "echo:orders.{$this->order->id},OrderShipped" => 'notifyShipped',
        // Private channel
        "echo-private:orders,OrderShipped" => 'notifyNewOrder',
        // Presence channels
        "echo-presence:orders,OrderShipped" => 'notifyNewOrder',
        "echo-presence:orders,here" => 'userHere',
        "echo-presence:orders,joining" => 'userJoining',
        "echo-presence:orders,leaving" => 'userLeaving',
    ];
}
```

### Testing Events

```php
test('it dispatches post created event', function () {
    Livewire::test(CreatePost::class)
        ->call('save')
        ->assertDispatched('post-created');
});

test('it updates post count when a post is created', function () {
    Livewire::test(Dashboard::class)
        ->assertSee('Posts created: 0')
        ->dispatch('post-created')
        ->assertSee('Posts created: 1');
});
```

## Computed Properties

### Basic Computed Property

```php
use Livewire\Attributes\Computed;

#[Computed]
public function posts()
{
    return Post::all(); // Runs once per request
}
```

```blade
{{-- Must use $this to access --}}
@foreach ($this->posts as $post)
    <div wire:key="{{ $post->id }}">{{ $post->title }}</div>
@endforeach
```

### Persistent Computed Property

```php
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
```

### Global Cache

```php
#[Computed(cache: true)]
public function posts()
{
    return Post::all();
}

// With custom key
#[Computed(cache: true, key: 'homepage-posts')]
public function posts()
{
    return Post::all();
}
```

### Clearing Memo

```php
public function createPost()
{
    // ... create post
    unset($this->posts); // Clear the memo
}
```

### When to Use Computed Properties

1. **Conditionally accessing values** - Avoid queries if data isn't displayed
2. **Using inline templates** - When render() returns inline HTML
3. **Omitting render method** - When you don't have a render() method

## Lazy Loading

### Basic Lazy Component

```blade
<livewire:revenue-chart lazy />
```

```php
use Livewire\Attributes\Lazy;

#[Lazy]
class RevenueChart extends Component
{
    public function placeholder()
    {
        return view('livewire.placeholders.skeleton');
    }
}
```

### Placeholder Template

```blade
{{-- resources/views/livewire/placeholders/skeleton.blade.php --}}
<div class="animate-pulse">
    <div class="h-4 bg-gray-200 rounded w-3/4"></div>
    <div class="h-4 bg-gray-200 rounded mt-2"></div>
    <div class="h-4 bg-gray-200 rounded w-5/6 mt-2"></div>
</div>
```

### Lazy with Custom Placeholder

```blade
<livewire:revenue-chart lazy>
    <div class="loading-spinner">Loading chart...</div>
</livewire:revenue-chart>
```

## Pagination

### Basic Pagination

```php
use Livewire\WithPagination;

class ShowPosts extends Component
{
    use WithPagination;

    public function render()
    {
        return view('livewire.show-posts', [
            'posts' => Post::paginate(10),
        ]);
    }
}
```

```blade
@foreach ($posts as $post)
    <div wire:key="{{ $post->id }}">{{ $post->title }}</div>
@endforeach

{{ $posts->links() }}
```

### Pagination with Query Parameters

```blade
@foreach ($posts as $post)
    ...
@endforeach

{{ $posts->appends(['search' => $search])->links() }}
```

### Simple Pagination

```php
public function render()
{
    return view('livewire.show-posts', [
        'posts' => Post::simplePaginate(10),
    ]);
}
```

```blade
{{ $posts->links() }}
```

### Cursor Pagination

```php
public function render()
{
    return view('livewire.show-posts', [
        'posts' => Post::cursorPaginate(10),
    ]);
}
```

## Polling

### Basic Polling

```blade
<div wire:poll>{{ $count }}</div>
```

### Custom Interval

```blade
<div wire:poll.15s>{{ $count }}</div>
```

### Poll When Visible

```blade
<div wire:poll.visible>{{ $count }}</div>
```

### Keep Polling in Background

```blade
<div wire:poll.keep-alive>{{ $count }}</div>
```

## URL Parameters

### Basic URL Binding

```php
use Livewire\Attributes\Url;

#[Url]
public $search = '';

#[Url]
public $category = '';
```

```blade
<input type="text" wire:model="search">
<select wire:model="category">
    <option value="">All Categories</option>
    <option value="news">News</option>
    <option value="sports">Sports</option>
</select>
```

Now URL updates automatically: `?search=foo&category=news`

### URL with History

```php
#[Url(history: true)]
public $search = '';
```

### URL Except

```php
#[Url(except: '')]
public $search = '';
```

## Session Properties

### Basic Session Property

```php
use Livewire\Attributes\Session;

#[Session]
public $search = '';
```

Persists across page refreshes without using URL.

### Multiple Session Properties

```php
#[Session]
public $search = '';

#[Session]
public $filters = [];
```

## Redirecting

### Basic Redirect

```php
return $this->redirect('/posts');
```

### Named Route

```php
return $this->redirectRoute('posts.show', ['id' => $post->id]);
```

### Back to Previous Page

```php
return $this->redirectBack();
```

## Downloads

### File Download

```php
use Symfony\Component\HttpFoundation\StreamedResponse;

public function download()
{
    return response()->download(storage_path('app/file.pdf'));
}

// Or with Livewire's download method
public function export()
{
    return $this->download(
        file_path: storage_path('app/exports.csv'),
        file_name: 'export.csv'
    );
}
```

## Teleport

### Teleport Content to DOM Element

```blade
<div id="modal-container"></div>

<div>
    <button wire:click="showModal">Show Modal</button>

    <livewire:teleport target="#modal-container">
        @if($showModal)
            <div class="modal">
                Modal Content
            </div>
        @endif
    </livewire:teleport>
</div>
```

## Islands (Isolated Updates)

### Basic Island

```blade
@island
    <div>
        Revenue: {{ $this->expensiveRevenue }}
        <button wire:click="$refresh">Refresh</button>
    </div>
@endisland
```

### Named Island

```blade
@island(name: 'stats')
    <div>Stats: {{ $this->stats }}</div>
@endisland
```

### Lazy Island

```blade
@island(lazy: true)
    <div>{{ $this->slowApiCall }}</div>
@endisland
```

## Performance Best Practices

1. **Use computed properties** for expensive queries
2. **Lazy load** components below the fold
3. **Use `.blur` instead of `.live`** when real-time isn't needed
4. **Avoid storing large Eloquent collections** as properties
5. **Use `wire:key`** for list items to prevent DOM thrashing
6. **Debounce live updates** for better performance
7. **Cache expensive operations** with `#[Computed(persist: true)]`
8. **Use `#[Locked]`** for sensitive IDs to reduce data transfer
