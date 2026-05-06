# Forms & Validation

## Form Submission

### Basic Form

```php
<?php
use Livewire\Component;
use App\Models\Post;

new class extends Component {
    public $title = '';
    public $content = '';

    public function save()
    {
        Post::create($this->only(['title', 'content']));
        session()->flash('status', 'Post successfully updated.');
        return $this->redirect('/posts');
    }
};
?>
```

```blade
<form wire:submit="save">
    <input type="text" wire:model="title">
    <textarea wire:model="content"></textarea>
    <button type="submit">Save</button>
</form>
```

### Form with Validation

```php
use Livewire\Attributes\Validate;

new class extends Component {
    #[Validate('required')]
    public $title = '';

    #[Validate('required')]
    public $content = '';

    public function save()
    {
        $this->validate(); // Runs all validation rules
        Post::create($this->only(['title', 'content']));
        return $this->redirect('/posts');
    }
};
```

### Displaying Errors

```blade
<form wire:submit="save">
    <input type="text" wire:model="title">
    @error('title') <span class="error">{{ $message }}</span> @enderror

    <textarea wire:model="content"></textarea>
    @error('content') <span class="error">{{ $message }}</span> @enderror

    <button type="submit">Save</button>
</form>
```

## Validation Attributes

### Basic Validation

```php
use Livewire\Attributes\Validate;

#[Validate('required|min:5')]
public $title = '';

#[Validate('required|email')]
public $email = '';

#[Validate('required|confirmed')]
public $password = '';

#[Validate('required')]
public $password_confirmation = '';
```

### Custom Error Messages

```php
#[Validate('required', message: 'Please provide a post title')]
public $title = '';

#[Validate('required|min:5', message: 'Title must be at least 5 characters')]
public $title = '';

// Multiple rules with different messages
#[Validate('required', message: 'Please enter a title.')]
#[Validate('min:5', message: 'Your title is too short.')]
public $title = '';
```

### Custom Attribute Names

```php
#[Validate('required', as: 'date of birth')]
public $dob = '';
// Error: "The date of birth field is required."
```

### Disable Auto-Validation

```php
#[Validate('required|min:5', onUpdate: false)]
public $title = '';
```

### Array Validation

```php
#[Validate([
    'todos' => 'required',
    'todos.*' => ['required', 'min:3', new Uppercase],
])]
public $todos = [];
```

### Validation with Messages Array

```php
#[Validate([
    'titles' => 'required',
    'titles.*' => 'required|min:5',
], message: [
    'required' => 'The :attribute is missing.',
    'titles.required' => 'The :attribute are missing.',
    'min' => 'The :attribute is too short.',
], attribute: [
    'titles.*' => 'title',
])]
public $titles = [];
```

## Validation Methods

### Using rules() Method

```php
protected function rules()
{
    return [
        'title' => 'required|min:5',
        'content' => 'required|min:10',
    ];
}

public function save()
{
    $this->validate();
    Post::create($this->all());
}
```

### Using Rule Objects

```php
use Illuminate\Validation\Rule;

protected function rules()
{
    return [
        'title' => [
            'required',
            Rule::unique('posts')->ignore($this->post),
        ],
        'email' => ['required', 'email'],
    ];
}
```

### Custom Messages Method

```php
protected function messages()
{
    return [
        'title.required' => 'The title field is required.',
        'content.min' => 'The content must be at least 10 characters.',
    ];
}
```

### Custom Attributes Method

```php
protected function validationAttributes()
{
    return [
        'content' => 'description',
        'dob' => 'date of birth',
    ];
}
```

## Real-Time Validation

### Live Validation

```blade
<input type="text" wire:model.live="title">
@error('title') <span class="error">{{ $message }}</span> @enderror
```

```php
#[Validate('required|min:5')]
public $title = '';
```

### Blur Validation

```blade
<input type="text" wire:model.live.blur="title">
```

### Validate Only Changed Property

```php
public function updated($property)
{
    $this->validateOnly($property);
}
```

### Real-Time Saving

```php
public function updated($name, $value)
{
    $this->post->update([$name => $value]);
}
```

```blade
<input type="text" wire:model.live.blur="title">
```

## Form Objects

### Creating a Form Object

```bash
php artisan livewire:form PostForm
```

### Basic Form Object

```php
namespace App\Livewire\Forms;
use Livewire\Attributes\Validate;
use Livewire\Form;

class PostForm extends Form
{
    #[Validate('required|min:5')]
    public $title = '';

    #[Validate('required|min:5')]
    public $content = '';

    public function store()
    {
        $this->validate();
        Post::create($this->only(['title', 'content']));
    }
}
```

