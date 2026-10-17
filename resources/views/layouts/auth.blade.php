<!DOCTYPE html>
<html lang="id" data-theme="light" data-brand="serangkab">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">

        <title>{{ $title ?? config('app.name', 'Laravel') }}</title>

        @include('layouts.partials.head-meta')

        {{-- Anti-FOUC: terapkan tema tersimpan sebelum paint --}}
        <script>
            document.documentElement.dataset.theme = localStorage.getItem('theme') === 'dark' ? 'dark' : 'light';
        </script>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600" rel="stylesheet" />
        <link href="https://fonts.bunny.net/css?family=caveat:400,500,600" rel="stylesheet" />

        <!-- Styles / Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        @livewireStyles
    </head>
    <body class="min-h-screen bg-background-primary text-content-primary antialiased">
        <main class="flex min-h-screen flex-col md:flex-row">
            {{ $slot }}
        </main>

        @livewireScripts
    </body>
</html>
