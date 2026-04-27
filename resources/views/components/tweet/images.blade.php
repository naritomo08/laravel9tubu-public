@props([
    'images' => []
])

@php
    $visibleImages = collect($images)->filter->existsOnPublicDisk();
@endphp

@if($visibleImages->isNotEmpty())
<div x-data="{}" class="px-2">
    <div class="flex flex-wrap justify-center -mx-2">
        @foreach($visibleImages as $image)
        <div class="w-1/6 px-2 mt-5">
            <div class="bg-gray-400">
                <a @click="$dispatch('img-modal', {  imgModalSrc: '{{ $image->publicUrl() }}' })" class="cursor-pointer">
                    <img alt="{{ $image->name }}" class="object-fit w-full" src="{{ $image->publicUrl() }}">
                </a>
            </div>
        </div>
        @endforeach
    </div>
</div>
@endif
