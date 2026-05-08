@props([
    'name'   => null,        // base name; will produce {name}_min, {name}_max
    'label'  => null,
    'tip'    => null,
    'step'   => null,
])

<div>
    @if($label)
        <div class="flex items-center gap-1.5 mb-1.5">
            <label class="text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ $label }}</label>
            @if($tip)
                <span class="tip">
                    <x-icon name="info" size="sm" class="text-gray-400 cursor-help" />
                    <span class="tip-box">{{ $tip }}</span>
                </span>
            @endif
        </div>
    @endif

    <div class="mpair">
        <input type="number"
               @if($name) name="{{ $name }}_min" id="{{ $name }}_min" @endif
               @if($step) step="{{ $step }}" @endif
               placeholder="Min"
               {{ $attributes->merge(['class' => 'fi']) }} />
        <input type="number"
               @if($name) name="{{ $name }}_max" id="{{ $name }}_max" @endif
               @if($step) step="{{ $step }}" @endif
               placeholder="Max"
               {{ $attributes->merge(['class' => 'fi']) }} />
    </div>
</div>
