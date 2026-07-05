(() => {
    const DEFAULT_URL = 'http://127.0.0.1:8765';
    const SCAN_TIMEOUT_MS = 600000;

    const resolveAgentUrl = (element) => {
        const host = element?.closest('[data-scan-agent-url]') ?? document.querySelector('[data-scan-agent-url]');
        const url = host?.dataset.scanAgentUrl?.trim() || DEFAULT_URL;

        return url.replace(/\/$/, '');
    };

    const scanFileName = (prefix = 'scan', mimeType = 'application/pdf') => {
        const timestamp = new Date().toISOString().slice(0, 19).replace(/[:T]/g, '-');
        const extension = mimeType.includes('jpeg') || mimeType.includes('jpg')
            ? 'jpg'
            : mimeType.includes('png')
                ? 'png'
                : 'pdf';

        return `${prefix}-${timestamp}.${extension}`;
    };

    const blobToFile = (blob, prefix = 'scan') => new File(
        [blob],
        scanFileName(prefix, blob.type || 'application/pdf'),
        { type: blob.type || 'application/pdf' },
    );

    const normalizeError = (error) => {
        if (error?.name === 'AbortError') {
            return new Error('انتهت مهلة المسح. جرّب بعدد أوراق أقل.');
        }

        if (error instanceof TypeError) {
            return new Error('تعذّر الاتصال بـ ARC Scan Agent. شغّل scan-agent/start-windows.bat.');
        }

        return error instanceof Error ? error : new Error('تعذّر المسح.');
    };

    const scan = async (options = {}) => {
        const agentUrl = (options.agentUrl || DEFAULT_URL).replace(/\/$/, '');
        const controller = new AbortController();
        const timeoutId = window.setTimeout(() => controller.abort(), options.timeoutMs ?? SCAN_TIMEOUT_MS);

        try {
            const response = await fetch(`${agentUrl}/scan`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                signal: controller.signal,
                body: JSON.stringify({
                    format: 'pdf',
                    resolution: options.resolution ?? 120,
                    quality: options.quality ?? 48,
                    mode: options.mode ?? 'Gray',
                    source: options.source ?? 'auto',
                    device: options.device ?? null,
                }),
            });

            if (! response.ok) {
                let message = 'Scan failed.';

                try {
                    const payload = await response.json();
                    message = payload.error || message;
                } catch {
                    // ignore JSON parse errors
                }

                throw new Error(message);
            }

            return response.blob();
        } finally {
            window.clearTimeout(timeoutId);
        }
    };

    const bindButton = (button, callbacks = {}) => {
        if (! button || button.dataset.arcScanBound === '1') {
            return;
        }

        button.dataset.arcScanBound = '1';

        const idleLabel = button.dataset.scanIdleLabel || button.textContent.trim();
        const scanningLabel = button.dataset.scanningLabel || 'Scanning...';
        const processingLabel = button.dataset.scanProcessingLabel || scanningLabel;
        let processingTimer = null;

        button.addEventListener('click', async () => {
            button.disabled = true;
            button.textContent = scanningLabel;
            callbacks.onStateChange?.('scanning');

            processingTimer = window.setTimeout(() => {
                button.textContent = processingLabel;
            }, 4000);

            try {
                const agentUrl = resolveAgentUrl(button);
                const blob = await scan({ agentUrl });
                const file = blobToFile(blob);
                callbacks.onSuccess?.(file);
            } catch (error) {
                callbacks.onError?.(normalizeError(error));
            } finally {
                window.clearTimeout(processingTimer);
                button.disabled = false;
                button.textContent = idleLabel;
                callbacks.onStateChange?.('idle');
            }
        });
    };

    window.ArcScan = {
        resolveAgentUrl,
        scan,
        blobToFile,
        bindButton,
    };
})();
