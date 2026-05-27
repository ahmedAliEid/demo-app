@extends('layouts.app')

@section('title', 'Page Not Found')

@section('content')
    <div class="demo-notice-error">
        <h1>404 - Page Not Found</h1>
        <p>The page you are looking for does not exist or has been moved.</p>
        <a href="{{ route('contacts.index') }}">Go to Contacts</a>
    </div>
@endsection
