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
    const latestUrl = list.dataset.latestUrl;
    let latestTweetId = Number(list.dataset.latestTweetId || 0);
    let loading = false;

    const refresh = async () => {
        if (loading) {
            return;
        }

        loading = true;

        try {
            const url = new URL(latestUrl, window.location.origin);
            url.searchParams.set('after_id', latestTweetId);

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
                items.insertAdjacentHTML('afterbegin', data.html);
                window.Alpine?.initTree(items);
            }

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
