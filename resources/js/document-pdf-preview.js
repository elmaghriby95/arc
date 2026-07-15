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

        const createWatermarkOverlay = () => {
            if (!watermark) return null;

            const lines = Array.isArray(watermark.center_lines)
                ? watermark.center_lines.filter(Boolean)
                : [];
            const footer = watermark.footer ? String(watermark.footer) : '';
            const showCenter = Boolean(watermark.show_center_text && lines.length);
            const showFooter = Boolean(watermark.show_footer && footer);
            const showQr = Boolean(watermark.show_qr_code && watermark.qr_svg);

            if (!showCenter && !showFooter && !showQr) {
                return null;
            }

            const overlay = document.createElement('div');
            overlay.className = 'doc-wm-overlay doc-wm-overlay--page';
            overlay.setAttribute('aria-hidden', 'true');
            overlay.style.setProperty('--doc-wm-opacity', String(Math.max(0.08, Math.min(0.6, Number(watermark.opacity || 0.18)))));
            overlay.style.setProperty('--doc-wm-angle', `${Number(watermark.angle || -45)}deg`);

            if (showCenter) {
                const center = document.createElement('div');
                center.className = 'doc-wm-center';
                lines.forEach((line, index) => {
                    const span = document.createElement('span');
                    span.textContent = String(line);
                    center.appendChild(span);
                });
                overlay.appendChild(center);
            }

            if (showFooter || showQr) {
                const bottom = document.createElement('div');
                bottom.className = 'doc-wm-bottom';

                if (showFooter) {
                    const footerEl = document.createElement('div');
                    footerEl.className = 'doc-wm-footer';
                    footerEl.textContent = footer;
                    bottom.appendChild(footerEl);
                }

                if (showQr) {
                    const qr = document.createElement('div');
                    qr.className = 'doc-wm-qr';
                    qr.innerHTML = String(watermark.qr_svg);
                    bottom.appendChild(qr);
                }

                overlay.appendChild(bottom);
            }

            return overlay;
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

                state.holder.style.width = `${canvas.width}px`;
                state.holder.style.height = `${canvas.height}px`;
                state.holder.innerHTML = '';
                state.holder.appendChild(canvas);
                const pageWatermark = createWatermarkOverlay();
                if (pageWatermark) {
                    state.holder.appendChild(pageWatermark);
                }
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
