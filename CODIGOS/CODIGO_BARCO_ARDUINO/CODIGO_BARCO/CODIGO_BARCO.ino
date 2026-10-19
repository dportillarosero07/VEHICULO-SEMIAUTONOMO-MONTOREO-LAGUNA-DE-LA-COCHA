#include <WiFi.h>  
#include <PubSubClient.h>  
#include <OneWire.h>  
  
// WIFI  
const char* ssid = "iPhone de David";  
const char* password = "12345678";  
  
// MQTT  
const char* mqtt_server = "broker.hivemq.com";  
WiFiClient espClient;  
PubSubClient client(espClient);  
String topic = "esp32/iot/sensores";  
  
// TIEMPO  
unsigned long intervalo = 300000;  
unsigned long tiempoAnterior = 0;  
 
// OD 
#define OD_PIN 35 
#define VREF 3300  
#define ADC_RES 4096  
 
// VARIABLES CALIBRACION OD 
#define READ_TEMP (18)  
#define CAL1_V (475)  
#define CAL1_T (18)  
  
const uint16_t DO_Table[41] = {  
  14460, 14220, 13820, 13440, 13090,  
  12740, 12420, 12110, 11810, 11530,  
  11260, 11010, 10770, 10530, 10300,  
  10080, 9860, 9660, 9460, 9270,  
  9080, 8900, 8730, 8570, 8410,  
  8250, 8110, 7960, 7820, 7690,  
  7560, 7430, 7300, 7180, 7070,  
  6950, 6840, 6730, 6630, 6530,  
  6410  
};  
  
uint8_t Temperaturet;  
uint16_t ADC_Raw;  
uint16_t ADC_Voltage;  
uint16_t DO;  
  
float oxigeno = 0;  
 
// TEMPERATURA 
  
int DS18S20_Pin = 14;  
OneWire ds(DS18S20_Pin);  
  
// TURBIDEZ 
 
#define TURBIDEZ_PIN 32  
  
int tbz;  
float V_tbz;  
  
float voltageTurbidity = 0;  
float voltageSensor = 0;  
  
// PH 
  
#define SensorPin 34 
#define Offset 2.2
#define samplingInterval 20  
#define ArrayLenth 40  
  
int pHArray[ArrayLenth];  
int pHArrayIndex = 0;  
  
static unsigned long samplingTime = 0;  
  
float pHValue = 0;  
float voltagePH = 0;  
  
// ---------------- WIFI ----------------  
  
void conectarWiFi() {  
  WiFi.begin(ssid, password);  
  Serial.print("Conectando WiFi");  
  
  while (WiFi.status() != WL_CONNECTED) {  
    delay(500);  
    Serial.print(".");  
  }  
  
  Serial.println("\nWiFi conectado");  
}  
  
// ---------------- MQTT ----------------  
  
void reconnect() {  
  while (!client.connected()) {  
    Serial.print("Conectando MQTT...");  
  
    if (client.connect("ESP32_IOT_SENSORES")) {  
      Serial.println("conectado");  
    } else {  
      Serial.print("falló rc=");  
      Serial.print(client.state());  
      delay(2000);  
    }  
  }  
}  
 
int16_t readDO(uint32_t voltage_mv, uint8_t temperature_c)  
{  
  uint16_t V_saturation =  
    (uint32_t)CAL1_V +  
    (uint32_t)35 * temperature_c -  
    (uint32_t)CAL1_T * 35;  
  
  return (  
    voltage_mv *  
    DO_Table[temperature_c] /  
    V_saturation  
  );  
}  
 
// TEMPERATURA  
  
float getTemp() {  
  
  byte data[12];  
  byte addr[8];  
  
  if (!ds.search(addr)) {  
    ds.reset_search();  
    return -1000;  
  }  
  
  if (OneWire::crc8(addr, 7) != addr[7]) {  
    return -1000;  
  }  
  
  if (addr[0] != 0x10 && addr[0] != 0x28) {  
    return -1000;  
  }  
  
  ds.reset();  
  ds.select(addr);  
  ds.write(0x44, 1);  
  
  delay(1000);  
  
  ds.reset();  
  ds.select(addr);  
  ds.write(0xBE);  
  
  for (int i = 0; i < 9; i++) {  
    data[i] = ds.read();  
  }  
  
  ds.reset_search();  
  
  byte MSB = data[1];  
  byte LSB = data[0];  
  
  float tempRead = ((MSB << 8) | LSB);  
  float TemperatureSum = tempRead / 16;  
  
  return TemperatureSum;  
}  
 
  
double avergearray(int* arr, int number)  
{  
  int i;  
  int max, min;  
  double avg;  
  long amount = 0;  
  
  if (number <= 0) {  
    Serial.println("Error number for the array to avraging!");  
    return 0;  
  }  
  
  if (number < 5) {  
  
    for (i = 0; i < number; i++) {  
      amount += arr[i];  
    }  
  
    avg = amount / number;  
    return avg;  
  
  } else {  
  
    if (arr[0] < arr[1]) {  
      min = arr[0];  
      max = arr[1];  
    } else {  
      min = arr[1];  
      max = arr[0];  
    }  
  
    for (i = 2; i < number; i++) {  
  
      if (arr[i] < min) {  
  
        amount += min;  
        min = arr[i];  
  
      } else {  
  
        if (arr[i] > max) {  
  
          amount += max;  
          max = arr[i];  
  
        } else {  
  
          amount += arr[i];  
        }  
      }  
    }  
  
    avg = (double)amount / (number - 2);  
  }  
  
  return avg;  
}  
  
