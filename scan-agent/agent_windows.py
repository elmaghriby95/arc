#!/usr/bin/env python3
"""ARC Scan Agent for Windows — WIA bridge, no PHP required."""

from __future__ import annotations

import os
import re
import tempfile
import traceback
import uuid
from pathlib import Path

from flask import Flask, Response, jsonify, request

app = Flask(__name__)

HOST = os.environ.get("SCAN_AGENT_HOST", "127.0.0.1")
PORT = int(os.environ.get("SCAN_AGENT_PORT", "8765"))
MAX_FEEDER_PAGES = 50
WIA_FORMAT_JPEG = "{B96B3CAE-0728-11D3-9D7B-0000F81EF32E}"
WIA_INTENT_GRAY = 0x00000002
WIA_INTENT_COLOR = 0x00000001
WIA_DPS_DOCUMENT_HANDLING_SELECT = 3088
WIA_IPS_PAGES = 3096
WIA_HORIZONTAL_RES = 6147
WIA_VERTICAL_RES = 6148
WIA_INTENT = 6146


def load_env_file() -> None:
    env_file = Path(__file__).resolve().parent / "agent.env"

    if not env_file.is_file():
        return

    for line in env_file.read_text(encoding="utf-8").splitlines():
        line = line.strip()

        if not line or line.startswith("#") or "=" not in line:
            continue

        key, value = line.split("=", 1)
        os.environ.setdefault(key.strip(), value.strip())


load_env_file()

LOG_FILE = Path(__file__).resolve().parent / "agent.log"


def setup_logging() -> None:
    import logging

    logging.basicConfig(
        filename=LOG_FILE,
        level=logging.INFO,
        format="%(asctime)s %(levelname)s %(message)s",
        encoding="utf-8",
    )


setup_logging()


def allowed_origins() -> list[str]:
    raw = os.environ.get("SCAN_ALLOWED_ORIGINS", "")
    origins = [origin.strip() for origin in raw.split(",") if origin.strip()]

    if origins:
        return origins

    return [
        "http://localhost",
        "http://127.0.0.1",
        "https://localhost",
        "https://127.0.0.1",
    ]


def origin_allowed(origin: str) -> bool:
    if re.match(r"^https?://(localhost|127\.0\.0\.1)(:\d+)?$", origin):
        return True

    for allowed in allowed_origins():
        if origin.rstrip("/") == allowed.rstrip("/"):
            return True

    return False


def cors_origin() -> str | None:
    origin = request.headers.get("Origin")

    if origin and origin_allowed(origin):
        return origin

    return None


@app.after_request
def add_cors_headers(response: Response) -> Response:
    origin = cors_origin()

    if origin:
        response.headers["Access-Control-Allow-Origin"] = origin
        response.headers["Vary"] = "Origin"

    response.headers["Access-Control-Allow-Methods"] = "GET, POST, OPTIONS"
    response.headers["Access-Control-Allow-Headers"] = "Content-Type"
    response.headers["Access-Control-Allow-Private-Network"] = "true"

    return response


_com_initialized = False


def ensure_com() -> None:
    global _com_initialized

    if _com_initialized:
        return

    import pythoncom

    pythoncom.CoInitialize()
    _com_initialized = True


def wia_available() -> bool:
    try:
        import pythoncom  # noqa: F401
        import win32com.client  # noqa: F401

        return True
    except ImportError:
        return False


def release_com_objects() -> None:
    import gc

    gc.collect()


def target_width(resolution: int) -> int:
    return max(900, min(1400, int(round(8.27 * resolution))))


def temp_path(suffix: str) -> Path:
    return Path(tempfile.gettempdir()) / f"arc-scan-{uuid.uuid4().hex}.{suffix}"


