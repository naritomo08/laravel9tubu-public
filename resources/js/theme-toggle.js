export const setupThemeToggle = () => {
    const getTheme = () => {
        try {
            return localStorage.getItem('theme') || 'light';
        } catch (error) {
            return 'light';
        }
    };

    const saveTheme = (theme) => {
        try {
            localStorage.setItem('theme', theme);
        } catch (error) {
            // Theme switching should still work for the current page.
        }
    };

    const applyTheme = (theme) => {
        const isDark = theme === 'dark';

        document.documentElement.classList.toggle('dark', isDark);
        saveTheme(theme);

        document.querySelectorAll('[data-theme-toggle]').forEach((button) => {
            button.setAttribute('aria-pressed', isDark ? 'true' : 'false');
        });

        document.querySelectorAll('[data-theme-toggle-label]').forEach((label) => {
            label.textContent = isDark ? 'ダーク' : 'ライト';
        });

        document.querySelectorAll('[data-theme-toggle-icon]').forEach((icon) => {
            icon.textContent = isDark ? '☾' : '☀';
        });
    };

    applyTheme(getTheme());

    document.addEventListener('click', (event) => {
        if (!event.target.closest('[data-theme-toggle]')) {
            return;
        }

        applyTheme(document.documentElement.classList.contains('dark') ? 'light' : 'dark');
    });
};
