# -*- coding: utf-8 -*-
"""
Servicio de IA (RF07): recibe una foto de una planta de cafe y devuelve el
diagnostico real (sana / plaga / enfermedad) usando el modelo entrenado con
el dataset real RoCoLe (fotos reales de hojas de cafe, con roya y acaros).

El backend Laravel llama a este servicio (POST /diagnosticar) en vez de usar
el resultado simulado.
"""
import io
import json
import os

import numpy as np
from fastapi import FastAPI, File, HTTPException, UploadFile
from fastapi.responses import JSONResponse
from PIL import Image
from tensorflow import keras

BASE_DIR = os.path.dirname(os.path.abspath(__file__))
RUTA_MODELO = os.path.join(BASE_DIR, "modelo_cafe.keras")
RUTA_CLASES = os.path.join(BASE_DIR, "clases.json")
IMG_SIZE = (224, 224)

# El modelo ahora tiene una 4ta clase "no_es_cafe" (entrenada con fotos
# genericas ajenas al cafe), asi que ya puede decir explicitamente "esto no
# es una hoja/fruto de cafe" en vez de forzar siempre sana/plaga/enfermedad.
# El umbral de confianza queda como red de seguridad extra para el caso de
# fotos ambiguas *de café* (borrosas, mal encuadradas, etc).
CLASE_NO_RECONOCIDO = "no_es_cafe"
UMBRAL_CONFIANZA_MINIMA = 50.0

app = FastAPI(title="Servicio de IA - Cafe Trazabilidad Bolivia")

modelo = None
nombres_clases = []


@app.on_event("startup")
def cargar_modelo():
    global modelo, nombres_clases
    if not os.path.exists(RUTA_MODELO):
        print(f"AVISO: no se encontro el modelo en {RUTA_MODELO}. El servicio no podra diagnosticar hasta que se entrene.")
        return
    modelo = keras.models.load_model(RUTA_MODELO)
    with open(RUTA_CLASES, encoding="utf-8") as f:
        nombres_clases = json.load(f)
    print(f"Modelo cargado. Clases: {nombres_clases}")


@app.get("/")
def salud():
    return {
        "servicio": "IA Cafe Trazabilidad",
        "modelo_cargado": modelo is not None,
        "clases": nombres_clases,
    }


@app.post("/diagnosticar")
async def diagnosticar(foto: UploadFile = File(...)):
    if modelo is None:
        raise HTTPException(status_code=503, detail="El modelo todavia no esta entrenado/cargado.")

    try:
        contenido = await foto.read()
        imagen = Image.open(io.BytesIO(contenido)).convert("RGB")
        imagen = imagen.resize(IMG_SIZE)
        arreglo = keras.utils.img_to_array(imagen)
        arreglo = np.expand_dims(arreglo, axis=0)
        # OJO: no se aplica preprocess_input aqui — el modelo ya lo tiene
        # incorporado en su propio grafo (se definio asi al entrenarlo), asi
        # que espera el arreglo "crudo" en escala 0-255. Aplicarlo dos veces
        # corrompe el rango de valores y arruina la prediccion.
    except Exception:
        raise HTTPException(status_code=422, detail="No se pudo leer la imagen enviada.")

    predicciones = modelo.predict(arreglo, verbose=0)[0]
    indice_max = int(np.argmax(predicciones))
    resultado = nombres_clases[indice_max]
    confianza = float(predicciones[indice_max]) * 100
    reconocido = resultado != CLASE_NO_RECONOCIDO and confianza >= UMBRAL_CONFIANZA_MINIMA

    return JSONResponse({
        "resultado": resultado,
        "confianza": round(confianza, 2),
        "reconocido": reconocido,
        "detalle_probabilidades": {
            nombres_clases[i]: round(float(p) * 100, 2) for i, p in enumerate(predicciones)
        },
    })
