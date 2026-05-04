const createTweetImageFormHandler = () => ({
    maxImageSize: 200 * 1024,
    targetImageSize: 190 * 1024,
    pendingCompressions: 0,
    shouldSubmitWhenReady: false,
    fields: [],
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
    addField() {
        const i = this.fields.length;
        this.fields.push({
            file: '',
            id: `input-image-${i}`,
            processing: false,
            compressed: false,
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
            lastModified: Date.now(),
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
                quality,
            );
        });
    },
    compressedFileName(fileName) {
        return fileName.replace(/\.[^.]+$/, '') + '.jpg';
    },
});

const createTweetImageModalHandler = () => ({
    imgModal: false,
    images: [],
    currentIndex: 0,
    swipeStartX: 0,
    swipeStartY: 0,
    open(detail) {
        const fallbackImage = detail.imgModalSrc
            ? [{ src: detail.imgModalSrc, alt: detail.imgModalSrc }]
            : [];

        this.images = Array.isArray(detail.images) && detail.images.length ? detail.images : fallbackImage;
        this.currentIndex = Math.min(Math.max(Number(detail.index || 0), 0), Math.max(this.images.length - 1, 0));
        this.imgModal = this.images.length > 0;
    },
    close() {
        this.imgModal = false;
        this.images = [];
        this.currentIndex = 0;
    },
    currentImage() {
        return this.images[this.currentIndex] || { src: '', alt: '' };
    },
    hasMultipleImages() {
        return this.images.length > 1;
    },
    previous() {
        if (!this.imgModal || !this.hasMultipleImages()) {
            return;
        }

        this.currentIndex = (this.currentIndex - 1 + this.images.length) % this.images.length;
    },
    next() {
        if (!this.imgModal || !this.hasMultipleImages()) {
            return;
        }

        this.currentIndex = (this.currentIndex + 1) % this.images.length;
    },
    startSwipe(event) {
        const touch = event.changedTouches[0];

        this.swipeStartX = touch.clientX;
        this.swipeStartY = touch.clientY;
    },
    endSwipe(event) {
        const touch = event.changedTouches[0];
        const diffX = touch.clientX - this.swipeStartX;
        const diffY = touch.clientY - this.swipeStartY;

        if (Math.abs(diffX) < 50 || Math.abs(diffX) < Math.abs(diffY)) {
            return;
        }

        if (diffX > 0) {
            this.previous();
        } else {
            this.next();
        }
    },
});

export const setupAlpineComponents = (Alpine) => {
    window.inputFormHandler = createTweetImageFormHandler;
    window.tweetImageModalHandler = createTweetImageModalHandler;

    Alpine.data('inputFormHandler', createTweetImageFormHandler);
    Alpine.data('tweetImageModalHandler', createTweetImageModalHandler);
};
