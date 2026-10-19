<?php

$archivo = '/var/www/html/predicciones.csv';

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=predicciones.csv');

if (!file_exists($archivo) || filesize($archivo) == 0) {

    $salida = fopen('php://output', 'w');

    fputcsv($salida, [
        'Hora',
        'Temperatura_pred',
        'pH_pred',
        'Oxigeno_pred',
        'Turbidez_pred'
    ]);

    fclose($salida);
    exit;
}

readfile($archivo);
exit;

?>
