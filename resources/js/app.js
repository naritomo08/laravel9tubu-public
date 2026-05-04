import './bootstrap';

import Alpine from 'alpinejs';
import { setupAlpineComponents } from './alpine-components';
import { setupAutoDismissAlerts } from './auto-dismiss-alerts';
import { onReady } from './dom-ready';
import { setupLikeButtons } from './like-buttons';
import { setupLiveTableRefreshers } from './live-table-refreshers';
import { setupAdminAccessWatch, setupAdminNavWatch, setupEmailVerificationWatch } from './status-watchers';
import { setupSessionTimeoutLogout } from './session';
import { setupThemeToggle } from './theme-toggle';
import { setupTweetAutoRefresh } from './tweet-auto-refresh';
import { setupTweetCharacterCounters } from './tweet-character-counters';
import { setupTweetSearch } from './tweet-search';

window.Alpine = Alpine;

setupAlpineComponents(Alpine);

Alpine.start();

onReady(() => {
    setupSessionTimeoutLogout();
    setupThemeToggle();
    setupAutoDismissAlerts();
    setupLiveTableRefreshers();
    setupTweetCharacterCounters();
    setupTweetAutoRefresh();
    setupTweetSearch();
    setupEmailVerificationWatch();
    setupAdminNavWatch();
    setupAdminAccessWatch();
    setupLikeButtons();
});
