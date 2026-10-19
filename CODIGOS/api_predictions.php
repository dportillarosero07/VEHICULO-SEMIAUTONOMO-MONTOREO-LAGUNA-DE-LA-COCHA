<?php

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, no-store, must-revalidate');

$archivo = '/var/www/html/predicciones.csv';

$resultado = [];

if (!file_exists($archivo) || filesize($archivo) == 0) {
    echo json_encode($resultado);
    exit;
}

$fp = fopen($archivo, 'r');

if ($fp === false) {
    echo json_encode($resultado);
    exit;
}

$encabezado = fgetcsv($fp);

while (($fila = fgetcsv($fp)) !== false) {

    if (count($fila) < 5) {
        continue;
    }

    $resultado[] = [
        'time' => $fila[0],
        'temp' => floatval($fila[1]),
        'ph' => floatval($fila[2]),
        'oxigeno' => floatval($fila[3]),
        'turbidez' => floatval($fila[4])
    ];
}

fclose($fp);

echo json_encode($resultado);
?>
