document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-dropdown]').forEach((dropdown) => {
        const toggle = dropdown.querySelector('[data-dropdown-toggle]');

        toggle?.addEventListener('click', (event) => {
            event.stopPropagation();
            dropdown.classList.toggle('is-open');
        });
    });

    document.addEventListener('click', () => {
        document.querySelectorAll('.dropdown.is-open').forEach((dropdown) => {
            dropdown.classList.remove('is-open');
        });
    });

    const navbar = document.querySelector('[data-navbar]');
    const navbarMenu = document.querySelector('[data-navbar-menu]');
    const navbarToggle = document.querySelector('[data-navbar-toggle]');
    const navbarBackdrop = document.querySelector('[data-navbar-backdrop]');

    const closeNavbarMenu = () => {
        navbarMenu?.classList.remove('is-open');
        navbarToggle?.classList.remove('is-active');
        navbarToggle?.setAttribute('aria-expanded', 'false');
        navbarBackdrop?.classList.remove('is-visible');
        document.body.style.overflow = '';
    };

    const openNavbarMenu = () => {
        navbarMenu?.classList.add('is-open');
        navbarToggle?.classList.add('is-active');
        navbarToggle?.setAttribute('aria-expanded', 'true');
        navbarBackdrop?.classList.add('is-visible');
        document.body.style.overflow = 'hidden';
    };

    navbarToggle?.addEventListener('click', (event) => {
        event.stopPropagation();

        if (navbarMenu?.classList.contains('is-open')) {
            closeNavbarMenu();
        } else {
            openNavbarMenu();
        }
    });

    navbarBackdrop?.addEventListener('click', closeNavbarMenu);

    document.querySelectorAll('[data-navbar-menu] .navbar-link').forEach((link) => {
        link.addEventListener('click', closeNavbarMenu);
    });

    window.addEventListener('scroll', () => {
        navbar?.classList.toggle('is-scrolled', window.scrollY > 8);
    }, { passive: true });

    navbar?.classList.toggle('is-scrolled', window.scrollY > 8);

    const mobileToggle = document.querySelector('[data-mobile-toggle]');
    const mobileMenu = document.querySelector('[data-mobile-menu]');

    mobileToggle?.addEventListener('click', () => {
        mobileMenu?.classList.toggle('is-open');
    });

    document.querySelectorAll('[data-modal-open]').forEach((button) => {
        button.addEventListener('click', () => {
            const modal = document.getElementById(`modal-${button.dataset.modalOpen}`);
            modal?.classList.add('is-open');
            document.body.style.overflow = 'hidden';
        });
    });

    document.querySelectorAll('[data-modal-close]').forEach((button) => {
        button.addEventListener('click', () => {
            const modal = button.closest('.modal');
            modal?.classList.remove('is-open');
            document.body.style.overflow = '';
        });
    });

    document.querySelectorAll('.modal-backdrop').forEach((backdrop) => {
        backdrop.addEventListener('click', () => {
            const modal = backdrop.closest('.modal');
            modal?.classList.remove('is-open');
            document.body.style.overflow = '';
        });
    });

    document.querySelectorAll('[data-password-toggle]').forEach((button) => {
        const input = button.closest('.login-input-wrap')?.querySelector('[data-password-input]');
        const iconShow = button.querySelector('[data-icon-show]');
        const iconHide = button.querySelector('[data-icon-hide]');

        if (! input) {
            return;
        }

        button.addEventListener('click', () => {
            const isHidden = input.type === 'password';
            input.type = isHidden ? 'text' : 'password';

            if (iconShow && iconHide) {
                iconShow.style.display = isHidden ? 'none' : 'block';
                iconHide.style.display = isHidden ? 'block' : 'none';
            }

            button.setAttribute('aria-label', isHidden ? 'إخفاء كلمة المرور' : 'إظهار كلمة المرور');
        });
    });

    document.querySelectorAll('[data-org-toggle]').forEach((button) => {
        button.addEventListener('click', () => {
            const node = button.closest('[data-org-node]');
            const children = node?.querySelector(':scope > [data-org-children]');
            const isExpanded = button.getAttribute('aria-expanded') === 'true';

            button.setAttribute('aria-expanded', isExpanded ? 'false' : 'true');
            node?.classList.toggle('is-collapsed', isExpanded);
            children?.classList.toggle('is-hidden', isExpanded);
        });
    });

    document.querySelector('[data-org-expand-all]')?.addEventListener('click', () => {
        document.querySelectorAll('[data-org-node]').forEach((node) => {
            node.classList.remove('is-collapsed');
            node.querySelector(':scope > [data-org-children]')?.classList.remove('is-hidden');
            node.querySelector('[data-org-toggle]')?.setAttribute('aria-expanded', 'true');
        });
    });

    const permissionsForm = document.querySelector('.permissions-form');

    if (permissionsForm) {
        const allCheckboxes = () => permissionsForm.querySelectorAll('input[type="checkbox"][name="permissions[]"]');

        permissionsForm.querySelector('[data-permissions-select-all]')?.addEventListener('click', () => {
            allCheckboxes().forEach((checkbox) => {
                checkbox.checked = true;
            });
        });

        permissionsForm.querySelector('[data-permissions-clear-all]')?.addEventListener('click', () => {
            allCheckboxes().forEach((checkbox) => {
                checkbox.checked = false;
            });
        });

        permissionsForm.querySelectorAll('[data-permission-group-toggle]').forEach((button) => {
            button.addEventListener('click', () => {
                const group = button.closest('[data-permission-group]');
                const checkboxes = group?.querySelectorAll('input[type="checkbox"][name="permissions[]"]') ?? [];
                const allChecked = Array.from(checkboxes).every((checkbox) => checkbox.checked);

                checkboxes.forEach((checkbox) => {
                    checkbox.checked = ! allChecked;
                });
            });
        });
    }
});
