export const setupTweetCharacterCounters = () => {
    document.querySelectorAll('[data-tweet-character-counter]').forEach((counter) => {
        if (counter.dataset.initialized === 'true') {
            return;
        }

        counter.dataset.initialized = 'true';

        const textarea = counter.querySelector('[data-tweet-input]');
        const highlight = counter.querySelector('[data-tweet-highlight]');
        const remaining = counter.querySelector('[data-tweet-remaining]');
        const form = counter.closest('form');
        const submit = form?.querySelector('[data-tweet-submit]');
        const maxLength = Number(counter.dataset.tweetMaxLength || 0);

        if (!textarea || !highlight || !remaining || !maxLength) {
            return;
        }

        const escapeHtml = (value) => value
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');

        const renderHighlight = (value) => {
            const characters = Array.from(value);
            const allowedText = characters.slice(0, maxLength).join('');
            const overText = characters.slice(maxLength).join('');
            const trailingBreak = value.endsWith('\n') ? '\n' : '';

            highlight.innerHTML = `${escapeHtml(allowedText)}<mark>${escapeHtml(overText)}</mark>${trailingBreak}`;
            highlight.scrollTop = textarea.scrollTop;
            highlight.scrollLeft = textarea.scrollLeft;
        };

        const syncHeight = () => {
            highlight.style.height = `${textarea.clientHeight}px`;
        };

        const update = () => {
            const length = Array.from(textarea.value).length;
            const remainingCount = maxLength - length;
            const isInvalid = length === 0 || remainingCount < 0;

            remaining.textContent = `${remainingCount}文字`;
            remaining.classList.toggle('text-red-600', remainingCount < 0);
            remaining.classList.toggle('dark:text-red-300', remainingCount < 0);
            remaining.classList.toggle('text-gray-500', remainingCount >= 0);
            remaining.classList.toggle('dark:text-gray-400', remainingCount >= 0);
            textarea.setAttribute('aria-invalid', remainingCount < 0 ? 'true' : 'false');

            if (submit) {
                submit.dataset.tweetTextInvalid = isInvalid ? 'true' : 'false';
                window.updateTweetSubmitState?.(submit);
            }

            renderHighlight(textarea.value);
            syncHeight();
        };

        textarea.addEventListener('input', update);
        textarea.addEventListener('scroll', () => {
            highlight.scrollTop = textarea.scrollTop;
            highlight.scrollLeft = textarea.scrollLeft;
        });
        window.addEventListener('resize', syncHeight);

        if ('ResizeObserver' in window) {
            const observer = new ResizeObserver(syncHeight);
            observer.observe(textarea);
        }

        update();
    });
};
