import { reloadWhenSessionExpired } from './session';

export const setupLikeButtons = () => {
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

            if (reloadWhenSessionExpired(response)) {
                return;
            }

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

    document.addEventListener('click', async (e) => {
        const likeBtn = e.target.closest('.like-btn');
        if (!likeBtn) return;

        const tweetId = likeBtn.dataset.tweetId;

        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

            if (!csrfToken) {
                throw new Error('CSRF token meta tag is missing');
            }

            const response = await fetch('/like', {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({ tweet_id: tweetId }),
            });

            if (reloadWhenSessionExpired(response)) {
                return;
            }

            if (!response.ok) {
                throw new Error('Network response was not ok');
            }

            const data = await response.json();
            const tweetItem = likeBtn.closest('li[data-tweet-id]');

            if (tweetItem) {
                updateLikeView(tweetItem, data);
            }
        } catch (error) {
            console.error('Error toggling like:', error);
        }
    });

    window.setInterval(refreshLikeStatuses, 5000);
    document.addEventListener('visibilitychange', () => {
        if (!document.hidden) {
            refreshLikeStatuses();
        }
    });
};
