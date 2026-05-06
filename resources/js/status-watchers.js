import { reloadWhenSessionExpired } from './session';

export const setupEmailVerificationWatch = () => {
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

            if (reloadWhenSessionExpired(response)) {
                return;
            }

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

export const setupAdminNavWatch = () => {
    if (window.__tubuyakiAdminNavWatchStarted) {
        return;
    }

    const target = document.querySelector('[data-admin-nav-watch]');

    if (!target) {
        return;
    }

    const statusUrl = target.dataset.statusUrl;
    const adminUrl = target.dataset.adminUrl;
    const twoFactorWarning = document.querySelector('[data-admin-two-factor-warning]');

    if (!statusUrl || !adminUrl) {
        return;
    }

    window.__tubuyakiAdminNavWatchStarted = true;

    let checking = false;

    const renderAdminButton = () => {
        target.dataset.isAdmin = 'true';
        target.innerHTML = `
            <a href="${adminUrl}" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 text-white bg-blue-500 hover:bg-blue-600 focus:ring-blue-500">
                管理者画面
            </a>
        `;
    };

    const checkAdminStatus = async () => {
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

            if (reloadWhenSessionExpired(response)) {
                return;
            }

            if (!response.ok) {
                return;
            }

            const data = await response.json();

            if (data.is_admin && target.dataset.isAdmin !== 'true') {
                renderAdminButton();
            } else if (!data.is_admin && target.dataset.isAdmin === 'true') {
                target.dataset.isAdmin = 'false';
                target.innerHTML = '';
            }

            if (data.has_two_factor_enabled && twoFactorWarning) {
                twoFactorWarning.remove();
            }
        } finally {
            checking = false;
        }
    };

    window.setInterval(checkAdminStatus, 5000);
    document.addEventListener('visibilitychange', () => {
        if (!document.hidden) {
            checkAdminStatus();
        }
    });
};

export const setupAdminAccessWatch = () => {
    if (window.__tubuyakiAdminAccessWatchStarted) {
        return;
    }

    const target = document.querySelector('[data-admin-access-watch]');

    if (!target) {
        return;
    }

    const statusUrl = target.dataset.statusUrl;
    const redirectUrl = target.dataset.redirectUrl || '/tweet';

    if (!statusUrl) {
        return;
    }

    window.__tubuyakiAdminAccessWatchStarted = true;

    let checking = false;

    const checkAdminAccess = async () => {
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

            if (reloadWhenSessionExpired(response)) {
                return;
            }

            if (!response.ok) {
                return;
            }

            const data = await response.json();

            if (!data.is_admin) {
                window.location.assign(redirectUrl);
            }
        } finally {
            checking = false;
        }
    };

    window.setInterval(checkAdminAccess, 5000);
    document.addEventListener('visibilitychange', () => {
        if (!document.hidden) {
            checkAdminAccess();
        }
    });
};
