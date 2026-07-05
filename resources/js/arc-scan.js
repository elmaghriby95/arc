(() => {
    const DEFAULT_URL = 'http://127.0.0.1:8765';

    const resolveAgentUrl = (element) => {
        const host = element?.closest('[data-scan-agent-url]') ?? document.querySelector('[data-scan-agent-url]');
        const url = host?.dataset.scanAgentUrl?.trim() || DEFAULT_URL;

        return url.replace(/\/$/, '');
    };

    const scanFileName = (prefix = 'scan') => {
        const timestamp = new Date().toISOString().slice(0, 19).replace(/[:T]/g, '-');

        return `${prefix}-${timestamp}.pdf`;
    };

    const blobToFile = (blob, prefix = 'scan') => new File(
        [blob],
        scanFileName(prefix),
        { type: blob.type || 'application/pdf' },
    );

    const scan = async (options = {}) => {
        const agentUrl = (options.agentUrl || DEFAULT_URL).replace(/\/$/, '');
        const response = await fetch(`${agentUrl}/scan`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                format: 'pdf',
                resolution: options.resolution ?? 300,
                mode: options.mode ?? 'Gray',
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
    };

    const bindButton = (button, callbacks = {}) => {
        if (! button || button.dataset.arcScanBound === '1') {
            return;
        }

        button.dataset.arcScanBound = '1';

        const idleLabel = button.dataset.scanIdleLabel || button.textContent.trim();
        const scanningLabel = button.dataset.scanningLabel || 'Scanning...';

        button.addEventListener('click', async () => {
            button.disabled = true;
            button.textContent = scanningLabel;
            callbacks.onStateChange?.('scanning');

            try {
                const agentUrl = resolveAgentUrl(button);
                const blob = await scan({ agentUrl });
                const file = blobToFile(blob);
                callbacks.onSuccess?.(file);
            } catch (error) {
                callbacks.onError?.(error);
            } finally {
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
