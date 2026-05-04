export const setupAutoDismissAlerts = () => {
    document.querySelectorAll('[data-auto-dismiss]').forEach((alert) => {
        if (alert.dataset.autoDismissInitialized === 'true') {
            return;
        }

        alert.dataset.autoDismissInitialized = 'true';

        const delay = Number(alert.dataset.autoDismiss || 4000);
        const fadeDuration = 500;

        window.setTimeout(() => {
            alert.classList.add('opacity-0');

            window.setTimeout(() => {
                alert.remove();
            }, fadeDuration);
        }, delay);
    });
};
