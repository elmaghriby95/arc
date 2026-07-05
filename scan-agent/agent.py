#!/usr/bin/env python3
"""ARC Scan Agent — local SANE bridge for direct scanning from the web app."""

from __future__ import annotations

import os
import re
import shutil
import subprocess
import tempfile
from pathlib import Path

from flask import Flask, Response, jsonify, request

app = Flask(__name__)

HOST = os.environ.get("SCAN_AGENT_HOST", "127.0.0.1")
PORT = int(os.environ.get("SCAN_AGENT_PORT", "8765"))
SCAN_TIMEOUT = int(os.environ.get("SCAN_TIMEOUT_SECONDS", "180"))
DEFAULT_RESOLUTION = int(os.environ.get("SCAN_DEFAULT_RESOLUTION", "120"))


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
        if origin == allowed or origin.startswith(allowed.rstrip("/")):
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


@app.route("/health", methods=["GET", "OPTIONS"])
def health():
    if request.method == "OPTIONS":
        return "", 204

    devices: list[dict[str, str]] = []

    try:
        devices = list_devices()
    except Exception:
        pass

    return jsonify({
        "ok": True,
        "platform": "linux",
        "sane": shutil.which("scanimage") is not None,
        "devices_found": len(devices),
    })


@app.route("/devices", methods=["GET", "OPTIONS"])
def devices():
    if request.method == "OPTIONS":
        return "", 204

    if not shutil.which("scanimage"):
        return jsonify({"error": "scanimage not found. Install sane-utils."}), 503

    try:
        return jsonify({"devices": list_devices()})
    except subprocess.CalledProcessError as exc:
        return jsonify({"error": exc.stderr.strip() or "Failed to list scanners."}), 500


@app.route("/scan", methods=["POST", "OPTIONS"])
def scan():
    if request.method == "OPTIONS":
        return "", 204

    if not shutil.which("scanimage"):
        return jsonify({"error": "scanimage not found. Install sane-utils."}), 503

    payload = request.get_json(silent=True) or {}

    resolution = str(payload.get("resolution", DEFAULT_RESOLUTION))
    mode = str(payload.get("mode", "Gray"))
    device = payload.get("device")
    source = str(payload.get("source", "auto"))
    fmt = str(payload.get("format", "pdf")).lower()

    if fmt not in {"pdf", "png", "jpeg", "jpg"}:
        return jsonify({"error": "Unsupported format. Use pdf, png, or jpeg."}), 400

    if fmt == "jpeg":
        fmt = "jpg"

    suffix = ".pdf" if fmt == "pdf" else ".png" if fmt == "png" else ".jpg"
    mime = {
        "pdf": "application/pdf",
        "png": "image/png",
        "jpg": "image/jpeg",
    }[fmt]

    cmd = [
        "scanimage",
        "--resolution",
        resolution,
        "--mode",
        mode,
        "--format",
        "pdf" if fmt == "pdf" else fmt,
    ]

    if device:
        cmd.extend(["--device-name", str(device)])

    source_option = resolve_source_option(source)
    if source_option:
        cmd.extend(["--source", source_option])

    if fmt == "pdf":
        cmd.extend(["--batch", f"--batch-count=50"])

    tmp_path = None

    try:
        with tempfile.NamedTemporaryFile(suffix=suffix, delete=False) as tmp:
            tmp_path = tmp.name

        with open(tmp_path, "wb") as output:
            subprocess.run(
                cmd,
                stdout=output,
                stderr=subprocess.PIPE,
                check=True,
                timeout=SCAN_TIMEOUT,
            )

        data = Path(tmp_path).read_bytes()

        if not data:
            return jsonify({"error": "Scanner returned an empty file."}), 500

        return Response(data, mimetype=mime)
    except subprocess.TimeoutExpired:
        return jsonify({"error": "Scan timed out. Try fewer pages."}), 504
    except subprocess.CalledProcessError as exc:
        message = exc.stderr.decode("utf-8", errors="replace").strip() or "Scan failed."

        return jsonify({"error": message}), 500
    finally:
        if tmp_path:
            Path(tmp_path).unlink(missing_ok=True)


def list_devices() -> list[dict[str, str]]:
    result = subprocess.run(
        ["scanimage", "-L"],
        capture_output=True,
        text=True,
        timeout=30,
        check=True,
    )

    devices: list[dict[str, str]] = []

    for line in result.stdout.splitlines():
        match = re.match(r"^device `([^']+)' is a (.+)$", line.strip())

        if match:
            devices.append({
                "id": match.group(1),
                "name": match.group(2),
            })

    return devices


def resolve_source_option(source: str) -> str | None:
    source = source.lower()

    if source in {"flatbed", "flat"}:
        return "Flatbed"

    if source in {"feeder", "adf"}:
        return "ADF"

    return None


if __name__ == "__main__":
    app.run(host=HOST, port=PORT, threaded=True)
