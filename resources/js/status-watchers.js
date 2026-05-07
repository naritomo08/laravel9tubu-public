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
    const verificationSendUrl = target.dataset.verificationSendUrl;
    const csrfToken = target.dataset.csrfToken;
    let checking = false;

    const warningHtml = (isPendingInitialEmailVerification) => `
        <div class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4 mb-4" data-email-verification-warning>
            ${isPendingInitialEmailVerification
                ? `メール認証が完了していません。登録時のメールアドレスに届いた認証メールをご確認ください。<br>
                    <span class="text-red-600 font-bold">※登録から1時間以内にメール認証が完了しない場合、アカウントは自動的に削除されます。</span>`
                : 'メール認証が完了していません。新しいメールアドレスに届いた認証メールをご確認ください。'}
            <form method="POST" action="${verificationSendUrl || '/email/verification-notification'}">
                <input type="hidden" name="_token" value="${csrfToken || ''}">
                <button type="submit" class="underline text-blue-600">認証メールを再送する</button>
            </form>
        </div>
    `;

    const showVerificationWarning = (isPendingInitialEmailVerification) => {
        target.innerHTML = warningHtml(isPendingInitialEmailVerification);
    };

    const setVerificationState = (verified) => {
        document.documentElement.dataset.emailVerificationState = verified ? 'verified' : 'unverified';
    };

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
                if (target.dataset.isVerified !== 'true') {
                    window.location.assign(verifiedUrl);
                }

                target.dataset.isVerified = 'true';
                setVerificationState(true);
                target.innerHTML = '';

                return;
            }

            target.dataset.isVerified = 'false';
            setVerificationState(false);

            if (!target.querySelector('[data-email-verification-warning]')) {
                showVerificationWarning(Boolean(data.pending_initial_email_verification));
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
    const twoFactorWarningTarget = document.querySelector('[data-admin-2fa-warning-watch]');

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

    const renderTwoFactorWarning = () => {
        if (!twoFactorWarningTarget || twoFactorWarningTarget.querySelector('[data-admin-two-factor-warning]')) {
            return;
        }

        twoFactorWarningTarget.innerHTML = `
            <div class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4 mb-4" data-admin-two-factor-warning>
                管理者自身の2段階認証が未設定のため、他ユーザーのつぶやきの編集・削除はできません。2段階認証はアカウント設定から有効化できます。
            </div>
        `;
    };

    const clearTwoFactorWarning = () => {
        if (twoFactorWarningTarget) {
            twoFactorWarningTarget.innerHTML = '';
        }
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

            if (data.is_admin && !data.has_two_factor_enabled) {
                renderTwoFactorWarning();
            } else {
                clearTwoFactorWarning();
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
