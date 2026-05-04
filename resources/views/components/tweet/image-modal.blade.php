<div x-data="tweetImageModalHandler()">
    <div
        @img-modal.window="open($event.detail)"
        @keydown.escape.window="close()"
        @keydown.arrow-left.window="previous()"
        @keydown.arrow-right.window="next()"
        x-cloak
        x-show="imgModal"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 transform"
        x-transition:enter-end="opacity-100 transform"
        x-transition:leave="transition ease-in duration-300"
        x-transition:leave-start="opacity-100 transform"
        x-transition:leave-end="opacity-0 transform"
        class="p-2 fixed w-full h-100 inset-0 z-50 overflow-hidden flex justify-center items-center bg-black bg-opacity-75"
    >
        <div
            @click.away="close()"
            @touchstart.passive="startSwipe($event)"
            @touchend.passive="endSwipe($event)"
            class="relative flex flex-col max-w-3xl max-h-full overflow-auto"
        >
            <div class="z-50">
                <button @click="close()" class="float-right pt-2 pr-2 outline-none focus:outline-none" aria-label="閉じる">
                    <svg class="fill-current text-white h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                    </svg>
                </button>
            </div>
            <div class="relative p-2">
                <button
                    x-show="hasMultipleImages()"
                    @click.stop="previous()"
                    class="absolute left-2 top-1/2 z-50 -translate-y-1/2 rounded-full bg-black bg-opacity-50 p-2 text-white hover:bg-opacity-70 focus:outline-none"
                    aria-label="前の画像"
                >
                    <svg class="h-6 w-6 fill-current" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M12.707 4.293a1 1 0 010 1.414L8.414 10l4.293 4.293a1 1 0 01-1.414 1.414l-5-5a1 1 0 010-1.414l5-5a1 1 0 011.414 0z" clip-rule="evenodd" />
                    </svg>
                </button>
                <img
                    class="object-contain h-1/2-screen"
                    :alt="currentImage().alt"
                    :src="currentImage().src"
                >
                <button
                    x-show="hasMultipleImages()"
                    @click.stop="next()"
                    class="absolute right-2 top-1/2 z-50 -translate-y-1/2 rounded-full bg-black bg-opacity-50 p-2 text-white hover:bg-opacity-70 focus:outline-none"
                    aria-label="次の画像"
                >
                    <svg class="h-6 w-6 fill-current" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M7.293 15.707a1 1 0 010-1.414L11.586 10 7.293 5.707a1 1 0 011.414-1.414l5 5a1 1 0 010 1.414l-5 5a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                    </svg>
                </button>
            </div>
        </div>
    </div>
</div>
