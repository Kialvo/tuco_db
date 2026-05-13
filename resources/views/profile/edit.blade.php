@extends('layouts.dashboard')
@section('title', 'Profile')

@section('content')
    <div class="px-6 py-6 max-w-3xl">
        <header class="mb-6">
            <h1 class="text-2xl font-bold text-gray-800">Profile</h1>
            <p class="text-sm text-gray-500 mt-1">Update your account information and password.</p>
        </header>

        <div class="space-y-6">
            <section class="bg-white rounded-xl border border-gray-200 shadow-card p-6">
                @include('profile.partials.update-profile-information-form')
            </section>

            <section class="bg-white rounded-xl border border-gray-200 shadow-card p-6">
                @include('profile.partials.update-password-form')
            </section>

            <section class="bg-white rounded-xl border border-red-200 shadow-card p-6">
                @include('profile.partials.delete-user-form')
            </section>
        </div>
    </div>
@endsection
