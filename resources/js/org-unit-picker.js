(() => {
    const pickers = [];

    const normalize = (value) => value
        .toLowerCase()
        .replace(/[أإآ]/g, 'ا')
        .replace(/ى/g, 'ي')
        .replace(/ة/g, 'ه')
        .replace(/[\u064B-\u0652]/g, '')
        .replace(/\s+/g, ' ')
        .trim();

    const repositionOpen = () => {
        pickers.forEach((picker) => picker.reposition());
    };

    const init = () => {
        document.querySelectorAll('[data-org-picker]:not(.is-locked)').forEach(setup);
        window.addEventListener('resize', repositionOpen);
        window.addEventListener('scroll', repositionOpen, true);
    };

    const setup = (picker) => {
        if (picker.dataset.orgReady === '1') {
            return;
        }

        const select = picker.querySelector('select');
        const input = picker.querySelector('[data-org-search]');
        const menu = picker.querySelector('[data-org-menu]');
        const list = picker.querySelector('[data-org-list]');
        const empty = picker.querySelector('[data-org-empty]');
        const clearBtn = picker.querySelector('[data-org-clear]');
        const display = picker.querySelector('[data-org-display]');
        const kindEl = picker.querySelector('[data-org-kind]');
        const nameEl = picker.querySelector('[data-org-name]');
        const field = picker.querySelector('[data-org-field]');

        if (!select || !input || !menu || !list || !empty || !display || !kindEl || !nameEl || !field) {
            return;
        }

        picker.dataset.orgReady = '1';

        const placeholder = picker.dataset.placeholder || '';
        const searchPlaceholder = picker.dataset.searchPlaceholder || placeholder;
        const options = [...list.querySelectorAll('[data-org-option]')];
        const valueDescriptor = Object.getOwnPropertyDescriptor(HTMLSelectElement.prototype, 'value');
        let syncing = false;

        const selectedOption = () => options.find((option) => option.dataset.value === select.value) || null;

        const visibleOptions = () => options.filter((option) => !option.hidden);

        const scrollOptionIntoView = (option) => {
            const listRect = list.getBoundingClientRect();
            const optionRect = option.getBoundingClientRect();

            if (optionRect.top < listRect.top) {
                list.scrollTop -= listRect.top - optionRect.top;
            } else if (optionRect.bottom > listRect.bottom) {
                list.scrollTop += optionRect.bottom - listRect.bottom;
            }
        };

        const setActive = (option) => {
            options.forEach((item) => item.classList.remove('is-active'));

            if (!option) {
                input.removeAttribute('aria-activedescendant');
                return;
            }

            option.classList.add('is-active');
            input.setAttribute('aria-activedescendant', option.id);
            scrollOptionIntoView(option);
        };

        const renderClosed = () => {
            const option = selectedOption();
            syncing = true;
            picker.classList.toggle('has-value', Boolean(option));

            if (option) {
                const kind = option.dataset.kind || '';
                kindEl.textContent = kind;
                kindEl.hidden = kind === '';
                nameEl.textContent = option.dataset.name || option.dataset.label || '';
                display.hidden = false;
                field.title = option.dataset.full || nameEl.textContent;
            } else {
                kindEl.textContent = '';
                kindEl.hidden = true;
                nameEl.textContent = '';
                display.hidden = true;
                field.removeAttribute('title');
            }

            input.value = '';
            input.placeholder = option ? searchPlaceholder : placeholder;

            if (clearBtn) {
                clearBtn.hidden = !option;
            }

            options.forEach((item) => {
                item.setAttribute('aria-selected', option && item === option ? 'true' : 'false');
            });

            syncing = false;
        };

        const filter = (query) => {
            const needle = normalize(query);
            let shown = 0;

            options.forEach((option) => {
                const haystack = normalize(option.dataset.search || '');
                const match = needle === '' || haystack.includes(needle);
                option.hidden = !match;
                option.style.setProperty('--depth', needle === '' ? (option.dataset.depth || '0') : '0');

                if (match) {
                    shown += 1;
                }
            });

            empty.hidden = shown !== 0;
            list.hidden = shown === 0;

            const active = list.querySelector('[data-org-option].is-active');

            if (!active || active.hidden) {
                setActive(visibleOptions()[0] || null);
            }
        };

        const position = () => {
            if (menu.hidden) {
                return;
            }

            const rect = field.getBoundingClientRect();
            const margin = 12;
            const gap = 6;
            const preferred = 320;
            const spaceBelow = window.innerHeight - rect.bottom - gap - margin;
            const spaceAbove = rect.top - gap - margin;
            const openUp = spaceBelow < 180 && spaceAbove > spaceBelow;
            const maxHeight = Math.max(140, Math.min(preferred, openUp ? spaceAbove : spaceBelow));
            let left = rect.left;
            let width = rect.width;

            if (left < margin) {
                width -= margin - left;
                left = margin;
            }

            if (left + width > window.innerWidth - margin) {
                width = window.innerWidth - margin - left;
            }

            menu.style.left = `${left}px`;
            menu.style.width = `${Math.max(width, 160)}px`;
            menu.style.right = 'auto';
            list.style.maxHeight = `${maxHeight}px`;
            picker.classList.toggle('is-open-up', openUp);

            if (openUp) {
                menu.style.top = 'auto';
                menu.style.bottom = `${window.innerHeight - rect.top + gap}px`;
            } else {
                menu.style.bottom = 'auto';
                menu.style.top = `${rect.bottom + gap}px`;
            }
        };

        let openGeneration = 0;

        const closeMenu = () => {
            if (menu.hidden) {
                renderClosed();
                return;
            }

            menu.hidden = true;
            picker.classList.remove('is-open', 'is-open-up');
            input.setAttribute('aria-expanded', 'false');
            renderClosed();
        };

        const openMenu = () => {
            if (!menu.hidden) {
                position();
                return;
            }

            document.querySelectorAll('[data-org-picker].is-open').forEach((other) => {
                if (other !== picker) {
                    other.dispatchEvent(new CustomEvent('org-picker:close'));
                }
            });

            openGeneration += 1;
            menu.hidden = false;
            picker.classList.add('is-open');
            input.setAttribute('aria-expanded', 'true');
            input.placeholder = searchPlaceholder;
            filter(input.value);
            position();

            const current = selectedOption();
            const initial = current && !current.hidden ? current : (visibleOptions()[0] || null);
            setActive(initial);
            if (initial) {
                scrollOptionIntoView(initial);
            }
        };

        const choose = (option) => {
            const next = option.dataset.value || '';

            if (select.value !== next) {
                select.value = next;
                select.dispatchEvent(new Event('change', { bubbles: true }));
            }

            picker.classList.remove('is-invalid');
            closeMenu();
        };

        const clear = () => {
            if (select.value !== '') {
                select.value = '';
                select.dispatchEvent(new Event('change', { bubbles: true }));
            }

            closeMenu();
            input.focus();
        };

        Object.defineProperty(select, 'value', {
            configurable: true,
            enumerable: true,
            get() {
                return valueDescriptor.get.call(this);
            },
            set(next) {
                valueDescriptor.set.call(this, next);

                if (picker.classList.contains('is-open')) {
                    options.forEach((item) => {
                        item.setAttribute('aria-selected', item.dataset.value === select.value ? 'true' : 'false');
                    });
                } else {
                    renderClosed();
                }
            },
        });

        select.addEventListener('focus', () => {
            input.focus();
        });

        select.addEventListener('invalid', () => {
            picker.classList.add('is-invalid');
            openMenu();
        });

        select.addEventListener('change', () => {
            picker.classList.remove('is-invalid');
        });

        input.addEventListener('focus', () => {
            openMenu();
        });

        input.addEventListener('input', () => {
            if (syncing) {
                return;
            }

            openMenu();
            filter(input.value);
        });

        input.addEventListener('keydown', (event) => {
            if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
                event.preventDefault();
                const wasClosed = menu.hidden;
                openMenu();

                if (wasClosed) {
                    return;
                }

                const items = visibleOptions();
                const current = list.querySelector('[data-org-option].is-active:not([hidden])');
                const index = items.indexOf(current);
                const nextIndex = event.key === 'ArrowDown'
                    ? Math.min(index + 1, items.length - 1)
                    : Math.max(index - 1, 0);
                setActive(items[nextIndex] || null);
                return;
            }

            if (event.key === 'Home' && !menu.hidden) {
                event.preventDefault();
                setActive(visibleOptions()[0] || null);
            } else if (event.key === 'End' && !menu.hidden) {
                event.preventDefault();
                const items = visibleOptions();
                setActive(items[items.length - 1] || null);
            } else if (event.key === 'Enter' && !menu.hidden) {
                event.preventDefault();
                const current = list.querySelector('[data-org-option].is-active:not([hidden])');
                if (current) {
                    choose(current);
                }
            } else if (event.key === 'Escape' && !menu.hidden) {
                event.preventDefault();
                closeMenu();
            } else if (event.key === 'Tab') {
                closeMenu();
            }
        });

        field.addEventListener('pointerdown', (event) => {
            if (event.button !== 0) {
                return;
            }

            if (event.target.closest('[data-org-clear], [data-org-caret]')) {
                return;
            }

            openMenu();
        });

        field.addEventListener('mousedown', (event) => {
            if (event.target.closest('[data-org-clear], [data-org-caret]')) {
                event.preventDefault();
            }
        });

        field.addEventListener('click', (event) => {
            if (event.target.closest('[data-org-clear]')) {
                clear();
                return;
            }

            if (event.target.closest('[data-org-caret]')) {
                if (menu.hidden) {
                    openMenu();
                    input.focus();
                } else {
                    closeMenu();
                    input.blur();
                }
                return;
            }

            if (document.activeElement !== input) {
                input.focus();
            } else if (menu.hidden) {
                openMenu();
            }
        });

        options.forEach((option) => {
            option.addEventListener('mousedown', (event) => {
                event.preventDefault();
            });
            option.addEventListener('click', () => choose(option));
            option.addEventListener('mouseenter', () => setActive(option));
        });

        picker.addEventListener('focusout', () => {
            const generation = openGeneration;

            window.setTimeout(() => {
                if (generation !== openGeneration) {
                    return;
                }

                if (!picker.contains(document.activeElement)) {
                    closeMenu();
                }
            }, 120);
        });

        picker.addEventListener('org-picker:close', closeMenu);

        renderClosed();
        pickers.push({ reposition: position });
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
