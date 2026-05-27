@extends('layouts.app')

@section('title', 'Contacts')

@section('content')
    <h1>Contacts</h1>

    @if(session('success'))
        <div class="demo-notice-success">{{ session('success') }}</div>
    @endif

    <div class="demo-search-form">
        <form method="GET" action="{{ route('contacts.index') }}">
            <input type="text" name="name" placeholder="Search by name..." value="{{ $filters['name'] ?? '' }}">

            <select name="city" data-autosubmit>
                <option value="">All Cities</option>
                @foreach($filterOptions['cities'] ?? [] as $city)
                    <option value="{{ $city }}" {{ ($filters['city'] ?? '') === $city ? 'selected' : '' }}>
                        {{ $city }}
                    </option>
                @endforeach
            </select>

            <select name="country" data-autosubmit>
                <option value="">All Countries</option>
                @foreach($filterOptions['countries'] ?? [] as $country)
                    <option value="{{ $country }}" {{ ($filters['country'] ?? '') === $country ? 'selected' : '' }}>
                        {{ $country }}
                    </option>
                @endforeach
            </select>

            <button type="submit">Search</button>
        </form>
    </div>

    <div class="demo-add-contact">
        <a href="{{ route('contacts.create') }}" class="demo-btn">Add Contact</a>
    </div>

    <table class="demo-contacts-table">
        <thead>
            <tr>
                <th>Name</th>
                <th>Email</th>
                <th>City</th>
                <th>Country</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($contacts as $contact)
                <tr>
                    <td>
                        <a href="{{ route('contacts.show', $contact['id']) }}">{{ $contact['fullName'] }}</a>
                    </td>
                    <td>{{ $contact['email'] ?? '' }}</td>
                    <td>{{ $contact['city'] ?? '' }}</td>
                    <td>{{ $contact['country'] ?? '' }}</td>
                    <td>
                        <a href="{{ route('contacts.edit', $contact['id']) }}">Edit</a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5">No contacts found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    @if($pages > 1)
        <div class="demo-pagination">
            @for($i = 0; $i < $pages; $i++)
                <a href="{{ route('contacts.index', array_merge(request()->except('page'), ['page' => $i])) }}"
                   class="{{ (request('page', 0) == $i) ? 'active' : '' }}">
                    {{ $i + 1 }}
                </a>
            @endfor
        </div>
    @endif
@endsection
