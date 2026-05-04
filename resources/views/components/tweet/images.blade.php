@props([
    'images' => []
])

@php
    $visibleImages = collect($images)->filter->existsOnPublicDisk();
    $modalImages = $visibleImages
        ->map(fn ($image) => [
            'src' => $image->publicUrl(),
            'alt' => $image->name,
        ])
        ->values()
        ->all();
@endphp

@if($visibleImages->isNotEmpty())
<div x-data="{}" class="px-2">
    <div class="flex flex-wrap justify-center -mx-2">
        @foreach($visibleImages->values() as $index => $image)
        <div class="w-1/6 px-2 mt-5">
            <div class="bg-gray-400">
                <a @click="$dispatch('img-modal', { images: {{ Illuminate\Support\Js::from($modalImages) }}, index: {{ $index }} })" class="cursor-pointer">
                    <img alt="{{ $image->name }}" class="object-fit w-full" src="{{ $image->publicUrl() }}">
                </a>
            </div>
        </div>
        @endforeach
    </div>
</div>
@endif
