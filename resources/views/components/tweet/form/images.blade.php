<div x-data="inputFormHandler()" class="my-2">
    <template x-for="(field, i) in fields" :key="i">
        <div class="w-full flex my-2">
            <label :for="field.id" class="border border-gray-300 rounded-md p-2 w-full bg-white cursor-pointer dark:border-gray-700 dark:bg-gray-900">
                <input type="file" accept="image/*" class="sr-only" :id="field.id" name="images[]" @change="handleFileChange($event, i)">
                <span x-text="field.file ? field.file.name : '画像を選択'" class="text-gray-700 dark:text-gray-200"></span>
                <span x-show="field.processing" class="ml-2 text-sm text-gray-500 dark:text-gray-400">圧縮中...</span>
                <span x-show="field.compressed" class="ml-2 text-sm text-green-600 dark:text-green-400">圧縮済み</span>
            </label>
            <button type="reset" @click="removeField(i)" class="p-2">
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
<script>
function inputFormHandler() {
  return {
    maxImageSize: 200 * 1024,
    targetImageSize: 190 * 1024,
    pendingCompressions: 0,
    shouldSubmitWhenReady: false,
    init() {
      this.$nextTick(() => {
        const form = this.$el.closest('form');

        form?.addEventListener('submit', (event) => {
          if (this.pendingCompressions === 0) {
            return;
          }

          event.preventDefault();
          this.shouldSubmitWhenReady = true;
        });
      });
    },
    fields: [],
    addField() {
      const i = this.fields.length;
      this.fields.push({
        file: '',
        id: `input-image-${i}`,
        processing: false,
        compressed: false
      });
    },
    removeField(index) {
      this.fields.splice(index, 1);
    },
    async handleFileChange(event, index) {
      const file = event.target.files[0] || '';
      const field = this.fields[index];

      field.file = file;
      field.processing = false;
      field.compressed = false;

      if (!file || file.size <= this.maxImageSize || !file.type.startsWith('image/')) {
        return;
      }

      field.processing = true;
      this.pendingCompressions += 1;

      try {
        const compressedFile = await this.compressImage(file);
        const dataTransfer = new DataTransfer();

        dataTransfer.items.add(compressedFile);
        event.target.files = dataTransfer.files;
        field.file = compressedFile;
        field.compressed = compressedFile.size < file.size;
      } catch (error) {
        console.error('Image compression failed:', error);
      } finally {
        field.processing = false;
        this.pendingCompressions = Math.max(0, this.pendingCompressions - 1);

        if (this.pendingCompressions === 0 && this.shouldSubmitWhenReady) {
          this.shouldSubmitWhenReady = false;
          this.$el.closest('form')?.requestSubmit();
        }
      }
    },
    async compressImage(file) {
      const image = await this.loadImage(file);
      let width = image.naturalWidth || image.width;
      let height = image.naturalHeight || image.height;
      let blob = await this.compressImageAtSize(image, width, height);

      while (blob.size > this.targetImageSize && Math.max(width, height) > 320) {
        width = Math.round(width * 0.85);
        height = Math.round(height * 0.85);
        blob = await this.compressImageAtSize(image, width, height);
      }

      URL.revokeObjectURL(image.src);

      return new File([blob], this.compressedFileName(file.name), {
        type: blob.type,
        lastModified: Date.now()
      });
    },
    async compressImageAtSize(image, width, height) {
      let quality = 0.86;
      let blob = await this.canvasToBlob(image, width, height, quality);

      while (blob.size > this.targetImageSize && quality > 0.42) {
        quality -= 0.08;
        blob = await this.canvasToBlob(image, width, height, quality);
      }

      return blob;
    },
    loadImage(file) {
      return new Promise((resolve, reject) => {
        const image = new Image();

        image.onload = () => resolve(image);
        image.onerror = reject;
        image.src = URL.createObjectURL(file);
      });
    },
    canvasToBlob(image, width, height, quality) {
      return new Promise((resolve, reject) => {
        const canvas = document.createElement('canvas');
        const context = canvas.getContext('2d');

        canvas.width = width;
        canvas.height = height;
        context.drawImage(image, 0, 0, width, height);
        canvas.toBlob(
          (blob) => blob ? resolve(blob) : reject(new Error('Canvas conversion failed.')),
          'image/jpeg',
          quality
        );
      });
    },
    compressedFileName(fileName) {
      return fileName.replace(/\.[^.]+$/, '') + '.jpg';
    }
  }
}
</script>
