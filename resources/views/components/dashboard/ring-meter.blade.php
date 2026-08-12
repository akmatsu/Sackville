@props([
    'ratio' => 0,
    'size' => 84,
    'thickness' => 9,
    'trackClass' => 'stroke-zinc-100 dark:stroke-zinc-800',
    'fillClass' => 'stroke-zinc-500 dark:stroke-zinc-400',
])

@php
    $radius = ($size - $thickness) / 2;
    $circumference = 2 * M_PI * $radius;
    $clampedRatio = min(1, max(0, $ratio));
    $offset = $circumference * (1 - $clampedRatio);
@endphp

<div {{ $attributes->class('relative inline-flex shrink-0 items-center justify-center') }}
    style="width: {{ $size }}px; height: {{ $size }}px;">
    <svg width="{{ $size }}" height="{{ $size }}" viewBox="0 0 {{ $size }} {{ $size }}" class="-rotate-90">
        <circle cx="{{ $size / 2 }}" cy="{{ $size / 2 }}" r="{{ $radius }}" fill="none"
            stroke-width="{{ $thickness }}" class="{{ $trackClass }}" />
        @if ($clampedRatio > 0)
            <circle cx="{{ $size / 2 }}" cy="{{ $size / 2 }}" r="{{ $radius }}" fill="none"
                stroke-width="{{ $thickness }}" stroke-linecap="round"
                stroke-dasharray="{{ $circumference }}" stroke-dashoffset="{{ $offset }}"
                class="{{ $fillClass }} transition-[stroke-dashoffset] duration-500 ease-out" />
        @endif
    </svg>
    <div class="absolute inset-0 flex flex-col items-center justify-center text-center">
        {{ $slot }}
    </div>
</div>
