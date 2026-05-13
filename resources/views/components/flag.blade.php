@props([
    'country' => null,
    'iso'     => null,            // override: pass the ISO code directly (faster for tables)
    'width'   => 20,
    'height'  => 15,
])

@php
    $code = $iso ?: \App\Support\CountryCode::iso($country);
@endphp

@if($code)
    <img src="https://flagcdn.com/{{ $width * 2 }}x{{ $height * 2 }}/{{ $code }}.png"
         srcset="https://flagcdn.com/{{ $width * 4 }}x{{ $height * 4 }}/{{ $code }}.png 2x"
         width="{{ $width }}" height="{{ $height }}"
         alt="{{ $country ?? strtoupper($code) }}"
         loading="lazy"
         {{ $attributes->merge(['class' => 'inline-block rounded-sm border border-gray-200 align-middle']) }} />
@else
    <span {{ $attributes->merge(['class' => 'inline-block w-5 h-[15px] rounded-sm bg-gray-100 align-middle']) }} aria-hidden="true"></span>
@endif
