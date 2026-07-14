/**
 * Client-side watermarked PDF download without server rebuild.
 * Avoids FPDI size inflation (e.g. 20MB → 38MB) and builds a compact JPEG-page PDF.
 */
(function () {
    const drawWatermark = (ctx, width, height, watermark) => {
        if (!watermark) return;
        const lines = Array.isArray(watermark.center_lines) ? watermark.center_lines.filter(Boolean) : [];
        const opacity = Number(watermark.opacity || 0.18);
        const angleDeg = Number(watermark.angle || -45);
        const fontSize = Math.max(14, Number(watermark.font_size || 28) * (width / 800));

        if (watermark.show_center_text && lines.length) {
            ctx.save();
            ctx.globalAlpha = Math.max(0.05, Math.min(0.6, opacity));
            ctx.fillStyle = '#3c3c3c';
            ctx.textAlign = 'center';
            ctx.textBaseline = 'middle';
            ctx.font = `bold ${fontSize}px DejaVu Sans, Arial, sans-serif`;
            ctx.translate(width / 2, height / 2);
            ctx.rotate((angleDeg * Math.PI) / 180);
            lines.forEach((line, index) => {
                const y = (index - (lines.length - 1) / 2) * (fontSize * 1.15);
                ctx.fillText(String(line), 0, y);
            });
            ctx.restore();
        }

        if (watermark.show_footer && watermark.footer) {
            ctx.save();
            ctx.globalAlpha = Math.max(0.35, Math.min(0.75, opacity + 0.25));
            ctx.fillStyle = '#282828';
            ctx.textAlign = 'left';
            ctx.textBaseline = 'bottom';
            ctx.font = `${Math.max(10, fontSize * 0.35)}px DejaVu Sans, Arial, sans-serif`;
            ctx.fillText(String(watermark.footer), 12, height - 10, width - 80);
            ctx.restore();
        }
    };

    const dataUrlToBytes = (dataUrl) => {
        const base64 = dataUrl.split(',')[1] || '';
        const binary = atob(base64);
        const bytes = new Uint8Array(binary.length);
        for (let i = 0; i < binary.length; i += 1) {
            bytes[i] = binary.charCodeAt(i);
        }
        return bytes;
    };

    const buildPdfSinglePass = (pages) => {
        const encoder = new TextEncoder();
        const chunks = [];
        let size = 0;
        const add = (data) => {
            const bytes = typeof data === 'string' ? encoder.encode(data) : data;
            chunks.push(bytes);
            size += bytes.length;
            return bytes.length;
        };

        const offsets = {};
        add('%PDF-1.4\n%\xFF\xFF\xFF\xFF\n');

        let nextId = 3;
        const pageIds = [];
        const pageMeta = pages.map((page) => {
            const imageId = nextId++;
            const contentId = nextId++;
            const pageId = nextId++;
            pageIds.push(pageId);
            return { page, imageId, contentId, pageId };
        });

        offsets[1] = size;
        add('1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n');

        offsets[2] = size;
        add(`2 0 obj\n<< /Type /Pages /Count ${pageIds.length} /Kids [${pageIds.map((id) => `${id} 0 R`).join(' ')}] >>\nendobj\n`);

        pageMeta.forEach(({ page, imageId, contentId, pageId }) => {
            offsets[imageId] = size;
            add(
                `${imageId} 0 obj\n<< /Type /XObject /Subtype /Image /Width ${page.width} /Height ${page.height} `
                + `/ColorSpace /DeviceRGB /BitsPerComponent 8 /Filter /DCTDecode /Length ${page.bytes.length} >>\nstream\n`
            );
            add(page.bytes);
            add('\nendstream\nendobj\n');

            const content = `q\n${page.width} 0 0 ${page.height} 0 0 cm\n/Im${imageId} Do\nQ\n`;
            offsets[contentId] = size;
            add(`${contentId} 0 obj\n<< /Length ${content.length} >>\nstream\n${content}endstream\nendobj\n`);

            offsets[pageId] = size;
            add(
                `${pageId} 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 ${page.width} ${page.height}] `
                + `/Resources << /XObject << /Im${imageId} ${imageId} 0 R >> >> /Contents ${contentId} 0 R >>\nendobj\n`
            );
        });

        const xrefStart = size;
        const maxObj = nextId - 1;
        add(`xref\n0 ${maxObj + 1}\n`);
        add('0000000000 65535 f \n');
        for (let id = 1; id <= maxObj; id += 1) {
            add(`${String(offsets[id]).padStart(10, '0')} 00000 n \n`);
        }
        add(`trailer\n<< /Size ${maxObj + 1} /Root 1 0 R >>\nstartxref\n${xrefStart}\n%%EOF`);

        const total = chunks.reduce((n, c) => n + c.length, 0);
        const out = new Uint8Array(total);
        let offset = 0;
        chunks.forEach((chunk) => {
            out.set(chunk, offset);
            offset += chunk.length;
        });
        return out;
    };

    const triggerDownload = (bytes, fileName) => {
        const blob = new Blob([bytes], { type: 'application/pdf' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = fileName;
        document.body.appendChild(a);
        a.click();
        a.remove();
        setTimeout(() => URL.revokeObjectURL(url), 2000);
    };

    const downloadWatermarkedPdf = async (link) => {
        const pdfUrl = link.dataset.wmPdfUrl;
        const fileName = (link.dataset.wmFilename || 'document').replace(/\.pdf$/i, '') + '-wm.pdf';
        let watermark = null;
        try {
            watermark = link.dataset.wmContext ? JSON.parse(link.dataset.wmContext) : null;
        } catch (e) {
            watermark = null;
        }

        if (!pdfUrl || typeof pdfjsLib === 'undefined') {
            window.location.href = link.href;
            return;
        }

        const originalLabel = link.textContent;
        link.style.pointerEvents = 'none';
        link.textContent = '...';

        try {
            pdfjsLib.GlobalWorkerOptions.workerSrc = link.dataset.wmPdfWorker || pdfjsLib.GlobalWorkerOptions.workerSrc;
            const pdf = await pdfjsLib.getDocument({ url: pdfUrl, withCredentials: true }).promise;
            const pages = [];
            const renderScale = 1.25;
            const jpegQuality = 0.8;

            for (let n = 1; n <= pdf.numPages; n += 1) {
                const page = await pdf.getPage(n);
                const viewport = page.getViewport({ scale: renderScale });
                const canvas = document.createElement('canvas');
                canvas.width = Math.floor(viewport.width);
                canvas.height = Math.floor(viewport.height);
                const ctx = canvas.getContext('2d', { alpha: false });
                await page.render({ canvasContext: ctx, viewport }).promise;
                drawWatermark(ctx, canvas.width, canvas.height, watermark);
                pages.push({
                    bytes: dataUrlToBytes(canvas.toDataURL('image/jpeg', jpegQuality)),
                    width: canvas.width,
                    height: canvas.height,
                });
            }

            triggerDownload(buildPdfSinglePass(pages), fileName);
        } catch (error) {
            window.location.href = link.href;
        } finally {
            link.textContent = originalLabel;
            link.style.pointerEvents = '';
        }
    };

    document.addEventListener('click', (event) => {
        const link = event.target.closest('[data-wm-client-download]');
        if (!link) return;
        event.preventDefault();
        downloadWatermarkedPdf(link);
    });
})();
