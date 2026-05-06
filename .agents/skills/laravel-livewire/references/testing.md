# Testing Livewire Components

## Basic Testing

### Setup

Livewire testing uses Laravel's test suite. Both Pest and PHPUnit are supported.

### Test a Component

```php
use Livewire\Livewire;

test('component renders', function () {
    Livewire::test(CreatePost::class)
        ->assertStatus(200);
});
```

### Test with Parameters

```php
test('component renders with post', function () {
    $post = Post::factory()->create();

    Livewire::test(EditPost::class, ['post' => $post->id])
        ->assertStatus(200)
        ->assertSee($post->title);
});
```

## Setting Properties

### Set Property Value

```php
test('can set title', function () {
    Livewire::test(CreatePost::class)
        ->set('title', 'New Title')
        ->assertSee('New Title');
});
```

### Set Multiple Properties

```php
test('can set multiple properties', function () {
    Livewire::test(CreatePost::class)
        ->set('title', 'Title')
        ->set('content', 'Content')
        ->assertSee('Title')
        ->assertSee('Content');
});
```

### Set Nested Properties

```php
test('can set form property', function () {
    Livewire::test(CreatePost::class)
        ->set('form.title', 'Title')
        ->assertSee('Title');
});
```

## Calling Actions

### Call Basic Action

```php
test('can save post', function () {
    Livewire::test(CreatePost::class)
        ->set('title', 'Test Post')
        ->call('save')
        ->assertRedirect('/posts');
});
```

### Call with Parameters

```php
test('can delete post', function () {
    $post = Post::factory()->create();

    Livewire::test(PostList::class)
        ->call('delete', $post->id)
        ->assertDontSee($post->title);
});
```

### Call Multiple Actions

```php
test('can chain actions', function () {
    Livewire::test(Counter::class)
        ->call('increment')
        ->call('increment')
        ->call('increment')
        ->assertSee('3');
});
```

## Assertions

### Status Assertions

```php
->assertStatus(200)
->assertRedirect('/posts')
->assertNoRedirect()
```

### Content Assertions

```php
->assertSee('Hello World')
->assertDontSee('Goodbye')
->assertSeeHtml('<h1>Title</h1>')
->assertSeeInOrder(['First', 'Second', 'Third'])
```

### Property Assertions

```php
->assertSet('title', 'Expected Title')
->assertSet('title.count', 5)
->assertCount('posts', 10)
```

### Error Assertions

```php
->assertHasErrors('title')
->assertHasErrors(['title', 'content'])
->assertHasErrors(['email' => ['required', 'email']])
->assertNoErrors()
```

### Event Assertions

```php
->assertDispatched('post-created')
->assertDispatched('post-created', postId: 1)
->assertNotDispatched('post-deleted')
```

### Redirect Assertions

```php
->assertRedirect('/posts')
->assertRedirectRoute('posts.show', ['id' => 1])
```

## Validation Testing

### Test Validation Rules

```php
test('title is required', function () {
    Livewire::test(CreatePost::class)
        ->set('title', '')
        ->call('save')
        ->assertHasErrors('title');
});
```

### Test Multiple Validation Rules

```php
test('title must be at least 5 characters', function () {
    Livewire::test(CreatePost::class)
        ->set('title', 'abc') // Only 3 characters
        ->call('save')
        ->assertHasErrors(['title' => ['min']]);
});
```

### Test Real-Time Validation

```php
test('real-time validation works', function () {
    Livewire::test(CreatePost::class)
        ->set('title', 'ab')
        ->call('updated', 'title', 'ab')
        ->assertHasErrors('title');
});
```

## Testing File Uploads

```php
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('can upload photo', function () {
    Storage::fake('photos');

    $file = UploadedFile::fake()->image('avatar.jpg');

    Livewire::test(UploadPhoto::class)
        ->set('photo', $file)
        ->call('save');

    Storage::disk('photos')->assertExists('avatar.jpg');
});
```

## Testing Events

### Test Event Dispatching

```php
test('dispatches post created event', function () {
    Livewire::test(CreatePost::class)
        ->set('title', 'Test Post')
        ->call('save')
        ->assertDispatched('post-created');
});
```

### Test Event with Data

```php
test('dispatches event with data', function () {
    Livewire::test(CreatePost::class)
        ->call('save')
        ->assertDispatched('post-created', postId: 1);
});
```

### Test Event Listening

```php
test('listens for post created event', function () {
    Livewire::test(Dashboard::class)
        ->assertSee('Posts created: 0')
        ->dispatch('post-created')
        ->assertSee('Posts created: 1');
});
```

## Testing Nested Components

### Test Parent Component

```php
test('parent renders child component', function () {
    Livewire::test(Dashboard::class)
        ->assertSeeLivewire(TodoList::class);
});
```

### Test Component Interaction

