import './bootstrap';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.start();

const setupTweetAutoRefresh = () => {
    if (window.tweetAutoRefreshStarted) {
        return;
    }

    const list = document.querySelector('[data-tweet-list]');

    if (!list) {
        return;
    }

    if (list.dataset.autoRefreshEnabled === 'false') {
        window.tweetAutoRefreshStarted = true;
        return;
    }

    window.tweetAutoRefreshStarted = true;

    const items = list.querySelector('[data-tweet-list-items]');
    const latestUrl = list.dataset.latestUrl;
    let latestTweetId = Number(list.dataset.latestTweetId || 0);
    let loading = false;

    const getTweetVersions = () => Object.fromEntries(
        Array.from(items.querySelectorAll('li[data-tweet-id][data-tweet-updated-at]'))
            .slice(0, 100)
            .map((item) => [item.dataset.tweetId, item.dataset.tweetUpdatedAt])
    );

    const getTweetSortValue = (item) => ({
        updatedAt: Date.parse(item.dataset.tweetUpdatedAt || '') || 0,
        id: Number(item.dataset.tweetId || 0),
    });

    const shouldInsertBefore = (tweetItem, currentItem) => {
        const tweet = getTweetSortValue(tweetItem);
        const current = getTweetSortValue(currentItem);

        if (tweet.updatedAt !== current.updatedAt) {
            return tweet.updatedAt > current.updatedAt;
        }

        return tweet.id > current.id;
    };

    const insertTweetItem = (tweetItem) => {
        const existingItem = items.querySelector(`li[data-tweet-id="${tweetItem.dataset.tweetId}"]`);

        if (existingItem && existingItem !== tweetItem) {
            existingItem.remove();
        }

        const referenceItem = Array.from(items.querySelectorAll('li[data-tweet-id][data-tweet-updated-at]'))
            .find((currentItem) => currentItem !== tweetItem && shouldInsertBefore(tweetItem, currentItem));

        items.insertBefore(tweetItem, referenceItem || null);
    };

    const replaceTweetItem = (tweetId, html) => {
        const currentItem = items.querySelector(`li[data-tweet-id="${tweetId}"]`);

        if (!currentItem) {
            return;
        }

        const template = document.createElement('template');
        template.innerHTML = html.trim();
        const nextItem = template.content.firstElementChild;

        if (!nextItem) {
            return;
        }

        currentItem.remove();
        insertTweetItem(nextItem);
        window.Alpine?.initTree(nextItem);
    };

    const refresh = async () => {
        if (loading) {
            return;
        }

        loading = true;

        try {
            const url = new URL(latestUrl, window.location.origin);
            url.searchParams.set('after_id', latestTweetId);
            url.searchParams.set('tweet_versions', JSON.stringify(getTweetVersions()));

            const response = await fetch(url.toString(), {
                headers: {
                    Accept: 'application/json',
                },
            });

            if (!response.ok) {
                return;
            }

            const data = await response.json();

            if (data.html) {
                const template = document.createElement('template');
                template.innerHTML = data.html.trim();
                const insertedItems = Array.from(template.content.children);

                insertedItems.forEach((item) => {
                    insertTweetItem(item);
                    window.Alpine?.initTree(item);
                });
            }

            Object.entries(data.updated_html || {}).forEach(([tweetId, html]) => {
                replaceTweetItem(tweetId, html);
            });

            if (Number(data.latest_id) > latestTweetId) {
                latestTweetId = Number(data.latest_id);
                list.dataset.latestTweetId = latestTweetId;
            }
        } finally {
            loading = false;
        }
    };

    window.setInterval(refresh, 5000);
    document.addEventListener('visibilitychange', () => {
        if (!document.hidden) {
            refresh();
        }
    });
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', setupTweetAutoRefresh);
} else {
    setupTweetAutoRefresh();
}

// いいねボタンの処理
const setupLikeButtons = () => {
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
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify({ tweet_id: tweetId }),
            });

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

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', setupLikeButtons);
} else {
    setupLikeButtons();
}
