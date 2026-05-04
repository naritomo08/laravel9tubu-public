import { reloadWhenSessionExpired } from './session';

export const setupTweetSearch = () => {
    const search = document.querySelector('[data-tweet-search]');

    if (!search || search.dataset.initialized === 'true') {
        return;
    }

    search.dataset.initialized = 'true';

    const input = search.querySelector('[data-tweet-search-input]');
    const userSearchInput = search.querySelector('[data-tweet-user-search-input]');
    const userSelectWrap = search.querySelector('[data-tweet-user-select-wrap]');
    const userSelect = search.querySelector('[data-tweet-user-select]');
    const results = document.querySelector('[data-tweet-search-results]');
    const count = search.querySelector('[data-tweet-search-count]');
    const loading = search.querySelector('[data-tweet-search-loading]');
    const searchUrl = search.dataset.searchUrl;
    const usersUrl = search.dataset.usersUrl;
    let debounceTimer = null;
    let controller = null;
    let usersController = null;
    let currentPage = Number(new URL(window.location.href).searchParams.get('page') || 1);

    if (!input || !results || !searchUrl) {
        return;
    }

    const hasOpenTweetMenu = () => Boolean(results.querySelector('.tweet-option[open]'));

    const renderUserOptions = (users, selectedUserId = '') => {
        if (!userSelect) {
            return;
        }

        userSelect.innerHTML = '';

        const emptyOption = document.createElement('option');
        emptyOption.value = '';
        emptyOption.textContent = 'ユーザーを選択';
        userSelect.appendChild(emptyOption);

        users.forEach((user) => {
            const option = document.createElement('option');
            option.value = String(user.id);
            option.textContent = user.name;
            option.selected = String(user.id) === selectedUserId;
            userSelect.appendChild(option);
        });

        if (selectedUserId && userSelect.value !== selectedUserId) {
            userSelect.value = '';
            userSelect.dataset.selectedUserId = '';
        }
    };

    const refreshUserOptions = async () => {
        if (!usersUrl || !userSelect) {
            return;
        }

        const selectedUserId = userSelect.value || userSelect.dataset.selectedUserId || '';

        if (usersController) {
            usersController.abort();
        }

        usersController = new AbortController();
        userSelect.disabled = true;

        try {
            const response = await fetch(new URL(usersUrl, window.location.origin).toString(), {
                headers: {
                    Accept: 'application/json',
                },
                signal: usersController.signal,
            });

            if (reloadWhenSessionExpired(response) || !response.ok) {
                return;
            }

            const data = await response.json();
            renderUserOptions(data.users || [], selectedUserId);
        } catch (error) {
            if (error.name !== 'AbortError') {
                console.error('Error loading users:', error);
            }
        } finally {
            userSelect.disabled = false;
        }
    };

    const toggleUserSelect = () => {
        const userSearch = userSearchInput?.checked === true;

        userSelectWrap?.classList.toggle('hidden', ! userSearch);
        input.disabled = userSearch;
        input.classList.toggle('bg-gray-100', userSearch);
        input.classList.toggle('dark:bg-gray-800', userSearch);
    };

    const updateBrowserUrl = (query, userSearch, userId, page = 1) => {
        const url = new URL(window.location.href);

        if (! userSearch && query) {
            url.searchParams.set('q', query);
        } else {
            url.searchParams.delete('q');
        }

        if (userSearch) {
            url.searchParams.set('user_search', '1');
        } else {
            url.searchParams.delete('user_search');
        }

        if (userSearch && userId) {
            url.searchParams.set('user_id', userId);
        } else {
            url.searchParams.delete('user_id');
        }

        if (page > 1) {
            url.searchParams.set('page', String(page));
        } else {
            url.searchParams.delete('page');
        }

        window.history.replaceState({}, '', url.toString());
    };

    const searchTweets = async (page = 1, options = {}) => {
        if (options.silent && hasOpenTweetMenu()) {
            return;
        }

        currentPage = page;

        const userSearch = userSearchInput?.checked === true;
        const query = userSearch ? '' : input.value.trim();
        const userId = userSearch ? (userSelect?.value || '') : '';
        updateBrowserUrl(query, userSearch, userId, page);

        if (controller) {
            controller.abort();
        }

        controller = new AbortController();
        if (!options.silent) {
            loading?.classList.remove('hidden');
        }

        try {
            const url = new URL(searchUrl, window.location.origin);
            url.searchParams.set('q', query);
            url.searchParams.set('user_search', userSearch ? '1' : '0');
            if (userId) {
                url.searchParams.set('user_id', userId);
            }
            url.searchParams.set('page', String(page));

            const response = await fetch(url.toString(), {
                headers: {
                    Accept: 'application/json',
                },
                signal: controller.signal,
            });

            if (reloadWhenSessionExpired(response)) {
                return;
            }

            if (!response.ok) {
                return;
            }

            const data = await response.json();
            const nextPage = Number(data.current_page || page);

            currentPage = nextPage;
            updateBrowserUrl(query, userSearch, userId, nextPage);
            results.innerHTML = data.html || '';

            if (count) {
                count.textContent = `${data.count ?? 0}件`;
            }

            window.Alpine?.initTree(results);
        } catch (error) {
            if (error.name !== 'AbortError') {
                console.error('Error searching tweets:', error);
            }
        } finally {
            if (!options.silent) {
                loading?.classList.add('hidden');
            }
        }
    };

    input.addEventListener('input', () => {
        window.clearTimeout(debounceTimer);
        debounceTimer = window.setTimeout(searchTweets, 300);
    });

    userSearchInput?.addEventListener('change', async () => {
        toggleUserSelect();
        if (userSearchInput?.checked === true) {
            await refreshUserOptions();
        }
        searchTweets();
    });

    userSelect?.addEventListener('change', () => searchTweets());

    results.addEventListener('click', (event) => {
        const link = event.target.closest('a[href]');

        if (!link) {
            return;
        }

        const pagination = link.closest(
            '[data-tweet-search-pagination-top], [data-tweet-search-pagination-bottom]',
        );

        if (!pagination) {
            return;
        }

        const url = new URL(link.href, window.location.origin);
        const page = Number(url.searchParams.get('page') || 1);

        if (!page) {
            return;
        }

        event.preventDefault();
        searchTweets(page);
    });

    window.setInterval(() => searchTweets(currentPage, { silent: true }), 5000);
    document.addEventListener('visibilitychange', () => {
        if (!document.hidden && !hasOpenTweetMenu()) {
            searchTweets(currentPage, { silent: true });
        }
    });

    toggleUserSelect();
    if (userSearchInput?.checked === true) {
        refreshUserOptions();
    }
};
