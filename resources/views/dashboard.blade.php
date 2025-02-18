<!-- resources/views/dashboard.blade.php -->
@extends('layouts.dashboard')

@section('content')
    <h1 class="text-2xl font-bold mb-4">Welcome to Your Dashboard</h1>

    <p>Hello, {{ Auth::user()->name }}. You are logged in as <strong>{{ Auth::user()->role }}</strong>.</p>
    <p>This page is visible to both Admin and Editor roles.</p>
@endsection
