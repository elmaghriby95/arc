document.addEventListener('DOMContentLoaded', () => {
    const navbarMenu = document.querySelector('[data-navbar-menu]');
    const navbarToggle = document.querySelector('[data-navbar-toggle]');
    const navbarBackdrop = document.querySelector('[data-navbar-backdrop]');

    document.querySelectorAll('[data-dropdown]').forEach((dropdown) => {
        const toggle = dropdown.querySelector('[data-dropdown-toggle]');

        toggle?.addEventListener('click', (event) => {
            event.stopPropagation();
            const willOpen = !dropdown.classList.contains('is-open');

            document.querySelectorAll('[data-dropdown].is-open').forEach((other) => {
                other.classList.remove('is-open');
                other.querySelector('[data-dropdown-toggle]')?.setAttribute('aria-expanded', 'false');
            });

            dropdown.classList.toggle('is-open', willOpen);
            toggle.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
        });
    });

    document.addEventListener('click', () => {
        document.querySelectorAll('[data-dropdown].is-open').forEach((dropdown) => {
            dropdown.classList.remove('is-open');
            dropdown.querySelector('[data-dropdown-toggle]')?.setAttribute('aria-expanded', 'false');
        });
    });

    const closeMobileMenu = () => {
        navbarMenu?.classList.remove('is-open');
        navbarToggle?.classList.remove('is-active');
        navbarToggle?.setAttribute('aria-expanded', 'false');
        navbarBackdrop?.classList.remove('is-visible');
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
});
