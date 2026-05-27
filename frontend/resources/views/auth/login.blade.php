@extends('layouts.app')

@section('title', 'Login')

@section('content')
    <div class="demo-login-form">
        <h1>Login</h1>

        @if($errors->any())
            <div class="demo-notice-error">
                {{ $errors->first('credentials') }}
            </div>
        @endif

        <form method="POST" action="{{ route('login') }}">
            @csrf

            <div>
                <label for="username">Username</label>
                <input type="text" id="username" name="username" value="{{ old('username') }}" required maxlength="100">
                @error('username')
                    <span class="demo-notice-error">{{ $message }}</span>
                @enderror
            </div>

            <div>
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required maxlength="100">
                @error('password')
                    <span class="demo-notice-error">{{ $message }}</span>
                @enderror
            </div>

            <div>
                <button type="submit">Login</button>
            </div>
        </form>
    </div>
@endsection
