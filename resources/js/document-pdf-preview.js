/**
 * PDF.js preview with one watermark per page (current viewer).
 * Served assets come from Laravel /assets/pdfjs/* so deploys don't depend on public/vendor.
 */
(function () {
    const boot = () => {
        const root = document.querySelector('[data-doc-pdf-viewer]');
        if (!root) {
            return;
        }

        if (typeof pdfjsLib === 'undefined') {
            const status = root.querySelector('[data-doc-pdf-status]');
            if (status) {
                status.hidden = false;
                status.textContent = root.dataset.pdfError || 'PDF preview failed';
            }
            return;
        }

        pdfjsLib.GlobalWorkerOptions.workerSrc = root.dataset.pdfWorker || '';

        const url = root.dataset.pdfUrl;
        const pagesEl = root.querySelector('[data-doc-pdf-pages]');
        const statusEl = root.querySelector('[data-doc-pdf-status]');
        const zoomInput = document.querySelector('[data-doc-preview-zoom]');
        const heightInput = document.querySelector('[data-doc-preview-height]');
        const zoomOutput = document.querySelector('[data-doc-preview-zoom-value]');
        const heightOutput = document.querySelector('[data-doc-preview-height-value]');
        const resetButton = document.querySelector('[data-doc-preview-reset]');
        const viewportEl = document.querySelector('[data-doc-preview-viewport]');

        let watermark = null;
        try {
            watermark = root.dataset.watermark ? JSON.parse(root.dataset.watermark) : null;
        } catch (e) {
            watermark = null;
        }
        if (!watermark) {
            const jsonEl = document.querySelector('[data-doc-watermark-json]');
            try {
                watermark = jsonEl?.textContent ? JSON.parse(jsonEl.textContent) : null;
            } catch (e) {
                watermark = null;
            }
        }

        let pdfDoc = null;
        let baseScale = 1.15;
        let zoomPercent = 100;
        let renderToken = 0;
        const pageState = new Map();
        const defaults = { zoom: 100, height: 850 };
        const storageKey = 'doc-preview-settings';
        const clamp = (v, min, max) => Math.min(max, Math.max(min, v));

        const setStatus = (text) => {
            if (!statusEl) return;
            statusEl.textContent = text || '';
            statusEl.hidden = !text;
        };

        const drawQrWatermark = async (ctx, width, height) => {
            if (!watermark?.show_qr_code || !watermark.qr_svg) return;

            try {
                const blob = new Blob([String(watermark.qr_svg)], { type: 'image/svg+xml;charset=utf-8' });
                const objectUrl = URL.createObjectURL(blob);
                const image = await new Promise((resolve, reject) => {
                    const img = new Image();
                    img.onload = () => resolve(img);
                    img.onerror = reject;
                    img.src = objectUrl;
                });
                const size = Math.max(40, Math.min(72, Math.min(width, height) * 0.08));
                ctx.save();
                ctx.globalAlpha = 0.95;
                ctx.fillStyle = '#fff';
                ctx.fillRect(width - size - 18, height - size - 18, size + 8, size + 8);
                ctx.drawImage(image, width - size - 14, height - size - 14, size, size);
                ctx.restore();
                URL.revokeObjectURL(objectUrl);
            } catch (e) {
                // QR is optional; text/footer still identify the page.
            }
        };

        const drawWatermark = async (ctx, width, height) => {
            if (!watermark) return;

            const lines = Array.isArray(watermark.center_lines)
                ? watermark.center_lines.filter(Boolean)
                : [];
            const footer = watermark.footer ? String(watermark.footer) : '';
            const showCenter = Boolean(watermark.show_center_text && lines.length);
            const showFooter = Boolean(watermark.show_footer && footer);
            const opacity = Number(watermark.opacity || 0.18);
            const angleDeg = Number(watermark.angle || -45);
            const fontSize = Math.max(14, Number(watermark.font_size || 28) * (width / 800));

            if (showCenter) {
                ctx.save();
                ctx.globalAlpha = Math.max(0.08, Math.min(0.6, opacity));
                ctx.fillStyle = '#3c3c3c';
                ctx.textAlign = 'center';
                ctx.textBaseline = 'middle';
                ctx.font = `bold ${fontSize}px "Segoe UI", Tahoma, "DejaVu Sans", Arial, sans-serif`;
                ctx.translate(width / 2, height / 2);
                ctx.rotate((angleDeg * Math.PI) / 180);
                lines.forEach((line, index) => {
                    const y = (index - (lines.length - 1) / 2) * (fontSize * 1.15);
                    ctx.fillText(String(line), 0, y);
                });
                ctx.restore();
            }

            if (showFooter) {
                ctx.save();
                ctx.globalAlpha = Math.max(0.35, Math.min(0.75, opacity + 0.25));
                ctx.fillStyle = '#282828';
                ctx.textAlign = 'left';
                ctx.textBaseline = 'bottom';
                ctx.font = `${Math.max(10, fontSize * 0.35)}px "Segoe UI", Tahoma, "DejaVu Sans", Arial, sans-serif`;
                ctx.fillText(footer, 12, height - 10, width - 90);
                ctx.restore();
            }

            await drawQrWatermark(ctx, width, height);
        };

        const renderPageInto = async (pageNumber, token) => {
            const state = pageState.get(pageNumber);
            if (!state || state.rendered || state.rendering || token !== renderToken) return;

            state.rendering = true;
            try {
                const page = await pdfDoc.getPage(pageNumber);
                if (token !== renderToken) return;

                const scale = baseScale * (zoomPercent / 100);
                const viewport = page.getViewport({ scale });
                const canvas = document.createElement('canvas');
                canvas.className = 'doc-pdf-page';
                canvas.width = viewport.width;
                canvas.height = viewport.height;

                const ctx = canvas.getContext('2d', { alpha: false });
                await page.render({ canvasContext: ctx, viewport }).promise;
                if (token !== renderToken) return;

                await drawWatermark(ctx, canvas.width, canvas.height);
                if (token !== renderToken) return;

                state.holder.style.width = `${canvas.width}px`;
                state.holder.style.height = `${canvas.height}px`;
                state.holder.innerHTML = '';
                state.holder.appendChild(canvas);
                state.rendered = true;
            } catch (e) {
                state.holder.textContent = String(pageNumber);
            } finally {
                state.rendering = false;
            }
        };

        const ensurePlaceholders = async (token) => {
            pagesEl.innerHTML = '';
            pageState.clear();

            const scale = baseScale * (zoomPercent / 100);
            const firstPage = await pdfDoc.getPage(1);
            const firstViewport = firstPage.getViewport({ scale });

            for (let n = 1; n <= pdfDoc.numPages; n += 1) {
                if (token !== renderToken) return;
                const holder = document.createElement('div');
                holder.className = 'doc-pdf-page-slot';
                holder.dataset.page = String(n);
                holder.style.width = `${Math.floor(firstViewport.width)}px`;
                holder.style.height = `${Math.floor(firstViewport.height)}px`;
                pagesEl.appendChild(holder);
                pageState.set(n, { holder, rendered: false, rendering: false });
            }

            const observer = new IntersectionObserver((entries) => {
                entries.forEach((entry) => {
                    if (!entry.isIntersecting || token !== renderToken) return;
                    renderPageInto(Number(entry.target.dataset.page), token);
                });
            }, { root: viewportEl, rootMargin: '240px 0px', threshold: 0.01 });

            pageState.forEach((state) => observer.observe(state.holder));

            const warm = Math.min(3, pdfDoc.numPages);
            for (let n = 1; n <= warm; n += 1) {
                renderPageInto(n, token);
            }
        };

        const applyHeight = (heightPx) => {
            if (!viewportEl || !heightInput) return;
            const value = clamp(heightPx, Number(heightInput.min), Number(heightInput.max));
            viewportEl.style.height = `${value}px`;
            heightInput.value = String(value);
            if (heightOutput) heightOutput.textContent = `${value}px`;
        };

        const applyZoom = async (zoom) => {
            if (!zoomInput || !pdfDoc) {
                zoomPercent = zoom;
                return;
            }
            zoomPercent = clamp(zoom, Number(zoomInput.min), Number(zoomInput.max));
            zoomInput.value = String(zoomPercent);
            if (zoomOutput) zoomOutput.textContent = `${zoomPercent}%`;
            const token = ++renderToken;
            setStatus('');
            await ensurePlaceholders(token);
        };

        const saveSettings = () => {
            localStorage.setItem(storageKey, JSON.stringify({
                zoom: zoomPercent,
                height: Number(heightInput?.value || defaults.height),
            }));
        };

        const loadSettings = () => {
            let zoom = defaults.zoom;
            let height = defaults.height;
            try {
                const saved = JSON.parse(localStorage.getItem(storageKey) || 'null');
                if (saved && typeof saved.zoom === 'number' && typeof saved.height === 'number') {
                    zoom = saved.zoom;
                    height = saved.height;
                }
            } catch (e) {}
            applyHeight(height);
            return zoom;
        };

        zoomInput?.addEventListener('change', async () => {
            await applyZoom(Number(zoomInput.value));
            saveSettings();
        });
        heightInput?.addEventListener('input', () => {
            applyHeight(Number(heightInput.value));
            saveSettings();
        });
        resetButton?.addEventListener('click', async () => {
            applyHeight(defaults.height);
            await applyZoom(defaults.zoom);
            saveSettings();
        });

        const initialZoom = loadSettings();
        zoomPercent = initialZoom;
        if (zoomInput) zoomInput.value = String(initialZoom);
        if (zoomOutput) zoomOutput.textContent = `${initialZoom}%`;

        setStatus('...');
        pdfjsLib.getDocument({ url, withCredentials: true }).promise
            .then(async (pdf) => {
                pdfDoc = pdf;
                await applyZoom(initialZoom);
            })
            .catch(() => {
                setStatus(root.dataset.pdfError || 'PDF preview failed');
            });
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }
})();
