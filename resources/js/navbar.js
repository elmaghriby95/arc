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
        link.addEventListener('click', closeMobileMenu);
    });
});