def optimize_jpeg(path: Path, quality: int, max_width: int) -> Path:
    from PIL import Image

    with Image.open(path) as image:
        image = image.convert("L")
        width, height = image.size

        if width > max_width:
            new_height = max(1, int(round(height * (max_width / width))))
            image = image.resize((max_width, new_height), Image.Resampling.LANCZOS)

        target = temp_path("jpg")
        image.save(target, format="JPEG", quality=max(35, min(75, quality)), optimize=True)

    if target != path:
        path.unlink(missing_ok=True)

    return target


def build_pdf(jpeg_paths: list[Path], dpi: int) -> bytes:
    import img2pdf

    return img2pdf.convert(*[str(path) for path in jpeg_paths], dpi=dpi)


def is_paper_empty_error(message: str) -> bool:
    lowered = message.lower()

    return (
        "paper empty" in lowered
        or "80210003" in lowered
        or "no documents" in lowered
        or "document feeder" in lowered
    )


def set_property(item, property_id: str | int, value: int) -> None:
    try:
        prop = item.Properties.Item(str(property_id))
        prop.Value = value
    except Exception:
        pass


def set_device_property(device, property_id: str | int, value: int) -> None:
    try:
        prop = device.Properties.Item(str(property_id))
        prop.Value = value
    except Exception:
        try:
            if int(device.Items.Count) >= 1:
                prop = device.Items[1].Properties.Item(str(property_id))
                prop.Value = value
        except Exception:
            pass


def apply_properties(item, mode: str, resolution: int) -> None:
    set_property(item, WIA_HORIZONTAL_RES, resolution)
    set_property(item, WIA_VERTICAL_RES, resolution)

    if mode == "color":
        set_property(item, WIA_INTENT, WIA_INTENT_COLOR)
    else:
        set_property(item, WIA_INTENT, WIA_INTENT_GRAY)


def list_devices() -> list[dict[str, str]]:
    import win32com.client

    ensure_com()
    manager = win32com.client.Dispatch("WIA.DeviceManager")
    devices: list[dict[str, str]] = []

    try:
        for info in manager.DeviceInfos:
            if int(info.Type) != 1:
                continue

            devices.append({
                "id": str(info.DeviceID),
                "name": str(info.Properties("Name").Value),
            })
    finally:
        manager = None
        release_com_objects()

    return devices


def resolve_device(manager, device_id: str | None):
    fallback = None

    for info in manager.DeviceInfos:
        if int(info.Type) != 1:
            continue

        if device_id is not None and str(info.DeviceID) == device_id:
            return info

        if fallback is None:
            fallback = info

    return fallback


def resolve_scan_item(device):
    if int(device.Items.Count) >= 1:
        return device.Items[1]

    raise RuntimeError("Scanner has no scannable items.")


def scan_from_feeder(device, item, mode: str, resolution: int) -> list[Path]:
    set_device_property(device, WIA_DPS_DOCUMENT_HANDLING_SELECT, 1)
    apply_properties(item, mode, resolution)

    pages: list[Path] = []

    for index in range(MAX_FEEDER_PAGES):
        set_property(item, WIA_IPS_PAGES, 1)

        try:
            image = item.Transfer(WIA_FORMAT_JPEG)
            path = temp_path("jpg")
            image.SaveFile(str(path))
            pages.append(path)
        except Exception as exc:
            if index == 0 and is_paper_empty_error(str(exc)):
                return []

            break

    return pages


def scan_from_flatbed(device, item, mode: str, resolution: int) -> Path:
    set_device_property(device, WIA_DPS_DOCUMENT_HANDLING_SELECT, 2)
    apply_properties(item, mode, resolution)
    set_property(item, WIA_IPS_PAGES, 1)

    try:
        image = item.Transfer(WIA_FORMAT_JPEG)
        path = temp_path("jpg")
        image.SaveFile(str(path))

        return path
    except Exception as exc:
        if is_paper_empty_error(str(exc)):
            raise RuntimeError("لا توجد أوراق. ضعها في الفيدر أو على السطح.") from exc

        raise RuntimeError(str(exc)) from exc