```php
test('child can trigger parent update', function () {
    Livewire::test(TodoList::class)
        ->call('addTodo', 'New Todo')
        ->assertSee('New Todo');
});
```

## Testing Form Objects

```php
test('form object validation works', function () {
    Livewire::test(CreatePost::class)
        ->set('form.title', '')
        ->call('save')
        ->assertHasErrors('form.title');
});
```

## Testing Computed Properties

```php
test('computed property returns posts', function () {
    Post::factory()->count(5);

    Livewire::test(PostList::class)
        ->assertSet('posts.count', 5);
});
```

## Testing Authentication/Authorization

```php
test('unauthenticated user cannot delete', function () {
    $post = Post::factory()->create();

    Livewire::test(PostList::class)
        ->call('delete', $post->id)
        ->assertStatus(403);
});
```

## Testing with Factories

```php
use App\Models\User;

test('authenticated user can create post', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(CreatePost::class)
        ->set('title', 'Test Post')
        ->call('save')
        ->assertRedirect('/posts');
});
```

## Testing Pagination

```php
test('pagination works', function () {
    Post::factory()->count(15);

    Livewire::test(PostList::class)
        ->assertSee('posts/1')
        ->call('gotoPage', 2)
        ->assertSee('posts/2');
});
```

## Testing Wire Model

```php
test('wire:model updates property', function () {
    Livewire::test(Search::class)
        ->set('query', 'test')
        ->assertSet('query', 'test');
});
```

## Testing Actions with Modifiers

### Test Async Action

```php
test('async action runs in parallel', function () {
    Livewire::test(Analytics::class)
        ->call('track')
        ->call('track') // Second call runs immediately
        ->assertSet('trackedCount', 2);
});
```

## Testing Conditional Rendering

```php
test('shows content when condition is met', function () {
    Livewire::test(Toggle::class)
        ->call('show')
        ->assertSee('Hidden content');
});

test('hides content when condition is not met', function () {
    Livewire::test(Toggle::class)
        ->assertDontSee('Hidden content');
});
```

## Testing Lifecycle Hooks

### Test Mount Hook

```php
test('mount initializes data', function () {
    $post = Post::factory()->create();

    Livewire::test(EditPost::class, ['post' => $post->id])
        ->assertSet('post.title', $post->title);
});
```

## Testing Alpine Integration

```php
test('alpine component initializes', function () {
    Livewire::test(Counter::class)
        ->assertSee('x-data');
});
```

## Testing Redirects

### Test After Save

```php
test('redirects after save', function () {
    Livewire::test(CreatePost::class)
        ->set('title', 'Test Post')
        ->call('save')
        ->assertRedirect('/posts');
});
```

### Test Named Route Redirect

```php
test('redirects to named route', function () {
    $post = Post::factory()->create();

    Livewire::test(CreatePost::class)
        ->call('update', $post->id)
        ->assertRedirectRoute('posts.show', ['post' => $post->id]);
});
```

## Common Testing Patterns

### CRUD Pattern

```php
test('can create post', function () {
    Livewire::test(CreatePost::class)
        ->set('title', 'Test Post')
        ->set('content', 'Test content')
        ->call('save')
        ->assertRedirect('/posts');
});

test('can read post', function () {
    $post = Post::factory()->create();

    Livewire::test(ShowPost::class, ['post' => $post->id])
        ->assertSee($post->title);
});

test('can update post', function () {
    $post = Post::factory()->create();

    Livewire::test(EditPost::class, ['post' => $post->id])
        ->set('title', 'Updated Title')
        ->call('save')
        ->assertRedirect('/posts');
});

test('can delete post', function () {
    $post = Post::factory()->create();

    Livewire::test(PostList::class)
        ->call('delete', $post->id)
        ->assertDontSee($post->title);
});
```

### Form Validation Pattern

```php
test('validates required fields', function () {
    Livewire::test(CreatePost::class)
        ->call('save')
        ->assertHasErrors(['title', 'content']);
});

test('validates min length', function () {
    Livewire::test(CreatePost::class)
        ->set('title', 'ab')
        ->call('save')
        ->assertHasErrors(['title' => ['min']]);
});
```

### Real-Time Validation Pattern

```php
test('validates on blur', function () {
    Livewire::test(CreatePost::class)
        ->set('title', 'ab')
        ->call('updatedProperty', 'title')
        ->assertHasErrors('title');
});
```

## Testing Best Practices

1. **Test user workflows** - test complete use cases
2. **Test validation** - ensure data integrity
3. **Test authorization** - ensure security
4. **Use factories** - create test data easily
5. **Use assertions** - verify expected behavior
6. **Test edge cases** - empty inputs, invalid data, etc.
7. **Keep tests fast** - avoid unnecessary database operations
8. **Organize tests** - group by feature or functionality
