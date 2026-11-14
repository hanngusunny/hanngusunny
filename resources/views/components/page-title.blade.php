@props([
    'kicker' => '',
    'icon' => '',
    'title' => '',
    'lead' => '',
])

<header {{ $attributes->class('op-hero') }}>
    <div class="op-hero-top">
        <div>
            @if ($kicker !== '')
                <p class="op-hero-kicker">
                    @if ($icon !== '')
                        <i class="fa-solid {{ $icon }}"></i>
                    @endif
                    {{ $kicker }}
                </p>
            @endif
            <h1>@if ($title !== ''){{ $title }}@else{{ $slot }}@endif</h1>
            @if (trim((string) $lead) !== '')
                <p class="op-hero-lead">{{ $lead }}</p>
            @endif
            @isset($meta)
                <div class="op-hero-meta">{{ $meta }}</div>
            @endisset
        </div>
        @isset($aside)
            <div class="op-hero-aside">{{ $aside }}</div>
        @endisset
    </div>
</header>
