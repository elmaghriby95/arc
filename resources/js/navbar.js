document.addEventListener('DOMContentLoaded', () => {
    const navbarMenu = document.querySelector('[data-navbar-menu]');
    const navbarToggle = document.querySelector('[data-navbar-toggle]');
    const navbarBackdrop = document.querySelector('[data-navbar-backdrop]');

    const closeMobileMenu = () => {
        navbarMenu?.classList.remove('is-open');
        navbarToggle?.classList.remove('is-active');
        navbarToggle?.setAttribute('aria-expanded', 'false');
        navbarBackdrop?.classList.remove('is-visible');
        document.body.style.overflow = '';
    };

    const closeNavDropdowns = () => {
        document.querySelectorAll('[data-navbar-dropdown].is-open').forEach((dropdown) => {
            dropdown.classList.remove('is-open');
            dropdown.querySelector('[data-dropdown-toggle]')?.setAttribute('aria-expanded', 'false');
        });
    };

    document.querySelectorAll('[data-navbar-dropdown]').forEach((dropdown) => {
        const toggle = dropdown.querySelector('[data-dropdown-toggle]');
        if (!toggle) {
            return;
        }

        toggle.addEventListener('click', (event) => {
            event.preventDefault();
            event.stopPropagation();

            const willOpen = !dropdown.classList.contains('is-open');
            closeNavDropdowns();

            if (willOpen) {
                dropdown.classList.add('is-open');
                toggle.setAttribute('aria-expanded', 'true');
            }
        });
    });

    document.addEventListener('click', (event) => {
        if (event.target.closest('[data-navbar-dropdown]')) {
            return;
        }

        closeNavDropdowns();
    });

    document.querySelectorAll('[data-navbar-menu] .navbar-nav-subitem').forEach((link) => {
        link.addEventListener('click', () => {
            closeNavDropdowns();
            closeMobileMenu();
        });
    });
});
