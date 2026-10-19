import json
import csv
import os
import time
from datetime import datetime, timedelta

ARCHIVO_DATOS = "/var/www/html/data.json"
ARCHIVO_PREDICCIONES = "/var/www/html/predicciones.csv"

NUM_DATOS = 3



def leer_datos():

    datos = []

    if not os.path.exists(ARCHIVO_DATOS):
        return datos

    with open(ARCHIVO_DATOS, "r", encoding="utf-8") as f:

        for linea in f:

            linea = linea.strip()

            if not linea:
                continue

            try:
                dato = json.loads(linea)
                datos.append(dato)

            except Exception as e:
                print("Error leyendo línea:", e)

    return datos

def persistencia(valor):

    return float(valor)

def media_movil_ponderada(valores):

    if len(valores) < 3:
        return float(valores[-1])

    dato_antiguo = float(valores[-3])
    dato_intermedio = float(valores[-2])
    dato_reciente = float(valores[-1])

    prediccion = (
        2 * dato_antiguo
        + 3 * dato_intermedio
        + 5 * dato_reciente) / 10

    return float(prediccion)

def calcular_hora_futura(hora):

    hora_actual = datetime.strptime(
        hora,
        "%H:%M:%S"
    )

    hora_futura = (
        hora_actual
        + timedelta(minutes=5)
    )

    return hora_futura.strftime(
        "%H:%M:%S"
    )

def obtener_ultima_hora_predicha():

    if not os.path.exists(ARCHIVO_PREDICCIONES):
        return None

    if os.path.getsize(ARCHIVO_PREDICCIONES) == 0:
        return None

    try:

        with open(
            ARCHIVO_PREDICCIONES,
            "r",
            encoding="utf-8"
        ) as f:

            filas = list(csv.reader(f))

        if len(filas) <= 1:
            return None

        return filas[-1][0]

    except Exception as e:

        print(
            "Error leyendo predicciones:",
            e
        )

        return None

def guardar_prediccion(
    hora,
    temperatura,
    ph,
    oxigeno,
    turbidez
):

    existe = (
        os.path.exists(ARCHIVO_PREDICCIONES)
        and
        os.path.getsize(ARCHIVO_PREDICCIONES) > 0
    )

    with open(
        ARCHIVO_PREDICCIONES,
        "a",
        newline="",
        encoding="utf-8"
    ) as f:

        writer = csv.writer(f)

        if not existe:

            writer.writerow([
                "Hora",
                "Temperatura_pred",
                "pH_pred",
                "Oxigeno_pred",
                "Turbidez_pred"
            ])

        writer.writerow([
            hora,
            round(temperatura, 4),
            round(ph, 4),
            round(oxigeno, 4),
            round(turbidez, 4)
        ])

while True:

    try:

        datos = leer_datos()

        if len(datos) < NUM_DATOS:

            print(
                f"Esperando datos: "
                f"{len(datos)}/{NUM_DATOS}"
            )

            time.sleep(5)
            continue


        # Últimas 3 mediciones
        ultimos = datos[-NUM_DATOS:]

        ultimo_dato = ultimos[-1]


        temperatura_actual = float(
            ultimo_dato["temp"]
        )

        ph_actual = float(
            ultimo_dato["ph"]
        )

        oxigeno_actual = float(
            ultimo_dato["oxigeno"]
        )

        turbideces = [
            float(d["turbidez"])
            for d in ultimos
        ]


        ultima_hora = ultimo_dato["time"]

        hora_prediccion = calcular_hora_futura(
            ultima_hora
        )

        if (
            hora_prediccion
            == obtener_ultima_hora_predicha()
        ):

            time.sleep(5)
            continue

        temperatura_pred = persistencia(
            temperatura_actual
        )

        ph_pred = persistencia(
            ph_actual
        )

        oxigeno_pred = persistencia(
            oxigeno_actual
        )

        turbidez_pred = media_movil_ponderada(
            turbideces
        )


        guardar_prediccion(
            hora_prediccion,
            temperatura_pred,
            ph_pred,
            oxigeno_pred,
            turbidez_pred
        )

        print("")
        print("Nueva predicción generada")

        print(
            f"Última medición: {ultima_hora}"
        )

        print(
            f"Predicción para: {hora_prediccion}"
        )

        print(
            f"Temperatura: "
            f"{temperatura_pred:.4f} °C "
            f"[Persistencia]"
        )

        print(
            f"pH: "
            f"{ph_pred:.4f} "
            f"[Persistencia]"
        )

        print(
            f"Oxígeno disuelto: "
            f"{oxigeno_pred:.4f} mg/L "
            f"[Persistencia]"
        )

        print(
            f"Turbidez: "
            f"{turbidez_pred:.4f} NTU "
            f"[Media móvil ponderada]"
        )

        print("")


    except Exception as e:

        print("Error:", e)


    time.sleep(5)