### Using Form Object in Component

```php
use App\Livewire\Forms\PostForm;

new class extends Component {
    public PostForm $form;

    public function save()
    {
        $this->form->store();
        return $this->redirect('/posts');
    }
};
```

```blade
<form wire:submit="save">
    <input type="text" wire:model="form.title">
    @error('form.title') <span class="error">{{ $message }}</span> @enderror

    <textarea wire:model="form.content"></textarea>
    @error('form.content') <span class="error">{{ $message }}</span> @enderror

    <button type="submit">Save</button>
</form>
```

### Form Object for Edit/Update

```php
namespace App\Livewire\Forms;
use Livewire\Attributes\Validate;
use Livewire\Form;
use App\Models\Post;

class PostForm extends Form
{
    public ?Post $post;

    #[Validate('required|min:5')]
    public $title = '';

    #[Validate('required|min:5')]
    public $content = '';

    public function setPost(Post $post)
    {
        $this->post = $post;
        $this->title = $post->title;
        $this->content = $post->content;
    }

    public function store()
    {
        $this->validate();
        Post::create($this->only(['title', 'content']));
    }

    public function update()
    {
        $this->validate();
        $this->post->update($this->only(['title', 'content']));
        $this->reset();
    }
}
```

### Form Object with Rules Method

```php
class PostForm extends Form
{
    public ?Post $post;
    public $title = '';
    public $content = '';

    #[Validate] // Enable real-time validation
    public $title = '';

    protected function rules()
    {
        return [
            'title' => [
                'required',
                Rule::unique('posts')->ignore($this->post),
            ],
            'content' => 'required|min:5',
        ];
    }

    public function update()
    {
        $this->validate();
        $this->post->update($this->all());
        $this->reset();
    }
}
```

## Form State Management

### Resetting Form Fields

```php
// Reset specific fields
$this->reset('title', 'content');

// Reset all fields
$this->reset();

// Reset and retrieve
$value = $this->pull('title');
```

### In Form Object

```php
public function store()
{
    $this->validate();
    Post::create($this->all());
    $this->reset(); // Reset all form fields
}
```

### Dirty State Indicators

```blade
<input type="text" wire:model.live.blur="title" wire:dirty.class="border-yellow">

<!-- Show element when dirty -->
<div wire:dirty wire:target="title">Unsaved...</div>
```

### Loading Indicators

```blade
<button type="submit">
    Save
    <span wire:loading>Saving...</span>
</button>

<!-- Target specific action -->
<div wire:loading wire:target="save">Saving...</div>

<!-- CSS attribute -->
<button class="data-loading:opacity-50">Save</button>

<!-- Toggle content -->
<button type="submit">
    <span class="not-data-loading:hidden">
        <svg>...</svg> <!-- Spinner -->
    </span>
    <span class="data-loading:hidden">Save</span>
</button>
```

## File Uploads

### Basic File Upload

```php
use Livewire\WithFileUploads;
use Livewire\Attributes\Validate;

new class extends Component {
    use WithFileUploads;

    #[Validate('image|max:1024')] // 1MB max
    public $photo;

    public function save()
    {
        $this->photo->store(path: 'photos');
    }
};
```

```blade
<form wire:submit="save">
    @if ($photo)
        <img src="{{ $photo->temporaryUrl() }}">
    @endif
    <input type="file" wire:model="photo">
    @error('photo') <span class="error">{{ $message }}</span> @enderror
    <button type="submit">Save photo</button>
</form>
```

### Storage Options

```php
// Default disk
$this->photo->store(path: 'photos');

// Specific disk
$this->photo->store(path: 'photos', options: 's3');

// Custom filename
$this->photo->storeAs(path: 'photos', name: 'avatar');

// Public visibility
$this->photo->storePublicly(path: 'photos', options: 's3');

// All together
$this->photo->storePubliclyAs(path: 'photos', name: 'avatar', options: 's3');
```

### Multiple File Upload

```php
use Livewire\WithFileUploads;
use Livewire\Attributes\Validate;

new class extends Component {
    use WithFileUploads;

    #[Validate(['photos.*' => 'image|max:1024'])]
    public $photos = [];

    public function save()
    {
        foreach ($this->photos as $photo) {
            $photo->store(path: 'photos');
        }
    }
};
```

```blade
<form wire:submit="save">
    <input type="file" wire:model="photos" multiple>
    @error('photos.*') <span class="error">{{ $message }}</span> @enderror
    <button type="submit">Save photos</button>
</form>
```

### Upload Progress

