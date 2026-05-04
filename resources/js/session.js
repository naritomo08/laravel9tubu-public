const sessionExpiredStatuses = new Set([401, 419]);
let sessionExpiredReloadStarted = false;

export const reloadWhenSessionExpired = (response) => {
    if (!sessionExpiredStatuses.has(response.status)) {
        return false;
    }

    if (!sessionExpiredReloadStarted) {
        sessionExpiredReloadStarted = true;
        window.location.reload();
    }

    return true;
};

export const setupSessionTimeoutLogout = () => {
    const sessionExpiresAtKey = 'tubuyaki.authSessionExpiresAt';
    const sessionStarted = document.querySelector('meta[name="auth-session-started"]')?.content === 'true';

    if (!sessionStarted) {
        try {
            sessionStorage.removeItem(sessionExpiresAtKey);
        } catch (error) {
            // Session storage may be unavailable in private browsing modes.
        }

        return;
    }

    const timeoutMinutes = Number(document.querySelector('meta[name="auth-session-timeout-minutes"]')?.content || 0);
    const logoutUrl = document.querySelector('meta[name="auth-logout-url"]')?.content;
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

    if (!timeoutMinutes || !logoutUrl || !csrfToken) {
        return;
    }

    const timeoutMilliseconds = timeoutMinutes * 60 * 1000;
    const fallbackExpiresAt = Date.now() + timeoutMilliseconds;
    let expiresAt = fallbackExpiresAt;

    try {
        const storedExpiresAt = Number(sessionStorage.getItem(sessionExpiresAtKey) || 0);

        if (storedExpiresAt > Date.now() && storedExpiresAt <= fallbackExpiresAt) {
            expiresAt = storedExpiresAt;
        } else {
            sessionStorage.setItem(sessionExpiresAtKey, String(expiresAt));
        }
    } catch (error) {
        // Keep the in-memory timeout if session storage is blocked.
    }

    let timeoutLogoutStarted = false;

    const logoutAndReload = async () => {
        if (timeoutLogoutStarted) {
            return;
        }

        timeoutLogoutStarted = true;

        try {
            sessionStorage.removeItem(sessionExpiresAtKey);
        } catch (error) {
            // Session storage may be unavailable in private browsing modes.
        }

        const controller = new AbortController();
        const abortTimer = window.setTimeout(() => controller.abort(), 5000);

        try {
            await fetch(logoutUrl, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: new URLSearchParams({ _token: csrfToken }).toString(),
                credentials: 'same-origin',
                signal: controller.signal,
            });
        } finally {
            window.clearTimeout(abortTimer);
            window.location.reload();
        }
    };

    const checkTimeout = () => {
        if (Date.now() >= expiresAt) {
            logoutAndReload();
        }
    };

    window.setTimeout(logoutAndReload, Math.max(expiresAt - Date.now(), 0));
    window.setInterval(checkTimeout, 30000);
    window.addEventListener('focus', checkTimeout);
    window.addEventListener('pageshow', checkTimeout);
    document.addEventListener('visibilitychange', () => {
        if (!document.hidden) {
            checkTimeout();
        }
    });
};
