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

    window.tweetAutoRefreshStarted = true;

    const items = list.querySelector('[data-tweet-list-items]');
    const topPagination = document.querySelector('[data-tweet-pagination-top]');
    const bottomPagination = document.querySelector('[data-tweet-pagination-bottom]');
    const latestUrl = list.dataset.latestUrl;
    const indexUrl = list.dataset.indexUrl || '/tweet';
    const currentPage = Number(list.dataset.currentPage || 1);
    let latestTweetId = Number(list.dataset.latestTweetId || 0);
    let loading = false;

    const redirectToFirstPage = () => {
        const nextUrl = new URL(indexUrl, window.location.origin);
        nextUrl.searchParams.set('page', '1');
        window.location.assign(nextUrl.toString());
    };

    const getTweetVersions = () => Object.fromEntries(
        Array.from(items.querySelectorAll('li[data-tweet-id][data-tweet-version]'))
            .slice(0, 100)
            .map((item) => [item.dataset.tweetId, item.dataset.tweetVersion])
    );

    const getSnapshotSignature = () => [
        Array.from(items.querySelectorAll('li[data-tweet-id]'))
            .slice(0, 100)
            .map((item) => item.dataset.tweetId)
            .join(','),
        items.querySelectorAll('li[data-tweet-id]').length,
    ].join('|');

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

    const replaceAllTweetItems = (html) => {
        items.innerHTML = html;
        Array.from(items.children).forEach((item) => {
            window.Alpine?.initTree(item);
        });
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
            url.searchParams.set('include_snapshot', '1');

            const response = await fetch(url.toString(), {
                headers: {
                    Accept: 'application/json',
                },
            });

            if (!response.ok) {
                return;
            }

            const data = await response.json();

            if (list.dataset.autoRefreshEnabled === 'false') {
                if (typeof data.last_page === 'number' && currentPage > data.last_page) {
                    redirectToFirstPage();
                }

                return;
            }

            const needsFullRefresh =
                typeof data.snapshot_signature === 'string' &&
                data.snapshot_signature !== getSnapshotSignature();

            if (needsFullRefresh && typeof data.full_html === 'string') {
                replaceAllTweetItems(data.full_html);
            } else if (data.html) {
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

            if (typeof data.pagination_html === 'string') {
                if (topPagination) {
                    topPagination.innerHTML = data.pagination_html;
                }

                if (bottomPagination) {
                    bottomPagination.innerHTML = data.pagination_html;
                }
            }

            if (typeof data.latest_id === 'number') {
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

const setupEmailVerificationWatch = () => {
    if (window.__tubuyakiEmailVerificationWatchStarted) {
        return;
    }

    const target = document.querySelector('[data-email-verification-watch]');

    if (!target) {
        return;
    }

    window.__tubuyakiEmailVerificationWatchStarted = true;

    const statusUrl = target.dataset.statusUrl;
    const verifiedUrl = target.dataset.verifiedUrl || window.location.href;
    let checking = false;

    const checkVerification = async () => {
        if (checking) {
            return;
        }

        checking = true;

        try {
            const response = await fetch(statusUrl, {
                headers: {
                    Accept: 'application/json',
                },
            });

            if (!response.ok) {
                return;
            }

            const data = await response.json();

            if (data.verified) {
                window.location.assign(verifiedUrl);
            }
        } finally {
            checking = false;
        }
    };

    window.setInterval(checkVerification, 5000);
    document.addEventListener('visibilitychange', () => {
        if (!document.hidden) {
            checkVerification();
        }
    });
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', setupEmailVerificationWatch);
} else {
    setupEmailVerificationWatch();
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
