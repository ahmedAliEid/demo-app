@extends('layouts.app')

@section('title', 'Service Unavailable')

@section('content')
    <div class="demo-notice-error">
        <h1>500 - Service Unavailable</h1>
        <p>We are experiencing temporary difficulties. Please try again later.</p>
        <a href="{{ route('contacts.index') }}">Go to Contacts</a>
    </div>
@endsection
