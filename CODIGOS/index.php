<!DOCTYPE html>
<html lang="es">

<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Dashboard Monitoreo Laguna de la Cocha</title>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: Arial, sans-serif;
    background: linear-gradient(135deg, #0a0f1f, #111827, #0f172a);
    color: white;
    text-align: center;
    padding: 20px;
}

.container {
    max-width: 1600px;
    margin: auto;
}

h1 {
    font-size: 3rem;
    margin-bottom: 30px;
    color: #00d9ff;
}

.valores {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 20px;
    margin-bottom: 35px;
}

.card {
    background: rgba(255,255,255,0.08);
    border: 1px solid rgba(255,255,255,0.08);
    backdrop-filter: blur(10px);
    border-radius: 18px;
    padding: 25px;
}

.card h2 {
    font-size: 1.6rem;
    color: #00d9ff;
    margin-bottom: 12px;
}

.card p {
    font-size: 2.2rem;
    font-weight: bold;
}

.boton-descarga {
    margin: 25px 0 40px;
    display: flex;
    justify-content: center;
    gap: 20px;
    flex-wrap: wrap;
}

.boton-descarga a {
    display: inline-block;
    text-decoration: none;
    background: linear-gradient(90deg, #00d9ff, #00aaff);
    color: black;
    padding: 16px 34px;
    border-radius: 14px;
    font-size: 1.2rem;
    font-weight: bold;
}

.graficas {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 30px;
}

.chart-container {
    background: white;
    padding: 22px;
    border-radius: 20px;
}

.chart-container h3 {
    color: black;
    margin-bottom: 15px;
    font-size: 1.5rem;
}

canvas {
    width: 100% !important;
    height: 360px !important;
}

@media (max-width: 1000px) {

    .valores {
        grid-template-columns: repeat(2, 1fr);
    }

    .graficas {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 600px) {

    h1 {
        font-size: 2rem;
    }

    .valores {
        grid-template-columns: 1fr;
    }

    .card p {
        font-size: 1.7rem;
    }

    .card h2 {
        font-size: 1.3rem;
    }

    canvas {
        height: 300px !important;
    }
}

</style>

</head>

<body>

<div class="container">

<h1>Monitoreo Variables Laguna de la Cocha</h1>

<div class="valores">

<div class="card">
<h2>Temperatura</h2>
<p id="v_temp">--</p>
</div>

<div class="card">
<h2>pH</h2>
<p id="v_ph">--</p>
</div>

<div class="card">
<h2>Oxígeno</h2>
<p id="v_o2">--</p>
</div>

<div class="card">
<h2>Turbidez</h2>
<p id="v_turb">--</p>
</div>

</div>

<div class="boton-descarga">

<a href="download_csv.php">
Descargar Mediciones CSV
</a>

<a href="download_predictions.php">
Descargar Predicciones CSV
</a>

</div>

<div class="graficas">

<div class="chart-container">
<h3>pH</h3>
<canvas id="ph"></canvas>
</div>

<div class="chart-container">
<h3>Temperatura</h3>
<canvas id="temp"></canvas>
</div>

<div class="chart-container">
<h3>Turbidez</h3>
<canvas id="turb"></canvas>
</div>

<div class="chart-container">
<h3>Oxígeno disuelto</h3>
<canvas id="o2"></canvas>
</div>

</div>

</div>


<script>

let charts = {};


// ==========================================
// CARGAR DATOS REALES Y PREDICCIONES
// ==========================================

async function cargar() {

    try {

        const [
            respuestaDatos,
            respuestaPredicciones
        ] = await Promise.all([

            fetch(
                "api_live.php?nocache=" +
                Date.now()
            ),

            fetch(
                "api_predictions.php?nocache=" +
                Date.now()
            )

        ]);


        const data =
            await respuestaDatos.json();

        const predicciones =
            await respuestaPredicciones.json();


        let temp = [];
        let ph = [];
        let o2 = [];
        let turb = [];
        let labels = [];


        // ======================================
        // DATOS REALES
        // ======================================

        data.forEach(d => {

            let obj;

            if (typeof d === "string") {

                obj = JSON.parse(d);

            } else {

                obj = d;
            }


            labels.push(
                obj.time
            );

            temp.push(
                parseFloat(
                    obj.temp
                )
            );

            ph.push(
                parseFloat(
                    obj.ph
                )
            );

            o2.push(
                parseFloat(
                    obj.oxigeno
                )
            );

            turb.push(
                parseFloat(
                    obj.turbidez
                )
            );

        });


        // ======================================
        // MOSTRAR ÚLTIMA MEDICIÓN
        // ======================================

        if (data.length > 0) {

            let ultimo;

            let ultimoDato =
                data[
                    data.length - 1
                ];

            if (
                typeof ultimoDato
                === "string"
            ) {

                ultimo =
                    JSON.parse(
                        ultimoDato
                    );

            } else {

                ultimo =
                    ultimoDato;
            }


            document
                .getElementById(
                    "v_temp"
                )
                .innerText =
                ultimo.temp +
                " °C";


            document
                .getElementById(
                    "v_ph"
                )
                .innerText =
                ultimo.ph;


            document
                .getElementById(
                    "v_o2"
                )
                .innerText =
                ultimo.oxigeno +
                " mg/L";


            document
                .getElementById(
                    "v_turb"
                )
                .innerText =
                ultimo.turbidez +
                " NTU";
        }


        // ======================================
        // ÚLTIMA PREDICCIÓN GENERADA POR PYTHON
        // ======================================

        let ultimaPrediccion = null;

        if (
            predicciones.length > 0
        ) {

            ultimaPrediccion =
                predicciones[
                    predicciones.length - 1
                ];
        }


        // ======================================
        // GRAFICAS
        // ======================================

        draw(
            "ph",
            "pH",
            labels,
            ph,
            "green",
            ultimaPrediccion
                ? ultimaPrediccion.ph
                : null,
            ultimaPrediccion
                ? ultimaPrediccion.time
                : null
        );


        draw(
            "temp",
            "Temperatura",
            labels,
            temp,
            "blue",
            ultimaPrediccion
                ? ultimaPrediccion.temp
                : null,
            ultimaPrediccion
                ? ultimaPrediccion.time
                : null
        );


        draw(
            "turb",
            "Turbidez",
            labels,
            turb,
            "orange",
            ultimaPrediccion
                ? ultimaPrediccion.turbidez
                : null,
            ultimaPrediccion
                ? ultimaPrediccion.time
                : null
        );


        draw(
            "o2",
            "Oxígeno disuelto",
            labels,
            o2,
            "purple",
            ultimaPrediccion
                ? ultimaPrediccion.oxigeno
                : null,
            ultimaPrediccion
                ? ultimaPrediccion.time
                : null
        );


    } catch (error) {

        console.error(
            "Error cargando datos:",
            error
        );
    }
}


// ==========================================
// DIBUJAR GRAFICA
// ==========================================

function draw(
    id,
    label,
    labels,
    datos,
    color,
    prediccion,
    horaPrediccion
) {

    if (
        charts[id]
    ) {

        charts[id].destroy();
    }


    if (
        labels.length === 0
    ) {

        return;
    }


    let labelsGrafica =
        [...labels];

    let datosReales =
        [...datos];

    let datosPrediccion =
        new Array(
            datos.length
        ).fill(
            null
        );


    // ======================================
    // AGREGAR PREDICCION SOLO SI EXISTE
    // ======================================

    if (
        prediccion !== null &&
        horaPrediccion !== null
    ) {

        labelsGrafica.push(
            horaPrediccion
        );

        datosReales.push(
            null
        );


        // Último punto real para unir
        // visualmente con la predicción

        if (
            datos.length > 0
        ) {

            datosPrediccion[
                datos.length - 1
            ] =
                datos[
                    datos.length - 1
                ];
        }


        datosPrediccion.push(
            Number(
                prediccion
            )
        );

    } else {

        datosPrediccion.push(
            null
        );
    }


    charts[id] =
        new Chart(
            document.getElementById(
                id
            ),
            {

                type:
                    "line",

                data: {

                    labels:
                        labelsGrafica,

                    datasets: [

                        {

                            label:
                                label,

                            data:
                                datosReales,

                            borderColor:
                                color,

                            backgroundColor:
                                color,

                            fill:
                                false,

                            tension:
                                0.25,

                            pointRadius:
                                3,

                            borderWidth:
                                3
                        },


                        {

                            label:
                                "Predicción +5 min",

                            data:
                                datosPrediccion,

                            borderColor:
                                "gray",

                            backgroundColor:
                                "gray",

                            fill:
                                false,

                            tension:
                                0,

                            borderWidth:
                                3,

                            borderDash:
                                [6, 5],

                            pointRadius:
                                5,

                            pointBackgroundColor:
                                "gray",

                            pointBorderColor:
                                "gray",

                            spanGaps:
                                true
                        }

                    ]
                },


                options: {

                    responsive:
                        true,

                    maintainAspectRatio:
                        false,


                    interaction: {

                        intersect:
                            false,

                        mode:
                            "index"
                    },


                    plugins: {

                        legend: {

                            labels: {

                                color:
                                    "black",

                                font: {

                                    size:
                                        15
                                }
                            }
                        },


                        tooltip: {

                            callbacks: {

                                label:
                                    function(
                                        context
                                    ) {

                                        let valor =
                                            context.parsed.y;

                                        if (
                                            valor === null
                                            ||
                                            valor === undefined
                                        ) {

                                            return "";
                                        }

                                        return (
                                            context
                                                .dataset
                                                .label

                                            +

                                            ": "

                                            +

                                            Number(
                                                valor
                                            ).toFixed(
                                                4
                                            )
                                        );
                                    }
                            }
                        }
                    },


                    scales: {

                        x: {

                            ticks: {

                                color:
                                    "black",

                                maxRotation:
                                    45,

                                minRotation:
                                    45,

                                font: {

                                    size:
                                        11
                                }
                            }
                        },


                        y: {

                            ticks: {

                                color:
                                    "black",

                                font: {

                                    size:
                                        12
                                },

                                callback:
                                    function(
                                        value
                                    ) {

                                        return Number(
                                            value
                                        ).toFixed(
                                            3
                                        );
                                    }
                            }
                        }
                    }
                }
            }
        );
}


// ==========================================
// REVISAR CADA 3 SEGUNDOS
// ==========================================

setInterval(
    cargar,
    3000
);

cargar();

</script>

</body>

</html>
