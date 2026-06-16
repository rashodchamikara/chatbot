<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
        <div class="min-h-screen bg-gray-100 dark:bg-gray-900">
            @include('layouts.navigation')

            <!-- Page Heading -->
            @isset($header)
                <header class="bg-white dark:bg-gray-800 shadow">
                    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endisset

            <!-- Page Content -->
            <main>
                {{ $slot }}
            </main>
        </div>
        <meta name="csrf-token" content="{{ csrf_token() }}">
        @auth
        <script>
        document.addEventListener('DOMContentLoaded', function () {
            const csrfToken = document
                .querySelector('meta[name="csrf-token"]')
                ?.getAttribute('content');

            fetch("{{ route('admin.agent.online') }}", {
                method: 'POST',
                credentials: 'same-origin',
                keepalive: true,
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({})
            })
            .then(async response => {
                const data = await response.json().catch(() => ({}));

                if (!response.ok) {
                    console.error(
                        'Could not mark agent online:',
                        response.status,
                        data
                    );

                    return;
                }

                console.log('Agent presence updated:', data);
            })
            .catch(error => {
                console.error(
                    'Agent online request failed:',
                    error
                );
            });
        });
        </script>
        @endauth
    </body>
</html>
