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
        const input = button.closest('.login-input-wrap, .password-input-wrap')?.querySelector('[data-password-input]');
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

    const userCreateForm = document.querySelector('[data-user-create-form]');

    if (userCreateForm) {
        const generatePassword = () => {
            const chars = 'abcdefghjkmnpqrstuvwxyzABCDEFGHJKMNPQRSTUVWXYZ23456789!@#$%';
            let password = '';

            for (let i = 0; i < 12; i += 1) {
                password += chars.charAt(Math.floor(Math.random() * chars.length));
            }

            return password;
        };

        userCreateForm.querySelector('[data-generate-password]')?.addEventListener('click', () => {
            const password = generatePassword();
            const passwordInput = userCreateForm.querySelector('#password');
            const confirmInput = userCreateForm.querySelector('#password_confirmation');

            if (passwordInput) {
                passwordInput.value = password;
                passwordInput.type = 'text';
            }

            if (confirmInput) {
                confirmInput.value = password;
                confirmInput.type = 'text';
            }
        });

        const roleSelect = userCreateForm.querySelector('[data-role-select]');
        const roleDescription = userCreateForm.querySelector('[data-role-description]');
        const roleDescriptionText = userCreateForm.querySelector('[data-role-description-text]');

        const updateRoleDescription = () => {
            const option = roleSelect?.selectedOptions[0];
            const description = option?.dataset.description;

            if (! roleDescription || ! roleDescriptionText) {
                return;
            }

            if (description) {
                roleDescriptionText.textContent = description;
                roleDescription.hidden = false;
            } else {
                roleDescription.hidden = true;
            }
        };

        roleSelect?.addEventListener('change', updateRoleDescription);
        updateRoleDescription();

        const departmentSelect = userCreateForm.querySelector('#department_id');
        const orgPreview = userCreateForm.querySelector('[data-org-preview]');
        const orgPreviewText = userCreateForm.querySelector('[data-org-preview-text]');
        const breadcrumbs = window.__userCreateBreadcrumbs ?? {};

        const updateOrgPreview = () => {
            const departmentId = departmentSelect?.value;
            const path = departmentId ? breadcrumbs[departmentId] : null;

            if (! orgPreview || ! orgPreviewText) {
                return;
            }

            if (path) {
                orgPreviewText.textContent = path;
                orgPreview.hidden = false;
            } else {
                orgPreview.hidden = true;
            }
        };

        departmentSelect?.addEventListener('change', updateOrgPreview);
        updateOrgPreview();
    }

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

/* ===== Transaction Create (bundled) ===== */

