(() => {
    if (window.__tubuyakiLikeButtonsSetup) {
        return;
    }

    window.__tubuyakiLikeButtonsSetup = true;

    const updateLikeView = (tweetItem, data) => {
        const likeButton = tweetItem.querySelector('.like-btn');
        const icon = tweetItem.querySelector('svg');
        const count = tweetItem.querySelector('.like-count');

        if (count) {
            count.textContent = data.like_count;
        }

        if (!likeButton) {
            return;
        }

        likeButton.dataset.isLiked = data.is_liked ? 'true' : 'false';

        if (data.is_liked) {
            icon?.setAttribute('fill', 'currentColor');
            likeButton.classList.remove('text-gray-400');
            likeButton.classList.add('text-red-500');
        } else {
            icon?.setAttribute('fill', 'none');
            likeButton.classList.remove('text-red-500');
            likeButton.classList.add('text-gray-400');
        }
    };

    const refreshLikeStatuses = async () => {
        const list = document.querySelector('[data-tweet-list]');

        if (!list?.dataset.likeStatusUrl) {
            return;
        }

        const tweetItems = Array.from(document.querySelectorAll('li[data-tweet-id]'))
            .slice(0, 100);
        const tweetIds = tweetItems
            .map((item) => item.dataset.tweetId)
            .filter(Boolean);

        if (tweetIds.length === 0) {
            return;
        }

        try {
            const url = new URL(list.dataset.likeStatusUrl, window.location.origin);
            url.searchParams.set('tweet_ids', [...new Set(tweetIds)].join(','));

            const response = await fetch(url.toString(), {
                headers: {
                    'Accept': 'application/json',
                },
            });

            if (!response.ok) {
                return;
            }

            const data = await response.json();
            const statuses = data.likes;

            if (!statuses) {
                return;
            }

            tweetItems.forEach((tweetItem) => {
                const hasStatus = Object.prototype.hasOwnProperty.call(statuses, tweetItem.dataset.tweetId);

                if (!hasStatus) {
                    tweetItem.remove();
                    return;
                }

                updateLikeView(tweetItem, statuses[tweetItem.dataset.tweetId]);
            });
        } catch (error) {
            console.error('Error refreshing likes:', error);
        }
    };

    document.addEventListener('click', async (event) => {
        const likeButton = event.target.closest('.like-btn');

        if (!likeButton) {
            return;
        }

        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

        if (!csrfToken) {
            console.error('Error toggling like: CSRF token meta tag is missing');
            return;
        }

        likeButton.disabled = true;

        try {
            const response = await fetch('/like', {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify({ tweet_id: likeButton.dataset.tweetId }),
            });

            if (!response.ok) {
                throw new Error(`Network response was not ok: ${response.status}`);
            }

            const data = await response.json();
            const tweetItem = likeButton.closest('li[data-tweet-id]');

            if (tweetItem) {
                updateLikeView(tweetItem, data);
            }
        } catch (error) {
            console.error('Error toggling like:', error);
        } finally {
            likeButton.disabled = false;
        }
    });

    window.setInterval(refreshLikeStatuses, 5000);
    document.addEventListener('visibilitychange', () => {
        if (!document.hidden) {
            refreshLikeStatuses();
        }
    });
})();
