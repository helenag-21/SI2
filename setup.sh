#!/bin/bash
set -e

GREEN='\033[0;32m'; YELLOW='\033[1;33m'; RED='\033[0;31m'; NC='\033[0m'
info()  { echo -e "${GREEN}[OK]${NC}   $1"; }
warn()  { echo -e "${YELLOW}[!]${NC}    $1"; }
error() { echo -e "${RED}[ERR]${NC}  $1"; exit 1; }

echo -e "\n${GREEN}=== Osobný denník — inštalácia ===${NC}\n"

# 1. Docker
if ! command -v docker &>/dev/null; then
    info "Inštalujem Docker..."
    curl -fsSL https://get.docker.com | sudo sh
    sudo usermod -aG docker "$USER"
    info "Docker nainštalovaný. POZOR: Odhláste sa a prihláste sa znova pre docker bez sudo."
else
    info "Docker: $(docker --version)"
fi

# 2. Docker Compose
if ! docker compose version &>/dev/null 2>&1; then
    info "Inštalujem Docker Compose plugin..."
    LATEST=$(curl -s https://api.github.com/repos/docker/compose/releases/latest | grep '"tag_name"' | cut -d'"' -f4)
    sudo mkdir -p /usr/local/lib/docker/cli-plugins
    sudo curl -SL "https://github.com/docker/compose/releases/download/$LATEST/docker-compose-linux-$(uname -m)" \
        -o /usr/local/lib/docker/cli-plugins/docker-compose
    sudo chmod +x /usr/local/lib/docker/cli-plugins/docker-compose
else
    info "Docker Compose: $(docker compose version)"
fi

# 3. Firewall — Oracle Linux používa firewalld
if command -v firewall-cmd &>/dev/null; then
    sudo firewall-cmd --permanent --add-port=80/tcp   2>/dev/null || true
    sudo firewall-cmd --permanent --add-port=8080/tcp 2>/dev/null || true
    sudo firewall-cmd --reload 2>/dev/null || true
    info "Firewall: porty 80 a 8080 otvorené"
elif command -v ufw &>/dev/null; then
    sudo ufw allow 80/tcp && sudo ufw allow 8080/tcp
    info "UFW: porty 80 a 8080 otvorené"
else
    warn "Firewall nenájdený — otvor porty 80 a 8080 manuálne v Oracle Cloud Console"
fi

# 4. .env
if [ ! -f .env ]; then
    cp .env.example .env
    warn "Súbor .env vytvorený. Teraz UPRAV HESLÁ:"
    echo ""
    echo "  nano .env"
    echo ""
    read -rp "  Stlač Enter keď budeš mať heslá upravené..."
fi

# 5. Spustenie
info "Spúšťam kontajnery..."
docker compose up -d --build

# 6. Čakáme na DB
info "Čakám na databázu..."
for i in $(seq 1 30); do
    docker exec dennik_db mysqladmin ping -h localhost --silent 2>/dev/null && break
    sleep 2
done
info "Databáza pripravená."

# 7. Hotovo
IP=$(curl -s ifconfig.me 2>/dev/null || hostname -I | awk '{print $1}')
echo ""
echo -e "${GREEN}╔══════════════════════════════════════════════╗${NC}"
echo -e "${GREEN}║  Hotovo!                                     ║${NC}"
echo -e "${GREEN}╠══════════════════════════════════════════════╣${NC}"
echo -e "${GREEN}║${NC}  Aplikácia:   http://$IP"
echo -e "${GREEN}║${NC}  Editor (kolegyňa): http://$IP:8080"
echo -e "${GREEN}╠══════════════════════════════════════════════╣${NC}"
echo -e "${GREEN}║${NC}  Nezabudni otvoriť porty v Oracle Cloud!"
echo -e "${GREEN}║${NC}  Networking → VCN → Security Lists"
echo -e "${GREEN}║${NC}  Ingress rules: port 80 a 8080 (0.0.0.0/0)"
echo -e "${GREEN}╚══════════════════════════════════════════════╝${NC}"
