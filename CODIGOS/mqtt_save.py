import paho.mqtt.client as mqtt
import json
from datetime import datetime

TOPIC = "esp32/iot/sensores"

def on_message(client, userdata, msg):
    try:
        data = json.loads(msg.payload.decode())

        linea = {
            "time": datetime.now().strftime("%H:%M:%S"),
            "temp": data["temp"],
            "turbidez": data["turbidez"],
            "oxigeno": data["oxigeno"],
            "ph": data["ph"]
        }

        with open("/var/www/html/data.json", "a") as f:
            f.write(json.dumps(linea) + "\n")

        print(linea)

    except Exception as e:
        print("Error:", e)

client = mqtt.Client()
client.connect("broker.hivemq.com", 1883, 60)

client.subscribe(TOPIC)
client.on_message = on_message

client.loop_forever()
