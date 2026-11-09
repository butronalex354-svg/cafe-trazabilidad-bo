# Servicio de IA (FastAPI + TensorFlow).
# En Railway: Root Directory = /ia-service. Solo lo llama el backend por la red privada.
FROM python:3.12-slim
WORKDIR /app
COPY requirements.txt .
RUN pip install --no-cache-dir -r requirements.txt
COPY . .
ENV TF_CPP_MIN_LOG_LEVEL=2
# Escucha por IPv4 e IPv6 (la red privada de Railway usa IPv6)
CMD ["python", "servidor.py"]
