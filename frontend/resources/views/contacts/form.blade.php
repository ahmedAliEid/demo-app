@extends('layouts.app')

@section('title', $contact ? 'Edit Contact' : 'Add Contact')

@section('content')
    <a href="{{ route('contacts.index') }}" class="demo-back-link">&larr; Back to List</a>

    <h1>{{ $contact ? 'Edit Contact' : 'Add Contact' }}</h1>

    @if($errors->any())
        <div class="demo-notice-error">
            <ul>
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ $action }}" class="demo-contact-form">
        @csrf
        @if($method === 'PUT')
            @method('PUT')
        @endif

        <div>
            <label for="fullName">Full Name <span class="required">*</span></label>
            <input type="text" id="fullName" name="fullName" value="{{ old('fullName', $contact['fullName'] ?? '') }}" required maxlength="255">
            @error('fullName')
                <span class="demo-notice-error">{{ $message }}</span>
            @enderror
        </div>

        <div>
            <label for="email">Email</label>
            <input type="email" id="email" name="email" value="{{ old('email', $contact['email'] ?? '') }}" maxlength="255">
            @error('email')
                <span class="demo-notice-error">{{ $message }}</span>
            @enderror
        </div>

        <div>
            <label for="phone">Phone</label>
            <input type="text" id="phone" name="phone" value="{{ old('phone', $contact['phone'] ?? '') }}" maxlength="255">
            @error('phone')
                <span class="demo-notice-error">{{ $message }}</span>
            @enderror
        </div>

        <div>
            <label for="streetLine1">Street Address <span class="required">*</span></label>
            <input type="text" id="streetLine1" name="streetLine1" value="{{ old('streetLine1', $contact['streetLine1'] ?? '') }}" required maxlength="255">
            @error('streetLine1')
                <span class="demo-notice-error">{{ $message }}</span>
            @enderror
        </div>

        <div>
            <label for="streetLine2">Address Line 2</label>
            <input type="text" id="streetLine2" name="streetLine2" value="{{ old('streetLine2', $contact['streetLine2'] ?? '') }}" maxlength="255">
            @error('streetLine2')
                <span class="demo-notice-error">{{ $message }}</span>
            @enderror
        </div>

        <div>
            <label for="city">City <span class="required">*</span></label>
            <input type="text" id="city" name="city" value="{{ old('city', $contact['city'] ?? '') }}" required maxlength="100">
            @error('city')
                <span class="demo-notice-error">{{ $message }}</span>
            @enderror
        </div>

        <div>
            <label for="country">Country <span class="required">*</span></label>
            <input type="text" id="country" name="country" value="{{ old('country', $contact['country'] ?? '') }}" required maxlength="100">
            @error('country')
                <span class="demo-notice-error">{{ $message }}</span>
            @enderror
        </div>

        <div>
            <button type="submit">{{ $contact ? 'Update' : 'Create' }}</button>
        </div>
    </form>
@endsection
