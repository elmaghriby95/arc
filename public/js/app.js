document.addEventListener('DOMContentLoaded', () => {
    const navbarMenu = document.querySelector('[data-navbar-menu]');
    const navbarToggle = document.querySelector('[data-navbar-toggle]');
    const navbarBackdrop = document.querySelector('[data-navbar-backdrop]');

    const closeMobileMenu = () => {
        navbarMenu?.classList.remove('is-open');
        navbarToggle?.classList.remove('is-active');
        navbarToggle?.setAttribute('aria-expanded', 'false');
        document.body.style.overflow = '';
    };

    document.querySelectorAll('.navbar-notifications[data-dropdown]').forEach((dropdown) => {
        const menu = dropdown.querySelector('.dropdown-menu');
        if (!menu) {
            return;
        }

        const clampNotificationsMenu = () => {
            if (!dropdown.classList.contains('is-open')) {
                menu.style.transform = '';
                return;
            }

            menu.style.transform = 'translateX(-50%)';

            requestAnimationFrame(() => {
                const rect = menu.getBoundingClientRect();
                const pad = 12;
                let shift = 0;

                if (rect.left < pad) {
                    shift = pad - rect.left;
                } else if (rect.right > window.innerWidth - pad) {
                    shift = (window.innerWidth - pad) - rect.right;
                }

                menu.style.transform = shift
                    ? `translateX(calc(-50% + ${shift}px))`
                    : 'translateX(-50%)';
            });
        };

        dropdown.querySelector('[data-dropdown-toggle]')?.addEventListener('click', () => {
            setTimeout(clampNotificationsMenu, 0);
        });

        window.addEventListener('resize', clampNotificationsMenu);
    });

    document.querySelectorAll('.navbar-nav-details').forEach((details) => {
        details.addEventListener('toggle', () => {
            if (!details.open) {
                return;
            }

            document.querySelectorAll('.navbar-nav-details').forEach((other) => {
                if (other !== details) {
                    other.removeAttribute('open');
                }
            });
        });
    });

    document.addEventListener('click', (event) => {
        if (event.target.closest('.navbar-nav-details')) {
            return;
        }

        document.querySelectorAll('.navbar-nav-details[open]').forEach((details) => {
            details.removeAttribute('open');
        });
    });

    document.querySelectorAll('[data-navbar-menu] .navbar-nav-subitem').forEach((link) => {
        link.addEventListener('click', () => {
            closeMobileMenu();
            link.closest('.navbar-nav-details')?.removeAttribute('open');
        });
    });

    initIdleSessionGuard();
});

