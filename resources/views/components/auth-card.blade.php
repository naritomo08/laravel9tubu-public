<div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-gray-100 dark:bg-gray-950">
    <div class="fixed right-4 top-4">
        <x-theme-toggle />
    </div>
    <div>
        {{ $logo }}
    </div>

    <div class="w-full sm:max-w-md mt-6 px-6 py-4 bg-white shadow-md overflow-hidden sm:rounded-lg dark:bg-gray-900 dark:shadow-gray-950/40">
        {{ $slot }}
    </div>
</div>
