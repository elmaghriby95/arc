document.addEventListener('DOMContentLoaded', () => {
    const form = document.querySelector('[data-txn-create-form]');

    if (! form) {
        return;
    }

    const refConfig = JSON.parse(form.dataset.refConfig || '{}');
    const i18n = JSON.parse(form.dataset.txnI18n || '{}');
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
    const scanBtn = dropzone?.querySelector('[data-txn-scan]');
    const content = dropzone?.querySelector('[data-txn-dropzone-content]');
    const queue = dropzone?.querySelector('[data-txn-queue]');
    const fileList = dropzone?.querySelector('[data-txn-file-list]');
    const submitBtn = form.querySelector('[data-txn-submit]');
    const uploadProgress = form.querySelector('[data-txn-upload-progress]');
    const progressFill = uploadProgress?.querySelector('[data-txn-upload-progress-fill]');
    const progressStatus = uploadProgress?.querySelector('[data-txn-upload-progress-status]');
    const progressPct = uploadProgress?.querySelector('[data-txn-upload-progress-pct]');

    /** @type {File[]} */
    let selectedFiles = [];
    let isUploading = false;
    const submitInitiallyDisabled = submitBtn?.disabled ?? false;

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
                alert(i18n.select_folder_required || 'Please select a folder.');
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
            folderSelectedName.textContent = i18n.no_folder_selected || 'No folder selected';
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
            ? `<label>${i18n.field_year || 'Year'}</label><input type="number" name="reference_years[${index}]" value="${refConfig.current_year}" min="1900" max="2100">`
            : `<input type="hidden" name="reference_years[${index}]" value="${refConfig.current_year}">`;

        const monthField = refConfig.month_optional
            ? `<label>${i18n.field_month || 'Month'}</label><input type="number" name="reference_months[${index}]" min="1" max="12" placeholder="—">`
            : `<label>${i18n.field_month_required || 'Month *'}</label><input type="number" name="reference_months[${index}]" min="1" max="12" required>`;

        const originalField = refConfig.original_document_number_optional
            ? `<label>${i18n.field_original || 'Original number'}</label><input type="text" name="original_document_numbers[${index}]" placeholder="—">`
            : `<label>${i18n.field_original_required || 'Original number *'}</label><input type="text" name="original_document_numbers[${index}]" required>`;

        const op = refConfig.operational_number_enabled
            ? `<label class="txnw-upload-ref-toggle"><input type="checkbox" name="use_operational[${index}]" value="1" data-use-operational data-index="${index}"> ${i18n.field_operational || 'Operational number'}</label>`
            : '';

        return `<li class="txnw-upload-item" data-doc-index="${index}">
            <div class="txnw-upload-item-head">
                <span>${fileName}</span>
                <span>${formatSize(selectedFiles[index]?.size || 0)}</span>
                <button type="button" data-remove-index="${index}" aria-label="${i18n.delete || 'Delete'}">&times;</button>
            </div>
            <div class="txnw-upload-item-body">
                <div><label>${i18n.field_title || 'Title'}</label><input type="text" name="titles[${index}]" value="${title}"></div>
                ${op}
                <div class="txnw-upload-ref-official" data-ref-official="${index}">
                    <div><label>${i18n.field_reference_number || 'Reference number *'}</label><input type="text" name="reference_numbers[${index}]" placeholder="${i18n.field_reference_placeholder || 'Enter number'}"></div>
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
        const rejected = Array.from(files).length - incoming.length;

        if (! incoming.length) {
            if (rejected > 0) {
                alert(i18n.unsupported_file_type || 'Unsupported file type.');
            }

            return;
        }

        if (rejected > 0) {
            alert((i18n.files_rejected || ':count file(s) ignored.').replace(':count', rejected));
        }

        selectedFiles = [...selectedFiles, ...incoming];
        renderList();
        syncInput();
        content?.classList.add('is-hidden');
        queue?.classList.remove('is-hidden');
    };

    browseBtn?.addEventListener('click', () => fileInput?.click());
    fileInput?.addEventListener('change', () => fileInput.files?.length && addFiles(fileInput.files));

    window.ArcScan?.bindButton(scanBtn, {
        onSuccess: (file) => addFiles([file]),
        onError: (error) => {
            alert(
                error?.message
                    || 'تعذّر المسح. شغّل ARC Scan Agent على جهازك (start-windows.bat أو install-ubuntu.sh).',
            );
        },
    });

    ['dragenter', 'dragover'].forEach((e) => dropzone?.addEventListener(e, (ev) => { ev.preventDefault(); dropzone.classList.add('is-dragover'); }));
    ['dragleave', 'drop'].forEach((e) => dropzone?.addEventListener(e, (ev) => { ev.preventDefault(); dropzone.classList.remove('is-dragover'); }));
    dropzone?.addEventListener('drop', (ev) => ev.dataTransfer?.files?.length && addFiles(ev.dataTransfer.files));

    const filterFolders = () => {
        const deptId = departmentSelect?.value;

        folderPicker?.querySelectorAll('[data-folder-node]').forEach((node) => {
            const folderDept = node.dataset.department || '';
            const matches = ! deptId || (folderDept !== '' && folderDept === deptId);
            node.classList.toggle('is-hidden', ! matches);
        });

        const selectedNode = folderPicker?.querySelector('[data-folder-select].is-selected')?.closest('[data-folder-node]');

        if (selectedNode?.classList.contains('is-hidden')) {
            folderInput.value = '';
            folderPicker.querySelectorAll('[data-folder-select]').forEach((b) => {
                b.classList.remove('is-selected');
                b.setAttribute('aria-pressed', 'false');
            });
            updateFolderSelected(null);
        }
    };

    const syncDepartmentFromFolder = (node) => {
        const folderDeptId = node?.dataset.department;

        if (! folderDeptId || ! departmentSelect) {
            return;
        }

        if (departmentSelect.value !== folderDeptId) {
            departmentSelect.value = folderDeptId;
            filterFolders();
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
            syncDepartmentFromFolder(node);
            updateFolderSelected(node.dataset.folderName);
            btn.closest('.txnw-box')?.classList.remove('txnw-box--error');
        });
    });

    folderSearch?.addEventListener('input', () => {
        const q = folderSearch.value.trim().toLowerCase();
        const deptId = departmentSelect?.value;

        folderPicker?.querySelectorAll('[data-folder-node]').forEach((node) => {
            const name = (node.dataset.folderName || '').toLowerCase();
            const folderDept = node.dataset.department || '';
            const searchMatch = ! q || name.includes(q);
            const deptMatch = ! deptId || (folderDept !== '' && folderDept === deptId);
            node.classList.toggle('is-hidden', ! (searchMatch && deptMatch));
        });
    });

    departmentSelect?.addEventListener('change', filterFolders);
    filterFolders();

    const init = folderPicker?.querySelector('[data-folder-select].is-selected')?.closest('[data-folder-node]');

    if (init) {
        syncDepartmentFromFolder(init);
        updateFolderSelected(init.dataset.folderName);
    }

    const setFormLocked = (locked) => {
        isUploading = locked;
        form.classList.toggle('is-uploading', locked);
        submitBtn && (submitBtn.disabled = locked || submitInitiallyDisabled);
        form.querySelectorAll('[data-next-step], [data-prev-step], [data-go-step], [data-txn-browse], [data-txn-scan], [data-remove-index]').forEach((el) => {
            el.disabled = locked;
        });
    };

    const updateUploadProgress = (loaded, total) => {
        if (! uploadProgress) {
            return;
        }

        const pct = total > 0 ? Math.min(100, Math.round((loaded / total) * 100)) : 0;

        if (progressFill) {
            progressFill.style.width = `${pct}%`;
        }

        if (progressPct) {
            progressPct.textContent = `${pct}%`;
        }

        if (progressStatus) {
            const loadedStr = formatSize(loaded);
            const totalStr = formatSize(total);
            const template = i18n.upload_progress || 'Uploading… :pct% (:loaded / :total)';

            progressStatus.textContent = template
                .replace(':pct', String(pct))
                .replace(':loaded', loadedStr)
                .replace(':total', totalStr);
        }
    };

    const resetUploadProgress = () => {
        uploadProgress?.classList.add('is-hidden');

        if (progressFill) {
            progressFill.style.width = '0%';
        }

        if (progressPct) {
            progressPct.textContent = '0%';
        }

        if (progressStatus) {
            progressStatus.textContent = i18n.upload_preparing || 'Preparing upload…';
        }
    };

    const showValidationErrors = (errors) => {
        const items = Object.values(errors || {}).flat();

        if (! items.length) {
            return;
        }

        const wrap = form.closest('.txnw-wrap');
        let alert = wrap?.querySelector('.txnw-alert--error');

        if (! alert && wrap) {
            alert = document.createElement('div');
            alert.className = 'txnw-alert txnw-alert--error';
            alert.setAttribute('role', 'alert');
            wrap.insertBefore(alert, form);
        }

        if (alert) {
            alert.innerHTML = `<strong>${i18n.validation_heading || 'Please correct the following errors:'}</strong><ul>${items.map((e) => `<li>${e}</li>`).join('')}</ul>`;
        }

        window.scrollTo({ top: 0, behavior: 'smooth' });
    };

    const submitWithProgress = () => {
        const formData = new FormData(form);
        const xhr = new XMLHttpRequest();

        uploadProgress?.classList.remove('is-hidden');
        updateUploadProgress(0, selectedFiles.reduce((sum, file) => sum + file.size, 0));

        xhr.open('POST', form.action);
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        xhr.setRequestHeader('Accept', 'application/json');

        xhr.upload.addEventListener('progress', (event) => {
            if (event.lengthComputable) {
                updateUploadProgress(event.loaded, event.total);
            }
        });

        xhr.addEventListener('load', () => {
            let data = null;

            try {
                data = JSON.parse(xhr.responseText);
            } catch {
                data = null;
            }

            if (xhr.status === 422) {
                setFormLocked(false);
                resetUploadProgress();
                showValidationErrors(data?.errors);

                return;
            }

            if (xhr.status >= 200 && xhr.status < 300 && data?.redirect) {
                window.location.assign(data.redirect);

                return;
            }

            const responseUrl = xhr.responseURL || '';
            const landedOnCreate = /\/transactions\/create\/?(?:\?|$)/.test(responseUrl);

            if (xhr.status >= 200 && xhr.status < 400 && ! landedOnCreate) {
                window.location.assign(responseUrl);

                return;
            }

            if (landedOnCreate) {
                setFormLocked(false);
                resetUploadProgress();
                window.location.assign(responseUrl);

                return;
            }

            setFormLocked(false);
            resetUploadProgress();
            alert(i18n.upload_failed || 'Upload failed.');
        });

        xhr.addEventListener('error', () => {
            setFormLocked(false);
            resetUploadProgress();
            alert(i18n.upload_failed || 'Upload failed.');
        });

        xhr.send(formData);
    };

    form.addEventListener('submit', (event) => {
        const deptId = departmentSelect?.value;
        const selectedNode = folderPicker?.querySelector('[data-folder-select].is-selected')?.closest('[data-folder-node]');
        const folderDeptId = selectedNode?.dataset.department || '';

        if (deptId && folderDeptId && deptId !== folderDeptId) {
            event.preventDefault();
            showStep(2);
            alert(i18n.folder_unit_mismatch || 'Folder does not match organizational unit.');
            return;
        }

        if (isUploading) {
            event.preventDefault();
            return;
        }

        if (! form.reportValidity()) {
            event.preventDefault();
            return;
        }

        for (let step = 1; step <= totalSteps; step++) {
            if (! validateStep(step)) {
                event.preventDefault();
                showStep(step);
                return;
            }
        }

        if (selectedFiles.length === 0) {
            return;
        }

        event.preventDefault();
        setFormLocked(true);
        submitWithProgress();
    });

    const initialStep = Number(form.dataset.initialStep) || 1;

    if (initialStep > 1) {
        showStep(initialStep);
    }
});
