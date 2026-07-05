#!/usr/bin/env bash
# ARC Scan Agent — one-command employee setup (Ubuntu)
set -euo pipefail

ARC_URL="https://arc.fwit.ly"
SOURCE_BASE="${ARC_SCAN_SOURCE:-https://arc.fwit.ly/scan-agent}"
INSTALL_DIR="${INSTALL_DIR:-/opt/arc-scan-agent}"
SERVICE_USER="${SUDO_USER:-$USER}"

resolve_sources() {
    if [[ -n "${BASH_SOURCE[0]:-}" && -f "${BASH_SOURCE[0]}" ]]; then
        SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

        if [[ -f "${SCRIPT_DIR}/agent.py" ]]; then
            echo "${SCRIPT_DIR}"
            return
        fi
    fi

    WORK_DIR="$(mktemp -d)"
    trap 'rm -rf "${WORK_DIR}"' EXIT

    for file in agent.py requirements.txt; do
        curl -fsSL "${SOURCE_BASE}/${file}" -o "${WORK_DIR}/${file}"
    done

    echo "${WORK_DIR}"
}

echo ""
echo "==> تثبيت ARC Scan Agent..."
echo ""

SCRIPT_DIR="$(resolve_sources)"

echo "==> تثبيت البرامج المطلوبة..."
sudo apt-get update -qq
sudo apt-get install -y sane sane-utils python3 python3-venv curl > /dev/null

echo "==> إعداد الماسح..."
sudo usermod -aG scanner "${SERVICE_USER}" 2>/dev/null || true

echo "==> تثبيت الوكيل..."
sudo mkdir -p "${INSTALL_DIR}"
sudo cp "${SCRIPT_DIR}/agent.py" "${INSTALL_DIR}/agent.py"
sudo cp "${SCRIPT_DIR}/requirements.txt" "${INSTALL_DIR}/requirements.txt"
echo "SCAN_ALLOWED_ORIGINS=${ARC_URL}" | sudo tee "${INSTALL_DIR}/agent.env" > /dev/null

if [[ ! -d "${INSTALL_DIR}/venv" ]]; then
    sudo python3 -m venv "${INSTALL_DIR}/venv"
fi

sudo "${INSTALL_DIR}/venv/bin/pip" install -q -r "${INSTALL_DIR}/requirements.txt"

sudo tee /etc/systemd/user/arc-scan-agent.service > /dev/null <<EOF
[Unit]
Description=ARC Scan Agent
After=network.target

[Service]
Type=simple
ExecStart=${INSTALL_DIR}/venv/bin/python ${INSTALL_DIR}/agent.py
Restart=on-failure
RestartSec=3
Environment=SCAN_AGENT_HOST=127.0.0.1
Environment=SCAN_AGENT_PORT=8765
Environment=SCAN_DEFAULT_RESOLUTION=120
Environment=SCAN_ALLOWED_ORIGINS=${ARC_URL}

[Install]
WantedBy=default.target
EOF

sudo loginctl enable-linger "${SERVICE_USER}" 2>/dev/null || true
sudo -u "${SERVICE_USER}" XDG_RUNTIME_DIR="/run/user/$(id -u "${SERVICE_USER}")" systemctl --user daemon-reload
sudo -u "${SERVICE_USER}" XDG_RUNTIME_DIR="/run/user/$(id -u "${SERVICE_USER}")" systemctl --user enable arc-scan-agent.service
sudo -u "${SERVICE_USER}" XDG_RUNTIME_DIR="/run/user/$(id -u "${SERVICE_USER}")" systemctl --user restart arc-scan-agent.service

sleep 1

if curl -fsS http://127.0.0.1:8765/health > /dev/null 2>&1; then
    echo ""
    echo "تم التثبيت بنجاح."
    echo "افتح ${ARC_URL} واضغط «مسح مباشر»."
    echo ""
else
    echo ""
    echo "تم التثبيت. أعد تسجيل الدخول ثم جرّب ${ARC_URL}"
    echo ""
fi