float round_to_dp(float in_value, int decimal_place)  
{  
  float multiplier = powf(10.0f, decimal_place);  
  
  in_value =  
    roundf(in_value * multiplier) /  
    multiplier;  
  
  return in_value;  
}  
  
// ---------------- SETUP ----------------  
  
void setup() {  
  
  Serial.begin(115200);  
  
  analogReadResolution(12);  
  
  analogSetPinAttenuation(  
    SensorPin,  
    ADC_11db  
  );  
  
  analogSetPinAttenuation(  
    TURBIDEZ_PIN,  
    ADC_11db  
  );  
  
  analogSetPinAttenuation(  
    OD_PIN,  
    ADC_11db  
  );  
  
  conectarWiFi();  
  
  client.setServer(  
    mqtt_server,  
    1883  
  );  
}  
  
// ---------------- LOOP ----------------  
  
void loop() {  
  
  if (!client.connected()) {  
    reconnect();  
  }  
  
  client.loop();  
 
  // PH  
  
  if (millis() - samplingTime > samplingInterval) {  
  
    pHArray[pHArrayIndex++] =  
      analogRead(SensorPin);  
  
    if (pHArrayIndex == ArrayLenth) {  
      pHArrayIndex = 0;  
    }  
  
    voltagePH = avergearray (pHArray,ArrayLenth) * 3.3  / 4095.0;  
  
    pHValue = 3.5 * voltagePH + Offset;  
  
    samplingTime = millis();  
  }  
  
  // ENVIO  
  
  if (millis() - tiempoAnterior >= intervalo) {  
  
    tiempoAnterior = millis();  
 
    // TEMPERATURA  
  
    float temperatura =  
      getTemp();  
 
    // TURBIDEZ SEN0189  

    voltageTurbidity = 0;  
  
    for (int i = 0; i < 800; i++) {  
  
      tbz = analogRead(TURBIDEZ_PIN);  
  
      voltageTurbidity += tbz * (3.3 / 4095.0);  
    }  
  
    voltageTurbidity /= 800.0;  
  
    voltageSensor =  voltageTurbidity * ((5.1 + 10.0) / 10.0);  
  
    V_tbz = -1120.4 * voltageSensor * voltageSensor + 5742.3 * voltageSensor - 4352.9;
  
    if (V_tbz < 0) {  
      V_tbz = 0;  
    }
  
    // OXIGENO DISUELTO  
  
    Temperaturet = (uint8_t)READ_TEMP;  
  
    ADC_Raw = analogRead(OD_PIN);  
  
    ADC_Voltage = uint32_t(VREF) * ADC_Raw / ADC_RES;  
  
    DO = readDO(ADC_Voltage, Temperaturet);  
  
    oxigeno = DO / 1000.0;  
  
    // PH  
  
    float ph = pHValue;  
  
    // MONITOR SERIAL 
  
    Serial.println();  
    Serial.println("------------------------------------");  
  
    Serial.print("Temperatura: ");  
    Serial.print(temperatura);  
    Serial.println(" C");  
  
    Serial.print("Voltaje GPIO32: ");  
    Serial.print(voltageTurbidity, 3);  
    Serial.println(" V");  
  
    Serial.print("Voltaje SEN0189: ");  
    Serial.print(voltageSensor, 3);  
    Serial.println(" V");  
  
    Serial.print("Turbidez: ");  
    Serial.print(V_tbz, 2);  
    Serial.println(" NTU");  
  
    Serial.print("Oxigeno: ");  
    Serial.print(oxigeno, 3);  
    Serial.println(" mg/L");  
  
    Serial.print("Voltaje OD: ");  
    Serial.print(ADC_Voltage);  
    Serial.println(" mV");  
  
    Serial.print("pH: ");  
    Serial.println(ph);  
 
    // JSON MQTT  
  
    String payload = "{";  
    payload += "\"temp\":" + String(temperatura) + ",";  
    payload += "\"turbidez\":" + String(V_tbz) + ",";  
    payload += "\"oxigeno\":" + String(oxigeno) + ",";  
    payload += "\"ph\":" + String(ph);  
    payload += "}";  
  
    Serial.println(payload);  
  
    client.publish(  
      topic.c_str(),  
      payload.c_str()  
    );  
  }  
}