{{-- Global topbar — internal staff only (admins/editors). Guests get nothing
     (zero height). Holds the notification bell top-right, CRM-style. --}}
@php
    $tbUser = Auth::user();
    $tbIsStaff = $tbUser && in_array($tbUser->role, ['admin', 'editor'], true);
@endphp
@if($tbIsStaff)
    <header class="h-12 bg-white border-b border-gray-200 flex items-center justify-end gap-3 px-6 flex-shrink-0">
        @include('layouts.partials.notification-bell')
        <a href="{{ route('profile.edit') }}" class="flex items-center gap-2 rounded-lg px-1.5 py-1 hover:bg-gray-50" title="My Profile — {{ $tbUser->email }}">
            @if($tbUser->avatar)
                <img src="{{ $tbUser->avatar }}" alt="" class="h-7 w-7 rounded-full object-cover border border-gray-200">
            @else
                <span class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-green-100 text-[11px] font-bold text-green-700">
                    {{ strtoupper(collect(explode(' ', $tbUser->name))->map(fn($p) => mb_substr($p, 0, 1))->take(2)->implode('')) }}
                </span>
            @endif
            <span class="hidden md:block text-xs font-medium text-gray-600 max-w-[140px] truncate">{{ $tbUser->name }}</span>
        </a>
    </header>
@endif