document.addEventListener('DOMContentLoaded', () => {
    const form = document.querySelector('[data-txn-create-form]');

    if (! form) {
        return;
    }

    const refConfig = JSON.parse(form.dataset.refConfig || '{}');
    let currentStep = 1;
    const totalSteps = 3;

    const steps = form.querySelectorAll('[data-step]');
    const progressItems = form.querySelectorAll('[data-go-step]');
    const departmentSelect = document.getElementById('department_id');
    const transactionTypeSelect = document.getElementById('transaction_type_id');
    const folderPicker = document.querySelector('[data-folder-picker]');
    const folderInput = folderPicker?.querySelector('[data-folder-input]');
    const folderSearch = folderPicker?.querySelector('[data-folder-search]');
    const folderSelected = document.querySelector('[data-folder-selected]');
    const folderSelectedName = document.querySelector('[data-folder-selected-name]');
    const dropzone = form.querySelector('[data-txn-create-dropzone]');
    const fileInput = dropzone?.querySelector('[data-txn-file-input]');
    const browseBtn = dropzone?.querySelector('[data-txn-browse]');
    const content = dropzone?.querySelector('[data-txn-dropzone-content]');
    const queue = dropzone?.querySelector('[data-txn-queue]');
    const fileList = dropzone?.querySelector('[data-txn-file-list]');

    /** @type {File[]} */
    let selectedFiles = [];

    const allowedExtensions = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp'];

    const isAllowed = (file) => allowedExtensions.includes(file.name.split('.').pop()?.toLowerCase() ?? '');

    const formatSize = (bytes) => bytes >= 1048576 ? `${(bytes / 1048576).toFixed(1)} MB` : `${(bytes / 1024).toFixed(1)} KB`;

    const showStep = (step) => {
        currentStep = step;

        steps.forEach((panel) => {
            panel.classList.toggle('is-visible', Number(panel.dataset.step) === step);
        });

        progressItems.forEach((item) => {
            const n = Number(item.dataset.goStep);
            item.classList.toggle('is-active', n === step);
            item.classList.toggle('is-done', n < step);
        });

        window.scrollTo({ top: 0, behavior: 'smooth' });
    };

    const validateStep = (step) => {
        if (step === 1) {
            const title = document.getElementById('title');

            if (! title?.value.trim()) {
                title?.focus();
                title?.reportValidity();
                return false;
            }

            if (! departmentSelect?.value) {
                departmentSelect?.focus();
                departmentSelect?.reportValidity();
                return false;
            }
        }

        if (step === 2) {
            if (! folderInput?.value) {
                folderPicker?.scrollIntoView({ behavior: 'smooth', block: 'center' });
                folderPicker?.classList.add('txnw-box--error');
                setTimeout(() => folderPicker?.closest('.txnw-box')?.classList.add('txnw-box--error'), 0);
                alert('يرجى اختيار مجلد لحفظ المعاملة.');
                return false;
            }
        }

        return true;
    };

    form.querySelectorAll('[data-next-step]').forEach((btn) => {
        btn.addEventListener('click', () => {
            if (! validateStep(currentStep)) {
                return;
            }

            if (currentStep < totalSteps) {
                showStep(currentStep + 1);
            }
        });
    });

    form.querySelectorAll('[data-prev-step]').forEach((btn) => {
        btn.addEventListener('click', () => {
            if (currentStep > 1) {
                showStep(currentStep - 1);
            }
        });
    });

    progressItems.forEach((item) => {
        item.addEventListener('click', () => {
            const target = Number(item.dataset.goStep);

            if (target <= currentStep) {
                showStep(target);
                return;
            }

            for (let s = currentStep; s < target; s++) {
                if (! validateStep(s)) {
                    showStep(s);
                    return;
                }
            }

            showStep(target);
        });
    });

    const updateFolderSelected = (name) => {
        if (! folderSelected || ! folderSelectedName) {
            return;
        }

        if (name) {
            folderSelected.classList.remove('is-empty');
            folderSelectedName.textContent = name;
        } else {
            folderSelected.classList.add('is-empty');
            folderSelectedName.textContent = 'لم يُحدد مجلد';
        }
    };

    const syncInput = () => {
        const dt = new DataTransfer();
        selectedFiles.forEach((f) => dt.items.add(f));
        fileInput.files = dt.files;
    };

    const buildRefFields = (index, fileName) => {
        const title = fileName.replace(/\.[^.]+$/, '');

        const yearField = refConfig.allow_previous_years
            ? `<label>السنة</label><input type="number" name="reference_years[${index}]" value="${refConfig.current_year}" min="1900" max="2100">`
            : `<input type="hidden" name="reference_years[${index}]" value="${refConfig.current_year}">`;

        const monthField = refConfig.month_optional
            ? `<label>الشهر</label><input type="number" name="reference_months[${index}]" min="1" max="12" placeholder="—">`
            : `<label>الشهر *</label><input type="number" name="reference_months[${index}]" min="1" max="12" required>`;

        const originalField = refConfig.original_document_number_optional
            ? `<label>رقم أصلي</label><input type="text" name="original_document_numbers[${index}]" placeholder="—">`
            : `<label>رقم أصلي *</label><input type="text" name="original_document_numbers[${index}]" required>`;

        const op = refConfig.operational_number_enabled
            ? `<label class="txnw-upload-ref-toggle"><input type="checkbox" name="use_operational[${index}]" value="1" data-use-operational data-index="${index}"> رقم تشغيلي</label>`
            : '';

        return `<li class="txnw-upload-item" data-doc-index="${index}">
            <div class="txnw-upload-item-head">
                <span>${fileName}</span>
                <span>${formatSize(selectedFiles[index]?.size || 0)}</span>
                <button type="button" data-remove-index="${index}" aria-label="حذف">&times;</button>
            </div>
            <div class="txnw-upload-item-body">
                <div><label>العنوان</label><input type="text" name="titles[${index}]" value="${title}"></div>
                ${op}
                <div class="txnw-upload-ref-official" data-ref-official="${index}">
                    <div><label>الرقم الإشاري *</label><input type="text" name="reference_numbers[${index}]" placeholder="أدخل الرقم"></div>
                    ${yearField}${monthField}${originalField}
                </div>
            </div>
        </li>`;
    };

    const bindRefToggles = () => {
        fileList?.querySelectorAll('[data-use-operational]').forEach((cb) => {
            cb.addEventListener('change', () => {
                const block = fileList.querySelector(`[data-ref-official="${cb.dataset.index}"]`);
                block?.classList.toggle('is-hidden', cb.checked);
            });
        });
    };

    const renderList = () => {
        if (! fileList) {
            return;
        }

        fileList.innerHTML = '';
        selectedFiles.forEach((file, i) => fileList.insertAdjacentHTML('beforeend', buildRefFields(i, file.name)));

        fileList.querySelectorAll('[data-remove-index]').forEach((btn) => {
            btn.addEventListener('click', () => {
                selectedFiles.splice(Number(btn.dataset.removeIndex), 1);
                renderList();
                syncInput();

                if (selectedFiles.length === 0) {
                    queue?.classList.add('is-hidden');
                    content?.classList.remove('is-hidden');
                }
            });
        });

        bindRefToggles();
    };

    const addFiles = (files) => {
        const incoming = Array.from(files).filter(isAllowed);

        if (! incoming.length) {
            return;
        }

        selectedFiles = [...selectedFiles, ...incoming];
        renderList();
        syncInput();
        content?.classList.add('is-hidden');
        queue?.classList.remove('is-hidden');
    };

    browseBtn?.addEventListener('click', () => fileInput?.click());
    fileInput?.addEventListener('change', () => fileInput.files?.length && addFiles(fileInput.files));

    ['dragenter', 'dragover'].forEach((e) => dropzone?.addEventListener(e, (ev) => { ev.preventDefault(); dropzone.classList.add('is-dragover'); }));
    ['dragleave', 'drop'].forEach((e) => dropzone?.addEventListener(e, (ev) => { ev.preventDefault(); dropzone.classList.remove('is-dragover'); }));
    dropzone?.addEventListener('drop', (ev) => ev.dataTransfer?.files?.length && addFiles(ev.dataTransfer.files));

    const filterFolders = () => {
        const deptId = departmentSelect?.value;

        folderPicker?.querySelectorAll('[data-folder-node]').forEach((node) => {
            node.classList.toggle('is-hidden', deptId && node.dataset.department !== deptId);
        });

        if (folderPicker?.querySelector('[data-folder-select].is-selected')?.closest('[data-folder-node]')?.classList.contains('is-hidden')) {
            folderInput.value = '';
            folderPicker.querySelectorAll('[data-folder-select]').forEach((b) => b.classList.remove('is-selected'));
            updateFolderSelected(null);
        }
    };

    folderPicker?.querySelectorAll('[data-folder-select]').forEach((btn) => {
        btn.addEventListener('click', () => {
            const node = btn.closest('[data-folder-node]');

            if (! node || node.classList.contains('is-hidden')) {
                return;
            }

            folderInput.value = node.dataset.folderId;
            folderPicker.querySelectorAll('[data-folder-select]').forEach((b) => {
                b.classList.remove('is-selected');
                b.setAttribute('aria-pressed', 'false');
            });
            btn.classList.add('is-selected');
            btn.setAttribute('aria-pressed', 'true');
            updateFolderSelected(node.dataset.folderName);
            btn.closest('.txnw-box')?.classList.remove('txnw-box--error');
        });
    });

    folderSearch?.addEventListener('input', () => {
        const q = folderSearch.value.trim().toLowerCase();
        const deptId = departmentSelect?.value;

        folderPicker?.querySelectorAll('[data-folder-node]').forEach((node) => {
            const match = ! q || (node.dataset.folderName || '').toLowerCase().includes(q);
            const dept = ! deptId || node.dataset.department === deptId;
            node.classList.toggle('is-hidden', ! (match && dept));
        });
    });

    departmentSelect?.addEventListener('change', filterFolders);
    filterFolders();

    const init = folderPicker?.querySelector('[data-folder-select].is-selected')?.closest('[data-folder-node]');

    if (init) {
        updateFolderSelected(init.dataset.folderName);
    }

    const initialStep = Number(form.dataset.initialStep) || 1;

    if (initialStep > 1) {
        showStep(initialStep);
    }
});
