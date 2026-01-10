<div>
    @if (isset($title))
        <title>{{ $title }}</title>
    @endif
    <x-layout.guest>
        {{ $slot }}
    </x-layout.guest>
</div>
