<div x-data="inputFormHandler()" class="my-2">
    <template x-for="(field, i) in fields" :key="i">
        <div class="w-full flex items-start gap-2 my-2">
            <label :for="field.id" class="border border-gray-300 rounded-md p-2 w-full bg-white cursor-pointer dark:border-gray-700 dark:bg-gray-900">
                <input type="file" accept="image/*" class="sr-only" :id="field.id" name="images[]" @change="handleFileChange($event, i)">
                <span x-show="!field.file" class="text-gray-700 dark:text-gray-200">画像を選択</span>
                <span x-show="field.processing" class="ml-2 text-sm text-gray-500 dark:text-gray-400">圧縮中...</span>
                <span x-show="field.compressed" class="ml-2 text-sm text-green-600 dark:text-green-400">圧縮済み</span>
                <div x-show="field.previewUrl" class="mt-2" x-cloak>
                    <img :src="field.previewUrl" :alt="field.file ? field.file.name : '選択した画像'" class="h-24 w-24 rounded object-cover">
                </div>
            </label>
            <button type="button" @click="removeField(i)" class="p-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-red-500 hover:text-red-700" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                </svg>
            </button>
        </div>
    </template>

    <template x-if="fields.length < 4">
        <button type="button" @click="addField()" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-gray-500 hover:bg-gray-600">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm12 12H4l4-8 3 6 2-4 3 6z" clip-rule="evenodd" />
            </svg>
            <span>画像を追加</span>
        </button>
    </template>
</div>
