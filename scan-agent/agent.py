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
SCAN_TIMEOUT = int(os.environ.get("SCAN_TIMEOUT_SECONDS", "120"))


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


def cors_origin() -> str | None:
    origin = request.headers.get("Origin")

    if not origin:
        return None

    for allowed in allowed_origins():
        if origin == allowed or origin.startswith(allowed.rstrip("/")):
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

    return jsonify({
        "ok": True,
        "sane": shutil.which("scanimage") is not None,
    })


@app.route("/devices", methods=["GET", "OPTIONS"])
def devices():
    if request.method == "OPTIONS":
        return "", 204

    if not shutil.which("scanimage"):
        return jsonify({"error": "scanimage not found. Install sane-utils."}), 503

    try:
        result = subprocess.run(
            ["scanimage", "-L"],
            capture_output=True,
            text=True,
            timeout=30,
            check=True,
        )
    except subprocess.CalledProcessError as exc:
        return jsonify({"error": exc.stderr.strip() or "Failed to list scanners."}), 500

    devices: list[dict[str, str]] = []

    for line in result.stdout.splitlines():
        match = re.match(r"^device `([^']+)' is a (.+)$", line.strip())

        if match:
            devices.append({
                "id": match.group(1),
                "name": match.group(2),
            })

    return jsonify({"devices": devices})


@app.route("/scan", methods=["POST", "OPTIONS"])
def scan():
    if request.method == "OPTIONS":
        return "", 204

    if not shutil.which("scanimage"):
        return jsonify({"error": "scanimage not found. Install sane-utils."}), 503

    payload = request.get_json(silent=True) or {}

    resolution = str(payload.get("resolution", 300))
    mode = str(payload.get("mode", "Gray"))
    device = payload.get("device")
    fmt = str(payload.get("format", "pdf"))

    if fmt not in {"pdf", "png"}:
        return jsonify({"error": "Unsupported format. Use pdf or png."}), 400

    suffix = ".pdf" if fmt == "pdf" else ".png"
    mime = "application/pdf" if fmt == "pdf" else "image/png"

    cmd = ["scanimage", "--resolution", resolution, "--mode", mode, "--format", fmt]

    if device:
        cmd.extend(["--device-name", str(device)])

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
        return jsonify({"error": "Scan timed out."}), 504
    except subprocess.CalledProcessError as exc:
        message = exc.stderr.decode("utf-8", errors="replace").strip() or "Scan failed."

        return jsonify({"error": message}), 500
    finally:
        if tmp_path:
            Path(tmp_path).unlink(missing_ok=True)


if __name__ == "__main__":
    app.run(host=HOST, port=PORT, threaded=True)
