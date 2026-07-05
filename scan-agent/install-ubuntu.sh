#!/usr/bin/env bash
set -euo pipefail

INSTALL_DIR="${INSTALL_DIR:-/opt/arc-scan-agent}"
SERVICE_USER="${SUDO_USER:-$USER}"
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ARC_URL="${SCAN_ALLOWED_ORIGINS:-${ARC_URL:-}}"

echo "==> Installing SANE..."
sudo apt-get update
sudo apt-get install -y sane sane-utils python3 python3-venv

echo "==> Adding ${SERVICE_USER} to scanner group..."
sudo usermod -aG scanner "${SERVICE_USER}" || true

echo "==> Installing ARC Scan Agent to ${INSTALL_DIR}..."
sudo mkdir -p "${INSTALL_DIR}"
sudo cp "${SCRIPT_DIR}/agent.py" "${INSTALL_DIR}/agent.py"
sudo cp "${SCRIPT_DIR}/requirements.txt" "${INSTALL_DIR}/requirements.txt"

sudo python3 -m venv "${INSTALL_DIR}/venv"
sudo "${INSTALL_DIR}/venv/bin/pip" install -r "${INSTALL_DIR}/requirements.txt"

ENV_LINE=""
if [[ -n "${ARC_URL}" ]]; then
    ENV_LINE="Environment=SCAN_ALLOWED_ORIGINS=${ARC_URL}"
fi

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
${ENV_LINE}

[Install]
WantedBy=default.target
EOF

sudo loginctl enable-linger "${SERVICE_USER}" || true
sudo -u "${SERVICE_USER}" XDG_RUNTIME_DIR="/run/user/$(id -u "${SERVICE_USER}")" systemctl --user daemon-reload
sudo -u "${SERVICE_USER}" XDG_RUNTIME_DIR="/run/user/$(id -u "${SERVICE_USER}")" systemctl --user enable arc-scan-agent.service
sudo -u "${SERVICE_USER}" XDG_RUNTIME_DIR="/run/user/$(id -u "${SERVICE_USER}")" systemctl --user restart arc-scan-agent.service

echo ""
echo "Installation complete."
echo "Test scanner: scanimage -L"
echo "Test agent:   curl http://127.0.0.1:8765/health"
echo ""
if [[ -z "${ARC_URL}" ]]; then
    echo "Tip: re-run with ARC_URL=https://your-arc-server.example"
fi
echo "Log out and back in so scanner group membership applies."
