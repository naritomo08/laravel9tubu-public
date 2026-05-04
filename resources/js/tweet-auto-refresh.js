import { reloadWhenSessionExpired } from './session';

export const setupTweetAutoRefresh = () => {
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

    if (!latestUrl) {
        return;
    }

    const hasOpenTweetMenu = () => Boolean(document.querySelector('.tweet-option[open]'));

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
        createdAt: Date.parse(item.dataset.tweetCreatedAt || '') || 0,
        id: Number(item.dataset.tweetId || 0),
    });

    const shouldInsertBefore = (tweetItem, currentItem) => {
        const tweet = getTweetSortValue(tweetItem);
        const current = getTweetSortValue(currentItem);

        if (tweet.createdAt !== current.createdAt) {
            return tweet.createdAt > current.createdAt;
        }

        return tweet.id > current.id;
    };

    const insertTweetItem = (tweetItem) => {
        const existingItem = items.querySelector(`li[data-tweet-id="${tweetItem.dataset.tweetId}"]`);

        if (existingItem && existingItem !== tweetItem) {
            existingItem.remove();
        }

        const referenceItem = Array.from(items.querySelectorAll('li[data-tweet-id][data-tweet-created-at]'))
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
        if (loading || hasOpenTweetMenu()) {
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

            if (reloadWhenSessionExpired(response)) {
                return;
            }

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
        if (!document.hidden && !hasOpenTweetMenu()) {
            refresh();
        }
    });
};
