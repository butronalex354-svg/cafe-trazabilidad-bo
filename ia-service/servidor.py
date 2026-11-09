# -*- coding: utf-8 -*-
"""Arranca el servicio de IA (main.py) escuchando a la vez por IPv4 e IPv6.

En Railway el backend llama a la IA por la red privada (IPv6) y en Docker
local por IPv4. "uvicorn --host ::" solo acepta IPv6 y "--host 0.0.0.0" solo
IPv4, por eso se abre un socket doble a mano.
Uso: python servidor.py   (puerto en la variable PORT, por defecto 8001)
"""
import os
import socket

import uvicorn

puerto = int(os.environ.get("PORT", "8001"))
try:
    sock = socket.socket(socket.AF_INET6, socket.SOCK_STREAM)
    sock.setsockopt(socket.IPPROTO_IPV6, socket.IPV6_V6ONLY, 0)  # tambien IPv4
    sock.setsockopt(socket.SOL_SOCKET, socket.SO_REUSEADDR, 1)
    sock.bind(("::", puerto))
except OSError:
    # equipo sin IPv6: solo IPv4
    sock = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
    sock.setsockopt(socket.SOL_SOCKET, socket.SO_REUSEADDR, 1)
    sock.bind(("0.0.0.0", puerto))

print(f"Servicio de IA escuchando en el puerto {puerto}", flush=True)
uvicorn.Server(uvicorn.Config("main:app", log_level="info")).run(sockets=[sock])
