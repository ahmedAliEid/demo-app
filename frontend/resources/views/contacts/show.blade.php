@extends('layouts.app')

@section('title', $contact['fullName'])

@section('content')
    <a href="{{ route('contacts.index') }}" class="demo-back-link">&larr; Back to List</a>

    <h1>{{ $contact['fullName'] }}</h1>

    <div class="demo-contact-detail">
        <dl>
            <dt>Email</dt>
            <dd>{{ $contact['email'] ?? 'N/A' }}</dd>

            <dt>Phone</dt>
            <dd>{{ $contact['phone'] ?? 'N/A' }}</dd>

            <dt>Street Address</dt>
            <dd>{{ $contact['streetLine1'] ?? 'N/A' }}</dd>

            @if(!empty($contact['streetLine2']))
                <dt>Address Line 2</dt>
                <dd>{{ $contact['streetLine2'] }}</dd>
            @endif

            <dt>City</dt>
            <dd>{{ $contact['city'] ?? 'N/A' }}</dd>

            <dt>Country</dt>
            <dd>{{ $contact['country'] ?? 'N/A' }}</dd>
        </dl>

        <a href="{{ route('contacts.edit', $contact['id']) }}" class="demo-btn">Edit</a>
    </div>
@endsection