def acquire_pages(device, item, source: str, mode: str, resolution: int) -> list[Path]:
    source = source.lower()

    if source in {"auto", "feeder"}:
        pages = scan_from_feeder(device, item, mode, resolution)

        if pages or source == "feeder":
            return pages

    flatbed = scan_from_flatbed(device, item, mode, resolution)

    return [flatbed]


def scan_document(payload: dict) -> tuple[bytes, str]:
    import win32com.client

    resolution = max(100, min(300, int(payload.get("resolution", 120))))
    quality = max(35, min(75, int(payload.get("quality", 48))))
    mode = str(payload.get("mode", "Gray")).lower()
    source = str(payload.get("source", "auto")).lower()
    fmt = str(payload.get("format", "pdf")).lower()
    device_id = payload.get("device")

    if fmt == "jpeg":
        fmt = "jpg"

    if fmt not in {"pdf", "jpg", "png"}:
        raise RuntimeError("Unsupported format. Use pdf, png, or jpeg.")

    ensure_com()
    manager = win32com.client.Dispatch("WIA.DeviceManager")
    device_info = resolve_device(manager, device_id)

    if device_info is None:
        raise RuntimeError("لم يُعثر على ماسح. تحقق من USB وتعريف HP.")

    device = device_info.Connect()
    item = resolve_scan_item(device)
    wia_mode = "color" if mode == "color" else "gray"

    try:
        raw_pages = acquire_pages(device, item, source, wia_mode, resolution)
    finally:
        item = device = device_info = manager = None
        release_com_objects()

    if not raw_pages:
        raise RuntimeError("لم تُمسح أي صفحة.")

    optimized = [
        optimize_jpeg(page, quality, target_width(resolution))
        for page in raw_pages
    ]

    for page in raw_pages:
        if page not in optimized:
            page.unlink(missing_ok=True)

    try:
        if fmt == "pdf" or len(optimized) > 1:
            data = build_pdf(optimized, resolution)
            mime = "application/pdf"
        else:
            data = optimized[0].read_bytes()
            mime = "image/jpeg"
    finally:
        for page in optimized:
            page.unlink(missing_ok=True)

    return data, mime


@app.route("/health", methods=["GET", "OPTIONS"])
def health():
    if request.method == "OPTIONS":
        return "", 204

    devices: list[dict[str, str]] = []

    if wia_available():
        try:
            devices = list_devices()
        except Exception:
            pass

    return jsonify({
        "ok": True,
        "platform": "windows",
        "wia": wia_available(),
        "devices_found": len(devices),
    })


@app.route("/devices", methods=["GET", "OPTIONS"])
def devices():
    if request.method == "OPTIONS":
        return "", 204

    if not wia_available():
        return jsonify({"error": "pywin32 not installed."}), 503

    try:
        return jsonify({"devices": list_devices()})
    except Exception as exc:
        return jsonify({"error": str(exc)}), 500


@app.route("/scan", methods=["POST", "OPTIONS"])
def scan():
    if request.method == "OPTIONS":
        return "", 204

    if not wia_available():
        return jsonify({"error": "pywin32 not installed."}), 503

    payload = request.get_json(silent=True) or {}

    try:
        data, mime = scan_document(payload)
    except RuntimeError as exc:
        print(f"[scan] {exc}", flush=True)
        app.logger.warning("scan rejected: %s", exc)
        return jsonify({"error": str(exc)}), 400
    except Exception as exc:
        traceback.print_exc()
        app.logger.exception("scan failed")
        return jsonify({"error": str(exc)}), 500

    return Response(data, mimetype=mime)


if __name__ == "__main__":
    ensure_com()
    app.logger.info("ARC Scan Agent starting on http://%s:%s", HOST, PORT)
    print("ARC Scan Agent ready.", flush=True)
    app.run(host=HOST, port=PORT, threaded=False)
