document.addEventListener('DOMContentLoaded', () => {
    const dropzone = document.querySelector('[data-txn-dropzone]');

    if (! dropzone) {
        return;
    }

    const i18n = JSON.parse(dropzone.dataset.txnI18n || '{}');
    const fileInput = dropzone.querySelector('[data-txn-file-input]');
    const browseBtn = dropzone.querySelector('[data-txn-browse]');
    const content = dropzone.querySelector('[data-txn-dropzone-content]');
    const queue = dropzone.querySelector('[data-txn-queue]');
    const fileList = dropzone.querySelector('[data-txn-file-list]');
    const uploadBtn = dropzone.querySelector('[data-txn-upload-btn]');

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

    const syncInput = () => {
        const dataTransfer = new DataTransfer();
        selectedFiles.forEach((file) => dataTransfer.items.add(file));
        fileInput.files = dataTransfer.files;
        uploadBtn.disabled = selectedFiles.length === 0;
    };

    const renderList = () => {
        fileList.innerHTML = '';

        selectedFiles.forEach((file, index) => {
            const item = document.createElement('li');
            item.className = 'txn-dropzone-file';
            item.innerHTML = `
                <span class="txn-dropzone-file-name">${file.name}</span>
                <span class="txn-dropzone-file-size">${formatSize(file.size)}</span>
                <button type="button" class="txn-dropzone-file-remove" aria-label="${i18n.remove || 'Remove'}">&times;</button>
            `;

            item.querySelector('.txn-dropzone-file-remove')?.addEventListener('click', () => {
                selectedFiles.splice(index, 1);
                renderList();
                syncInput();

                if (selectedFiles.length === 0) {
                    queue.classList.add('is-hidden');
                    content.classList.remove('is-hidden');
                }
            });

            fileList.appendChild(item);
        });
    };

    const addFiles = (files) => {
        const incoming = Array.from(files).filter(isAllowed);
        const rejected = Array.from(files).length - incoming.length;

        if (incoming.length === 0) {
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
        dropzone.addEventListener(eventName, (event) => {
            event.preventDefault();
            dropzone.classList.add('is-dragover');
        });
    });

    ['dragleave', 'drop'].forEach((eventName) => {
        dropzone.addEventListener(eventName, (event) => {
            event.preventDefault();
            dropzone.classList.remove('is-dragover');
        });
    });

    dropzone.addEventListener('drop', (event) => {
        const files = event.dataTransfer?.files;

        if (files?.length) {
            addFiles(files);
        }
    });
});
