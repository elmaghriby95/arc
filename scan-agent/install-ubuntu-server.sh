#!/usr/bin/env bash
# Install ARC Scan Agent on the SERVER (central scanner connected to server USB/network).
set -euo pipefail

INSTALL_DIR="${INSTALL_DIR:-/opt/arc-scan-agent}"
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

echo "==> Installing SANE on server..."
sudo apt-get update
sudo apt-get install -y sane sane-utils python3 python3-venv

echo "==> Installing ARC Scan Agent to ${INSTALL_DIR}..."
sudo mkdir -p "${INSTALL_DIR}"
sudo cp "${SCRIPT_DIR}/agent.py" "${INSTALL_DIR}/agent.py"
sudo cp "${SCRIPT_DIR}/requirements.txt" "${INSTALL_DIR}/requirements.txt"

sudo python3 -m venv "${INSTALL_DIR}/venv"
sudo "${INSTALL_DIR}/venv/bin/pip" install -r "${INSTALL_DIR}/requirements.txt"

sudo tee /etc/systemd/system/arc-scan-agent.service > /dev/null <<EOF
[Unit]
Description=ARC Scan Agent (server)
After=network.target

[Service]
Type=simple
User=root
Group=scanner
ExecStart=${INSTALL_DIR}/venv/bin/python ${INSTALL_DIR}/agent.py
Restart=on-failure
RestartSec=3
Environment=SCAN_AGENT_HOST=127.0.0.1
Environment=SCAN_AGENT_PORT=8765
Environment=SCAN_DEFAULT_RESOLUTION=120

[Install]
WantedBy=multi-user.target
EOF

sudo systemctl daemon-reload
sudo systemctl enable arc-scan-agent.service
sudo systemctl restart arc-scan-agent.service

echo ""
echo "Server agent installed."
echo "Test scanner: scanimage -L"
echo "Test agent:   curl http://127.0.0.1:8765/health"
echo ""
echo "In Laravel .env set:"
echo "  SCAN_MODE=server"
echo "  SCAN_AGENT_URL=/scan-agent"
echo "  SCAN_INTERNAL_AGENT_URL=http://127.0.0.1:8765"
