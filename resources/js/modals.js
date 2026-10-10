document.addEventListener('click', (event) => {
    const openButton = event.target.closest('[data-modal-open]');

    if (openButton) {
        event.preventDefault();
        const name = openButton.getAttribute('data-modal-open');
        const modal = name ? document.getElementById('modal-' + name) : null;

        if (!modal) {
            return;
        }

        document.querySelectorAll('.modal.is-open').forEach((openModal) => {
            if (openModal !== modal) {
                openModal.classList.remove('is-open');
            }
        });

        modal.classList.add('is-open');
        document.body.style.overflow = 'hidden';
        modal.querySelector('input, textarea, select')?.focus();
        return;
    }

    if (!event.target.closest('[data-modal-close]')) {
        return;
    }

    const modal = event.target.closest('.modal');

    if (!modal) {
        return;
    }

    modal.classList.remove('is-open');

    if (!document.querySelector('.modal.is-open')) {
        document.body.style.overflow = '';
    }
});

document.addEventListener('keydown', (event) => {
    if (event.key !== 'Escape') {
        return;
    }

    document.querySelectorAll('.modal.is-open').forEach((modal) => {
        modal.classList.remove('is-open');
    });
    document.body.style.overflow = '';
});
