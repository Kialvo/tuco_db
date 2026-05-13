@props([
    'name'  => null,
    'label' => null,
    'tip'   => null,
    'as'    => 'input',     // input | select | textarea
    'type'  => 'text',
    'placeholder' => '',
])

<div>
    @if($label)
        <div class="flex items-center gap-1.5 mb-1.5">
            <label @if($name) for="{{ $name }}" @endif
                   class="text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ $label }}</label>
            @if($tip)
                <span class="tip">
                    <x-icon name="info" size="sm" class="text-gray-400 cursor-help" :stroke="2" />
                    <span class="tip-box">{{ $tip }}</span>
                </span>
            @endif
        </div>
    @endif

    @if($as === 'select')
        <select @if($name) name="{{ $name }}" id="{{ $name }}" @endif
                {{ $attributes->merge(['class' => 'fi']) }}>
            {{ $slot }}
        </select>
    @elseif($as === 'textarea')
        <textarea @if($name) name="{{ $name }}" id="{{ $name }}" @endif
                  placeholder="{{ $placeholder }}"
                  {{ $attributes->merge(['class' => 'fi resize-none leading-relaxed']) }}>{{ $slot }}</textarea>
    @else
        <input type="{{ $type }}"
               @if($name) name="{{ $name }}" id="{{ $name }}" @endif
               placeholder="{{ $placeholder }}"
               {{ $attributes->merge(['class' => 'fi']) }} />
    @endif
</div>
