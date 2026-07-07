(function () {
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
        });
    });
})();
