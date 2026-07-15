/**
 * Seal PDF downloads in the browser so original compressed streams are preserved.
 * Server FPDI rewrite explodes scanned PDFs (e.g. 10MB → 38MB); this keeps ~same size.
 */
(function () {
    const root = document.getElementById('doc-pdf-seal');
    const statusEl = document.querySelector('[data-doc-seal-status]');
    if (!root) return;

    const setStatus = (text) => {
        if (statusEl) statusEl.textContent = text || '';
    };

    const watermark = (() => {
        try {
            return JSON.parse(root.dataset.watermark || 'null');
        } catch (e) {
            return null;
        }
    })();

    const sourceUrl = root.dataset.sourceUrl;
    const filename = root.dataset.filename || 'document-wm.pdf';
    const msgPreparing = root.dataset.msgPreparing || 'Preparing…';
    const msgFailed = root.dataset.msgFailed || 'Failed';
    const msgDone = root.dataset.msgDone || 'Done';

    const canvasToPng = async (canvas) => {
        const blob = await new Promise((resolve) => canvas.toBlob(resolve, 'image/png'));
        if (!blob) throw new Error('PNG encode failed');
        return blob.arrayBuffer();
    };

    const buildCenterStamp = async (wm) => {
        const lines = Array.isArray(wm.center_lines) ? wm.center_lines.filter(Boolean) : [];
        if (!wm.show_center_text || !lines.length) return null;

        const size = 1200;
        const canvas = document.createElement('canvas');
        canvas.width = size;
        canvas.height = size;
        const ctx = canvas.getContext('2d');
        if (!ctx) throw new Error('Canvas unavailable');

        const opacity = Math.max(0.05, Math.min(0.6, Number(wm.opacity || 0.18)));
        const angle = (Number(wm.angle || -45) * Math.PI) / 180;
        const fontSize = Math.max(22, Number(wm.font_size || 28) * 1.8);

        ctx.clearRect(0, 0, size, size);
        ctx.save();
        ctx.translate(size / 2, size / 2);
        ctx.rotate(angle);
        ctx.globalAlpha = opacity;
        ctx.fillStyle = '#3c3c3c';
        ctx.textAlign = 'center';
        ctx.textBaseline = 'middle';
        ctx.font = `bold ${fontSize}px "Segoe UI", Tahoma, "DejaVu Sans", Arial, sans-serif`;
        lines.forEach((line, index) => {
            const y = (index - (lines.length - 1) / 2) * (fontSize * 1.25);
            ctx.fillText(String(line), 0, y);
        });
        ctx.restore();

        return canvasToPng(canvas);
    };

    const buildFooterStamp = async (wm) => {
        if (!wm.show_footer || !wm.footer) return null;

        const canvas = document.createElement('canvas');
        canvas.width = 1600;
        canvas.height = 64;
        const ctx = canvas.getContext('2d');
        if (!ctx) throw new Error('Canvas unavailable');

        const opacity = Math.max(0.35, Math.min(0.75, Number(wm.opacity || 0.18) + 0.25));
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        ctx.globalAlpha = opacity;
        ctx.fillStyle = '#282828';
        ctx.textAlign = 'left';
        ctx.textBaseline = 'middle';
        ctx.font = '28px "Segoe UI", Tahoma, "DejaVu Sans", Arial, sans-serif';
        ctx.fillText(String(wm.footer), 8, canvas.height / 2);

        return canvasToPng(canvas);
    };

    const buildQrStamp = async (wm) => {
        if (!wm.show_qr_code || !wm.qr_svg) return null;

        const svg = String(wm.qr_svg);
        const blob = new Blob([svg], { type: 'image/svg+xml;charset=utf-8' });
        const url = URL.createObjectURL(blob);
        try {
            const img = await new Promise((resolve, reject) => {
                const image = new Image();
                image.onload = () => resolve(image);
                image.onerror = reject;
                image.src = url;
            });
            const canvas = document.createElement('canvas');
            canvas.width = 128;
            canvas.height = 128;
            const ctx = canvas.getContext('2d');
            if (!ctx) return null;
            ctx.fillStyle = '#ffffff';
            ctx.fillRect(0, 0, 128, 128);
            ctx.drawImage(img, 8, 8, 112, 112);
            return canvasToPng(canvas);
        } finally {
            URL.revokeObjectURL(url);
        }
    };

    const triggerDownload = (bytes, name) => {
        const blob = new Blob([bytes], { type: 'application/pdf' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = name;
        document.body.appendChild(a);
        a.click();
        a.remove();
        setTimeout(() => URL.revokeObjectURL(url), 2000);
    };

    const run = async () => {
        setStatus(msgPreparing);

        if (typeof PDFLib === 'undefined') {
            setStatus(msgFailed);
            return;
        }

        if (!sourceUrl || !watermark) {
            setStatus(msgFailed);
            return;
        }

        try {
            const response = await fetch(sourceUrl, {
                credentials: 'same-origin',
                headers: { Accept: 'application/pdf' },
            });
            if (!response.ok) throw new Error('source ' + response.status);

            const sourceBytes = await response.arrayBuffer();
            const pdfDoc = await PDFLib.PDFDocument.load(sourceBytes, {
                ignoreEncryption: true,
            });

            const [centerBytes, footerBytes, qrBytes] = await Promise.all([
                buildCenterStamp(watermark),
                buildFooterStamp(watermark),
                buildQrStamp(watermark),
            ]);

            const centerImage = centerBytes ? await pdfDoc.embedPng(centerBytes) : null;
            const footerImage = footerBytes ? await pdfDoc.embedPng(footerBytes) : null;
            const qrImage = qrBytes ? await pdfDoc.embedPng(qrBytes) : null;

            pdfDoc.getPages().forEach((page) => {
                const { width, height } = page.getSize();

                if (centerImage) {
                    const size = Math.min(width, height) * 0.82;
                    page.drawImage(centerImage, {
                        x: (width - size) / 2,
                        y: (height - size) / 2,
                        width: size,
                        height: size,
                    });
                }

                if (footerImage) {
                    const fw = Math.min(width - 24, width * 0.9);
                    const fh = fw * (footerImage.height / footerImage.width);
                    page.drawImage(footerImage, {
                        x: 12,
                        y: 10,
                        width: fw,
                        height: fh,
                    });
                }

                if (qrImage) {
                    const q = Math.min(42, width * 0.08);
                    page.drawImage(qrImage, {
                        x: width - q - 12,
                        y: 10,
                        width: q,
                        height: q,
                    });
                }
            });

            const out = await pdfDoc.save({ useObjectStreams: true });
            triggerDownload(out, filename);
            setStatus(msgDone);
        } catch (err) {
            console.error(err);
            setStatus(msgFailed);
        }
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', run);
    } else {
        run();
    }
})();
