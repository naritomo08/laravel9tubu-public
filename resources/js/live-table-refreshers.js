const refreshInterval = 15000;
const requestHeaders = {
    'X-Requested-With': 'XMLHttpRequest',
    Accept: 'application/json',
};

const escapeHtml = (value) => {
    const div = document.createElement('div');
    div.textContent = value ?? '';
    return div.innerHTML;
};

const startJsonPoller = ({ tableSelector, bodySelector, urlDatasetKey, onData, errorMessage }) => {
    const table = document.querySelector(tableSelector);
    const body = document.querySelector(bodySelector);

    if (!table || !body || table.dataset.initialized === 'true') {
        return;
    }

    const url = table.dataset[urlDatasetKey];

    if (!url) {
        return;
    }

    table.dataset.initialized = 'true';

    const refresh = async () => {
        try {
            const response = await fetch(url, { headers: requestHeaders });

            if (!response.ok) {
                return;
            }

            onData(await response.json(), body);
        } catch (error) {
            console.error(errorMessage, error);
        }
    };

    window.setInterval(refresh, refreshInterval);
};

const renderAccountStats = (stats, body) => {
    body.innerHTML = `
        <tr class="bg-blue-50 font-bold dark:bg-gray-800">
            <td class="py-2 px-4 border-b dark:border-gray-700">${escapeHtml(stats.label)}</td>
            <td class="py-2 px-4 border-b text-right dark:border-gray-700">${stats.tweet_count}</td>
            <td class="py-2 px-4 border-b text-right dark:border-gray-700">${stats.like_count}</td>
        </tr>
    `;
};

const renderAdminStats = (stats, body) => {
    const totalRow = `
        <tr class="bg-blue-50 font-bold dark:bg-gray-800">
            <td class="py-2 px-4 border-b dark:border-gray-700">${escapeHtml(stats.totals.label)}</td>
            <td class="py-2 px-4 border-b text-right dark:border-gray-700">${stats.totals.tweet_count}</td>
            <td class="py-2 px-4 border-b text-right dark:border-gray-700">${stats.totals.like_count}</td>
        </tr>
    `;

    const userRows = stats.users.map((user) => `
        <tr>
            <td class="py-2 px-4 border-b dark:border-gray-700">${escapeHtml(user.name)}</td>
            <td class="py-2 px-4 border-b text-right dark:border-gray-700">${user.tweet_count}</td>
            <td class="py-2 px-4 border-b text-right dark:border-gray-700">${user.like_count}</td>
        </tr>
    `).join('');

    body.innerHTML = totalRow + userRows;
};

const renderHtmlResponse = (data, body) => {
    body.innerHTML = data.html ?? '';
};

export const setupLiveTableRefreshers = () => {
    startJsonPoller({
        tableSelector: '[data-account-stats-table]',
        bodySelector: '[data-account-stats-body]',
        urlDatasetKey: 'statsUrl',
        onData: renderAccountStats,
        errorMessage: 'Error refreshing account stats:',
    });

    startJsonPoller({
        tableSelector: '[data-account-scheduled-tweets-table]',
        bodySelector: '[data-account-scheduled-tweets-body]',
        urlDatasetKey: 'scheduledTweetsUrl',
        onData: renderHtmlResponse,
        errorMessage: 'Error refreshing account scheduled tweets:',
    });

    startJsonPoller({
        tableSelector: '[data-admin-stats-table]',
        bodySelector: '[data-admin-stats-body]',
        urlDatasetKey: 'statsUrl',
        onData: renderAdminStats,
        errorMessage: 'Error refreshing admin stats:',
    });

    startJsonPoller({
        tableSelector: '[data-admin-scheduled-tweets-table]',
        bodySelector: '[data-admin-scheduled-tweets-body]',
        urlDatasetKey: 'scheduledTweetsUrl',
        onData: renderHtmlResponse,
        errorMessage: 'Error refreshing admin scheduled tweets:',
    });

    startJsonPoller({
        tableSelector: '[data-admin-users-table]',
        bodySelector: '[data-admin-users-body]',
        urlDatasetKey: 'usersUrl',
        onData: renderHtmlResponse,
        errorMessage: 'Error refreshing admin users:',
    });
};
