<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Demo App') - Demo App</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
    <div class="demo-contacts-wrap">
        <header>
            <nav>
                <a href="{{ route('contacts.index') }}" class="demo-logo">Demo App</a>
                @auth
                    @if(session('role'))
                        <span class="demo-role-badge">{{ session('role') }}</span>
                    @endif
                    <form action="{{ route('logout') }}" method="POST" style="display:inline">
                        @csrf
                        <button type="submit" class="demo-logout-btn">Logout</button>
                    </form>
                @endauth
            </nav>
        </header>

        <main>
            @yield('content')
        </main>
    </div>
    <script src="{{ asset('js/app.js') }}"></script>
</body>
</html>
