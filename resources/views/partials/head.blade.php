<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />

<title>
    {{ filled($title ?? null) ? $title.' - '.config('app.name', 'Laravel') : config('app.name', 'Laravel') }}
</title>

<link rel="icon" href="/favicon.ico" sizes="any">
<link rel="icon" href="/logo.svg" type="image/svg+xml">
<link rel="logo" href="/logo.png">

@fonts

@vite(['resources/css/app.css', 'resources/js/app.js'])
<script>
    if (!window.localStorage.getItem('flux.appearance')) {
        window.localStorage.setItem('flux.appearance', 'light');
    }
</script>
@fluxAppearance