```blade
<form wire:submit="save">
    <div
        x-data="{ uploading: false, progress: 0 }"
        x-on:livewire-upload-start="uploading = true"
        x-on:livewire-upload-finish="uploading = false"
        x-on:livewire-upload-error="uploading = false"
        x-on:livewire-upload-progress="progress = $event.detail.progress"
    >
        <input type="file" wire:model="photo">
        <div x-show="uploading">
            <progress max="100" :value="progress"></progress>
        </div>
    </div>
</form>
```

### Cancel Upload

```blade
<input type="file" wire:model="photo">
<button type="button" wire:click="$cancelUpload('photo')">Cancel Upload</button>

<!-- Or from Alpine -->
<button x-on:click="$wire.cancelUpload('photo')">Cancel</button>
```

### Direct S3 Upload

```env
# .env
LIVEWIRE_TEMPORARY_FILE_UPLOAD_DISK=s3
```

```bash
php artisan livewire:configure-s3-upload-cleanup
```

### Upload Configuration

```php
// config/livewire.php
'temporary_file_upload' => [
    'rules' => 'file|mimes:png,jpg,pdf|max:102400', // 100MB, only PNG/JPG/PDF
    'middleware' => 'throttle:5,1', // 5 uploads per minute
    'directory' => 'tmp',
],
```

## Testing Forms

### Basic Form Test

```php
use Livewire\Livewire;

test('can create post', function () {
    Livewire::test(CreatePost::class)
        ->set('title', 'Test Post')
        ->set('content', 'Test content')
        ->call('save')
        ->assertRedirect('/posts');
});
```

### Validation Test

```php
test('validation works', function () {
    Livewire::test(CreatePost::class)
        ->set('title', '')
        ->call('save')
        ->assertHasErrors('title');

    Livewire::test(CreatePost::class)
        ->set('title', 'ab')
        ->call('save')
        ->assertHasErrors(['title' => ['min']]);

    Livewire::test(CreatePost::class)
        ->call('save')
        ->assertHasErrors(['title', 'content']);
});
```

### File Upload Test

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

## Form Patterns

### Reusable Input Component

```blade
<!-- resources/views/components/input-text.blade.php -->
@props(['name'])
<input type="text" name="{{ $name }}" {{ $attributes }}>
@error($name) <span class="error">{{ $message }}</span> @enderror
```

```blade
<!-- Usage -->
<form wire:submit="save">
    <x-input-text name="title" wire:model="title" />
    <x-input-text name="content" wire:model="content" />
    <button type="submit">Save</button>
</form>
```

### Custom Input with wire:model

```blade
<!-- resources/views/components/input-counter.blade.php -->
<div x-data="{ count: 0 }" x-modelable="count" {{ $attributes }}>
    <button x-on:click="count--">-</button>
    <span x-text="count"></span>
    <button x-on:click="count++">+</button>
</div>
```

```blade
<x-input-counter wire:model="quantity" />
```

## Advanced Form Features

### Debouncing Input

```blade
<input type="text" wire:model.live.debounce.500ms="search">
```

### Throttling Input

```blade
<input type="text" wire:model.live.throttle.150ms="title">
```

### Custom Input Types

```blade
<!-- Checkboxes -->
<input type="checkbox" wire:model="receiveUpdates">

<!-- Multiple checkboxes -->
<input type="checkbox" value="email" wire:model="updateTypes">
<input type="checkbox" value="sms" wire:model="updateTypes">
<input type="checkbox" value="notification" wire:model="updateTypes">

<!-- Radio buttons -->
<input type="radio" value="yes" wire:model="receiveUpdates">
<input type="radio" value="no" wire:model="receiveUpdates">

<!-- Select dropdown -->
<select wire:model="state">
    <option value="AL">Alabama</option>
    <option value="AK">Alaska</option>
</select>

<!-- Multi-select -->
<select wire:model="states" multiple>
    <option value="AL">Alabama</option>
    <option value="AK">Alaska</option>
</select>

<!-- With placeholder -->
<select wire:model="state">
    <option disabled value="">Select a state...</option>
    @foreach (\App\Models\State::all() as $state)
        <option value="{{ $state->id }}">{{ $state->label }}</option>
    @endforeach
</select>
```

### Dependent Selects

```blade
<select wire:model.live="selectedState">
    @foreach(State::all() as $state)
        <option value="{{ $state->id }}">{{ $state->label }}</option>
    @endforeach
</select>

<select wire:model.live="selectedCity" wire:key="{{ $selectedState }}">
    @foreach(City::whereStateId($selectedState)->get() as $city)
        <option value="{{ $city->id }}">{{ $city->label }}</option>
    @endforeach
</select>
```