function initIdleSessionGuard() {
    const timeoutMeta = document.querySelector('meta[name="idle-timeout-minutes"]');
    const logoutUrl = document.querySelector('meta[name="logout-url"]')?.content;
    const keepaliveUrl = document.querySelector('meta[name="session-keepalive-url"]')?.content;
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

    if (!timeoutMeta || !logoutUrl || !csrfToken) {
        return;
    }

    const timeoutMinutes = Number.parseInt(timeoutMeta.content, 10);

    if (!Number.isFinite(timeoutMinutes) || timeoutMinutes <= 0) {
        return;
    }

    const timeoutMs = timeoutMinutes * 60 * 1000;
    const warningMs = Math.min(60_000, Math.max(10_000, Math.floor(timeoutMs * 0.25)));
    const keepaliveEveryMs = Math.min(30_000, Math.max(10_000, Math.floor(timeoutMs / 2)));
    const storageKey = 'arc_idle_last_activity';
    const endedKey = 'arc_idle_session_ended';
    const activityEvents = ['mousemove', 'mousedown', 'keydown', 'scroll', 'touchstart', 'wheel', 'click'];

    const modal = document.getElementById('idle-session-modal');
    const countdownEl = modal?.querySelector('[data-idle-countdown]');
    const stayBtn = modal?.querySelector('[data-idle-stay]');

    let lastActivity = Date.now();
    let lastKeepalive = 0;
    let warningVisible = false;
    let loggingOut = false;
    let tickTimer = null;
    let broadcastTimer = null;

    const writeActivity = (timestamp) => {
        lastActivity = timestamp;

        try {
            localStorage.setItem(storageKey, String(timestamp));
        } catch {
            // Ignore storage quota / private mode errors.
        }
    };

    const pingKeepalive = () => {
        if (!keepaliveUrl || loggingOut) {
            return;
        }

        const now = Date.now();

        if (now - lastKeepalive < keepaliveEveryMs) {
            return;
        }

        lastKeepalive = now;

        fetch(keepaliveUrl, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest',
                Accept: 'application/json',
            },
            credentials: 'same-origin',
        }).catch(() => {
            // Network errors are ignored; idle logout still runs client-side.
        });
    };

    const hideWarning = () => {
        warningVisible = false;

        if (!modal) {
            return;
        }

        modal.classList.remove('is-open');
        modal.hidden = true;
        document.body.style.overflow = '';
    };

    const showWarning = (secondsLeft) => {
        if (!modal) {
            return;
        }

        warningVisible = true;
        modal.hidden = false;
        modal.classList.add('is-open');
        document.body.style.overflow = 'hidden';

        if (countdownEl) {
            countdownEl.textContent = String(secondsLeft);
        }
    };

    const logout = () => {
        if (loggingOut) {
            return;
        }

        loggingOut = true;
        hideWarning();

        try {
            localStorage.setItem(endedKey, String(Date.now()));
        } catch {
            // Ignore.
        }

        const form = document.createElement('form');
        form.method = 'POST';
        form.action = logoutUrl;
        form.style.display = 'none';

        const tokenInput = document.createElement('input');
        tokenInput.type = 'hidden';
        tokenInput.name = '_token';
        tokenInput.value = csrfToken;
        form.appendChild(tokenInput);

        const idleInput = document.createElement('input');
        idleInput.type = 'hidden';
        idleInput.name = 'idle';
        idleInput.value = '1';
        form.appendChild(idleInput);

        document.body.appendChild(form);
        form.submit();
    };

    const markActivity = () => {
        if (loggingOut) {
            return;
        }

        writeActivity(Date.now());
        hideWarning();
        pingKeepalive();
    };

    const onActivity = () => {
        if (broadcastTimer) {
            return;
        }

        broadcastTimer = window.setTimeout(() => {
            broadcastTimer = null;
            markActivity();
        }, 500);
    };

    const tick = () => {
        if (loggingOut) {
            return;
        }

        const now = Date.now();
        const idleFor = now - lastActivity;
        const remainingMs = timeoutMs - idleFor;

        if (remainingMs <= 0) {
            logout();
            return;
        }

        if (remainingMs <= warningMs) {
            const secondsLeft = Math.max(1, Math.ceil(remainingMs / 1000));
            showWarning(secondsLeft);
            return;
        }

        if (warningVisible) {
            hideWarning();
        }
    };

    writeActivity(Date.now());
    pingKeepalive();

    activityEvents.forEach((eventName) => {
        document.addEventListener(eventName, onActivity, { passive: true });
    });

    stayBtn?.addEventListener('click', (event) => {
        event.preventDefault();
        markActivity();
    });

    window.addEventListener('storage', (event) => {
        if (event.key === endedKey && event.newValue) {
            logout();
            return;
        }

        if (event.key !== storageKey || !event.newValue) {
            return;
        }

        const remoteActivity = Number.parseInt(event.newValue, 10);

        if (!Number.isFinite(remoteActivity)) {
            return;
        }

        lastActivity = Math.max(lastActivity, remoteActivity);
        hideWarning();
    });

    document.addEventListener('visibilitychange', () => {
        if (document.visibilityState === 'visible') {
            tick();
        }
    });

    tickTimer = window.setInterval(tick, 1000);
    window.addEventListener('beforeunload', () => {
        if (tickTimer) {
            window.clearInterval(tickTimer);
        }
    });
}
