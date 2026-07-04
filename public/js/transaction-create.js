document.addEventListener('DOMContentLoaded', () => {
    const form = document.querySelector('[data-txn-create-form]');

    if (! form) {
        return;
    }

    const refConfig = JSON.parse(form.dataset.refConfig || '{}');
    const departmentSelect = document.getElementById('department_id');
    const transactionTypeSelect = document.getElementById('transaction_type_id');
    const folderPicker = document.querySelector('[data-folder-picker]');
    const folderInput = folderPicker?.querySelector('[data-folder-input]');
    const folderHint = folderPicker?.querySelector('[data-folder-hint]');
    const folderSearch = folderPicker?.querySelector('[data-folder-search]');
    const dropzone = form.querySelector('[data-txn-create-dropzone]');
    const fileInput = dropzone?.querySelector('[data-txn-file-input]');
    const browseBtn = dropzone?.querySelector('[data-txn-browse]');
    const content = dropzone?.querySelector('[data-txn-dropzone-content]');
    const queue = dropzone?.querySelector('[data-txn-queue]');
    const fileList = dropzone?.querySelector('[data-txn-file-list]');

    /** @type {File[]} */
    let selectedFiles = [];

    const allowedExtensions = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp'];

    const isAllowed = (file) => {
        const ext = file.name.split('.').pop()?.toLowerCase() ?? '';

        return allowedExtensions.includes(ext);
    };

    const formatSize = (bytes) => {
        if (bytes >= 1048576) {
            return `${(bytes / 1048576).toFixed(1)} MB`;
        }

        return `${(bytes / 1024).toFixed(1)} KB`;
    };

    const selectedTypeCode = () => {
        const option = transactionTypeSelect?.selectedOptions[0];

        return option?.dataset.typeCode || 'DOC';
    };

    const syncInput = () => {
        const dataTransfer = new DataTransfer();
        selectedFiles.forEach((file) => dataTransfer.items.add(file));
        fileInput.files = dataTransfer.files;
    };

    const buildRefFields = (index, fileName) => {
        const title = fileName.replace(/\.[^.]+$/, '');
        const yearField = refConfig.allow_previous_years
            ? `<div class="txn-doc-field">
                    <label>السنة</label>
                    <input type="number" name="reference_years[${index}]" class="form-control form-control--compact" value="${refConfig.current_year}" min="1900" max="2100">
               </div>`
            : `<input type="hidden" name="reference_years[${index}]" value="${refConfig.current_year}">`;

        const monthField = refConfig.month_optional
            ? `<div class="txn-doc-field">
                    <label>الشهر (اختياري)</label>
                    <input type="number" name="reference_months[${index}]" class="form-control form-control--compact" min="1" max="12" placeholder="—">
               </div>`
            : `<div class="txn-doc-field">
                    <label>الشهر</label>
                    <input type="number" name="reference_months[${index}]" class="form-control form-control--compact" min="1" max="12" required>
               </div>`;

        const originalField = refConfig.original_document_number_optional
            ? `<div class="txn-doc-field">
                    <label>رقم المستند الأصلي (اختياري)</label>
                    <input type="text" name="original_document_numbers[${index}]" class="form-control form-control--compact" placeholder="—">
               </div>`
            : `<div class="txn-doc-field">
                    <label>رقم المستند الأصلي</label>
                    <input type="text" name="original_document_numbers[${index}]" class="form-control form-control--compact" required>
               </div>`;

        const operationalOption = refConfig.operational_number_enabled
            ? `<label class="txn-doc-ref-toggle">
                    <input type="checkbox" name="use_operational[${index}]" value="1" data-use-operational data-index="${index}">
                    <span>استخدام الرقم التشغيلي</span>
               </label>
               <p class="form-hint txn-doc-ref-preview is-hidden" data-ref-preview="${index}">
                    معاينة: ${refConfig.operational_number_preview}
               </p>`
            : '';

        return `
            <li class="txn-doc-upload-item" data-doc-index="${index}">
                <div class="txn-doc-upload-head">
                    <strong class="txn-doc-upload-name">${fileName}</strong>
                    <span class="txn-doc-upload-size">${formatSize(selectedFiles[index]?.size || 0)}</span>
                    <button type="button" class="txn-dropzone-file-remove" data-remove-index="${index}" aria-label="إزالة">&times;</button>
                </div>
                <div class="txn-doc-upload-fields">
                    <div class="txn-doc-field">
                        <label>عنوان المستند</label>
                        <input type="text" name="titles[${index}]" class="form-control form-control--compact" value="${title}">
                    </div>
                    ${operationalOption}
                    <div class="txn-doc-ref-official" data-ref-official="${index}">
                        <div class="txn-doc-field">
                            <label>الرقم الإشاري</label>
                            <input type="text" name="reference_numbers[${index}]" class="form-control form-control--compact" placeholder="أدخل الرقم كما هو مكتوب">
                        </div>
                        ${yearField}
                        ${monthField}
                        ${originalField}
                    </div>
                </div>
            </li>
        `;
    };

    const bindRefToggles = () => {
        fileList?.querySelectorAll('[data-use-operational]').forEach((checkbox) => {
            checkbox.addEventListener('change', () => {
                const index = checkbox.dataset.index;
                const official = fileList.querySelector(`[data-ref-official="${index}"]`);
                const preview = fileList.querySelector(`[data-ref-preview="${index}"]`);

                if (checkbox.checked) {
                    official?.classList.add('is-hidden');
                    preview?.classList.remove('is-hidden');
                } else {
                    official?.classList.remove('is-hidden');
                    preview?.classList.add('is-hidden');
                }
            });
        });
    };

    const renderList = () => {
        fileList.innerHTML = '';

        selectedFiles.forEach((file, index) => {
            fileList.insertAdjacentHTML('beforeend', buildRefFields(index, file.name));
        });

        fileList.querySelectorAll('[data-remove-index]').forEach((button) => {
            button.addEventListener('click', () => {
                const removeIndex = Number(button.dataset.removeIndex);
                selectedFiles.splice(removeIndex, 1);
                renderList();
                syncInput();

                if (selectedFiles.length === 0) {
                    queue.classList.add('is-hidden');
                    content.classList.remove('is-hidden');
                }
            });
        });

        bindRefToggles();
    };

    const addFiles = (files) => {
        const incoming = Array.from(files).filter(isAllowed);

        if (incoming.length === 0) {
            return;
        }

        selectedFiles = [...selectedFiles, ...incoming];
        renderList();
        syncInput();
        content.classList.add('is-hidden');
        queue.classList.remove('is-hidden');
    };

    browseBtn?.addEventListener('click', () => fileInput.click());

    fileInput?.addEventListener('change', () => {
        if (fileInput.files?.length) {
            addFiles(fileInput.files);
        }
    });

    ['dragenter', 'dragover'].forEach((eventName) => {
        dropzone?.addEventListener(eventName, (event) => {
            event.preventDefault();
            dropzone.classList.add('is-dragover');
        });
    });

    ['dragleave', 'drop'].forEach((eventName) => {
        dropzone?.addEventListener(eventName, (event) => {
            event.preventDefault();
            dropzone.classList.remove('is-dragover');
        });
    });

    dropzone?.addEventListener('drop', (event) => {
        const files = event.dataTransfer?.files;

        if (files?.length) {
            addFiles(files);
        }
    });

    const filterFoldersByDepartment = () => {
        const departmentId = departmentSelect?.value;

        folderPicker?.querySelectorAll('[data-folder-node]').forEach((node) => {
            const matches = ! departmentId || node.dataset.department === departmentId;
            node.classList.toggle('is-hidden', ! matches);
        });

        const selectedNode = folderPicker?.querySelector('[data-folder-select].is-selected');

        if (selectedNode?.closest('[data-folder-node]')?.classList.contains('is-hidden')) {
            folderInput.value = '';
            folderPicker.querySelectorAll('[data-folder-select]').forEach((btn) => {
                btn.classList.remove('is-selected');
                btn.setAttribute('aria-selected', 'false');
            });
            if (folderHint) {
                folderHint.textContent = 'اختر مجلداً لحفظ المعاملة ومستنداتها.';
            }
        }
    };

    folderPicker?.querySelectorAll('[data-folder-select]').forEach((button) => {
        button.addEventListener('click', () => {
            const node = button.closest('[data-folder-node]');

            if (! node || node.classList.contains('is-hidden')) {
                return;
            }

            folderInput.value = node.dataset.folderId;

            folderPicker.querySelectorAll('[data-folder-select]').forEach((btn) => {
                btn.classList.remove('is-selected');
                btn.setAttribute('aria-selected', 'false');
            });

            button.classList.add('is-selected');
            button.setAttribute('aria-selected', 'true');

            if (folderHint) {
                folderHint.innerHTML = `المحدد: <strong>${node.dataset.folderName}</strong>`;
            }
        });
    });

    folderSearch?.addEventListener('input', () => {
        const query = folderSearch.value.trim().toLowerCase();

        folderPicker?.querySelectorAll('[data-folder-node]').forEach((node) => {
            const name = (node.dataset.folderName || '').toLowerCase();
            const departmentId = departmentSelect?.value;
            const departmentMatch = ! departmentId || node.dataset.department === departmentId;
            const searchMatch = query === '' || name.includes(query);

            node.classList.toggle('is-hidden', ! (departmentMatch && searchMatch));
        });
    });

    departmentSelect?.addEventListener('change', filterFoldersByDepartment);
    filterFoldersByDepartment();

    transactionTypeSelect?.addEventListener('change', () => {
        selectedTypeCode();
    });
});
