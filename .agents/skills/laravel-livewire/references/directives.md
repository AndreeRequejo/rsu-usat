# Wire Directives Reference

## wire:model

Two-way data binding between properties and form inputs.

```blade
<input type="text" wire:model="title">
<textarea wire:model="content"></textarea>
<input type="checkbox" wire:model="agree">
<select wire:model="state">...</select>
```

### Modifiers

| Modifier | Description |
|----------|-------------|
| `.live` | Send updates to server as user types |
| `.blur` | Update only when field loses focus |
| `.change` | Update on change event |
| `.enter` | Update when Enter key is pressed |
| `.lazy` | Update on change + network request (v3 compatible) |
| `.debounce.Xms` | Debounce updates (use with .live) |
| `.throttle.Xms` | Throttle updates (use with .live) |
| `.number` | Cast value to int on server |
| `.boolean` | Cast value to bool on server |
| `.fill` | Use initial value from HTML value attribute |
| `.deep` | Listen to events from child elements |
| `.preserve-scroll` | Maintain scroll position during updates |

### Examples

```blade
<!-- Real-time validation -->
<input type="email" wire:model.live="email">

<!-- Update on blur -->
<input type="text" wire:model.blur="title">

<!-- Custom debounce -->
<input type="text" wire:model.live.debounce.500ms="search">

<!-- Throttle -->
<input type="text" wire:model.live.throttle.150ms="title">

<!-- Number input -->
<input type="number" wire:model.number="count">

<!-- Boolean checkbox -->
<input type="checkbox" wire:model.boolean="isActive">

<!-- Maintain scroll -->
<select wire:model.live.preserve-scroll="category">...</select>
```

## wire:click

Call component method when element is clicked.

```blade
<button wire:click="save">Save</button>
<button wire:click="delete({{ $post->id }})">Delete</button>
```

### Modifiers

| Modifier | Description |
|----------|-------------|
| `.prevent` | Call preventDefault() |
| `.stop` | Call stopPropagation() |
| `.window` | Listen on window object |
| `.outside` | Only listen for clicks outside element |
| `.document` | Listen on document object |
| `.once` | Only trigger once |
| `.debounce` | Debounce by 250ms |
| `.debounce.Xms` | Debounce for X milliseconds |
| `.throttle` | Throttle to every 250ms |
| `.throttle.Xms` | Throttle to every X milliseconds |
| `.self` | Only call if event originated on this element |
| `.camel` | Convert event name to camel case |
| `.dot` | Convert event name to dot notation |
| `.passive` | Don't block scroll performance |
| `.capture` | Listen in capturing phase |
| `.async` | Execute action in parallel |
| `.renderless` | Skip re-render after action |

### Examples

```blade
<button wire:click.prevent="save">Save</button>
<button wire:click.stop="save">Save</button>
<button wire:click.once="submit">Submit</button>
<button wire:click.outside="closeModal">Close</button>
<button wire:click.debounce.500ms="search">Search</button>
<button wire:click.async="logActivity">Track</button>
<button wire:click.renderless="incrementViewCount">Count</button>
```

## wire:submit

Handle form submission.

```blade
<form wire:submit="save">
    <input type="text" wire:model="title">
    <button type="submit">Save</button>
</form>
```

### Modifiers

Same as `wire:click` modifiers.

```blade
<form wire:submit.prevent="save">
<form wire:submit.once="save">
```

## wire:keydown

Listen for keyboard events.

```blade
<input wire:keydown.enter="search">
<input wire:keydown.escape="cancel">
```

### Key Modifiers

| Modifier | Key |
|----------|-----|
| `.shift` | Shift |
| `.enter` | Enter |
| `.space` | Space |
| `.ctrl` | Ctrl |
| `.cmd` | Cmd (Mac) / Windows (Windows) |
| `.meta` | Cmd on Mac, Windows key on Windows |
| `.alt` | Alt |
| `.up` | Up arrow |
| `.down` | Down arrow |
| `.left` | Left arrow |
| `.right` | Right arrow |
| `.escape` | Escape |
| `.tab` | Tab |
| `.caps-lock` | Caps Lock |
| `.equal` | Equal (=) |
| `.period` | Period (.) |
| `.slash` | Forward Slash (/) |

### Examples

```blade
<input wire:keydown.enter="search">
<input wire:keydown.shift.enter="...">
<input wire:keydown.escape="cancel">
```

## wire:init

Run code when component initializes.

```blade
<div wire:init="fetchData">...</div>
```

```php
public function fetchData()
{
    $this->data = SomeModel::all();
}
```

## wire:loading

Show content when network request is in progress.

```blade
<button wire:click="save">
    Save
    <span wire:loading>Saving...</span>
</button>

<!-- Target specific action -->
<div wire:loading wire:target="removePhoto">Removing...</div>

<!-- Target multiple properties -->
<div wire:loading wire:target="save,delete">Loading...</div>
```

### CSS Attribute

```blade
<!-- Automatic data-loading attribute -->
<button class="data-loading:opacity-50">Save</button>

<!-- Using Tailwind -->
<div>
    <input type="file" wire:model="photo">
    <div class="not-data-loading:hidden">Uploading...</div>
</div>
```

