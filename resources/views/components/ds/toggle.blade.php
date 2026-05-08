@props([
    'name'  => null,
    'label' => null,
    'hint'  => null,
])

<div class="flex items-start justify-between gap-3">
    @if($label || $hint)
        <div class="min-w-0">
            @if($label)<div class="text-sm font-medium text-gray-700">{{ $label }}</div>@endif
            @if($hint)<div class="text-xs text-gray-400 mt-0.5 leading-relaxed">{{ $hint }}</div>@endif
        </div>
    @endif

    <label class="toggle-switch mt-0.5">
        <input type="checkbox"
               @if($name) name="{{ $name }}" id="{{ $name }}" @endif
               {{ $attributes }}>
        <span class="toggle-track"></span>
    </label>
</div>