## wire:dirty

Show/hide content when input value differs from server value.

```blade
<input type="text" wire:model.live.blur="title" wire:dirty.class="border-yellow">

<!-- Toggle visibility -->
<div wire:dirty wire:target="title">Unsaved...</div>
```

## wire:offline

Show content when browser is offline.

```blade
<div wire:offline>
    You are currently offline. Changes will be saved when you reconnect.
</div>
```

## wire:navigate

Use for SPA-like navigation without full page reload.

```blade
<a wire:navigate href="/posts">Posts</a>
```

### Modifiers

```blade
<!-- Navigate with transition -->
<a wire:navigate.hover href="/posts">Posts</a>

<!-- Navigate only if same origin -->
<a wire:navigate href="/posts">Posts</a>
```

## wire:poll

Poll server at intervals.

```blade
<div wire:poll>{{ $count }}</div>
<div wire:poll.15s>{{ $count }}</div>
<div wire:poll.visible>{{ $count }}</div>
<div wire:poll.keep-alive>{{ $count }}</div>
```

## wire:stream

Stream content to browser.

```php
public function streamContent()
{
    $this->stream(
        content: 'Hello ',
        replace: false,
    );
    $this->stream(
        content: 'World!',
        replace: false,
    );
}
```

## wire:transition

Apply transitions when element updates.

```blade
<div wire:transition.duration.500ms>
    {{ $content }}
</div>
```

## wire:intersect

Trigger action when element enters viewport.

```blade
<div wire:intersect="incrementViewCount">
    {{ $post->content }}
</div>
```

## wire:ignore

Prevent Livewire from tracking element changes.

```blade
<div wire:ignore>
    <!-- Third-party library content here -->
</div>
```

## wire:ignore.self

Ignore changes to this element but track children.

```blade
<div wire:ignore.self>
    <input type="text" wire:model="childProperty">
</div>
```

## wire:cloak

Hide element until Livewire initializes (prevent flash).

```blade
<div wire:cloak>
    {{ $content }}
</div>

<!-- Or with CSS -->
<style>
    [wire\:cloak] { display: none; }
</style>
```

## wire:confirm

Show confirmation before action.

```blade
<button wire:click="delete" wire:confirm="Are you sure?">
    Delete
</button>
```

## wire:current

Track the current element for use with `$event.target`.

```blade
<input wire:current="searchInput">
<button wire:click="doSearch">Search</button>
```

## wire:bind

Bind arbitrary HTML attributes to properties.

```blade
<div wire:bind.class="isActive">
    <!-- Classes will reflect $isActive -->
</div>
```

## wire:ref

Store element reference for use in JavaScript.

```blade
<input wire:ref="searchInput">

<script>
    this.$wire.searchInput.focus()
</script>
```

## wire:replace

Replace element content entirely on update.

```blade
<div wire:replace>
    {{ $content }}
</div>
```

## wire:show

Conditionally show element (server-side).

```blade
<div wire:show="$showModal">
    Modal content
</div>
```

## wire:sort

Enable drag-and-drop sorting.

```blade
<div wire:sort="items">
    @foreach ($items as $item)
        <div wire:key="{{ $item->id }}">{{ $item->name }}</div>
    @endforeach
</div>
```

## wire:text

Set element text content.

```blade
<span wire:text="$title"></span>
```

## Third-Party Event Listeners

Livewire supports listening for custom events from third-party libraries.

```blade
<!-- Trix editor -->
<trix-editor wire:trix-change="setContent"></trix-editor>

<!-- Custom event -->
<div wire:custom-event="handleCustom">
    <button x-on:click="$dispatch('custom-event')">Trigger</button>
</div>
```

## Action Modifiers Reference

All `wire:*` directives support these modifiers:

| Modifier | Description |
|----------|-------------|
| `.prevent` | Prevent default behavior |
| `.stop` | Stop event propagation |
| `.window` | Listen on window |
| `.outside` | Click outside element |
| `.document` | Listen on document |
| `.once` | Trigger only once |
| `.debounce` | Debounce 250ms |
| `.debounce.Xms` | Debounce X ms |
| `.throttle` | Throttle to every 250ms |
| `.throttle.Xms` | Throttle every X ms |
| `.self` | Only if event.target is this element |
| `.camel` | Convert event to camelCase |
| `.dot` | Convert event to dot notation |
| `.passive` | Passive event listener |
| `.capture` | Use capture phase |
| `.async` | Execute in parallel |
| `.renderless` | Skip re-render |
| `.preserve-scroll` | Maintain scroll position |

## Event Listeners Reference

Livewire supports any browser event by appending it to `wire:`:

```blade
<!-- Standard events -->
<element wire:click="...">
<element wire:submit="...">
<element wire:keydown="...">
<element wire:keyup="...">
<element wire:mouseenter="...">
<element wire:mouseleave="...">
<element wire:focus="...">
<element wire:blur="...">

<!-- Custom events -->
<element wire:trix-change="...">
<element wire:custom-event="...">

<!-- Any browser event -->
<element wire:transitionend="...">
<element wire:input="...">
```
