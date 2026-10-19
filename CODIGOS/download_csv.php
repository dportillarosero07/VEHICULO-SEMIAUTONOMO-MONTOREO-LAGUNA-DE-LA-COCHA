<?php

$file = 'data.json';

if (!file_exists($file)) {
    die("No existe el archivo de datos.");
}

$data = file(
    $file,
    FILE_IGNORE_NEW_LINES |
    FILE_SKIP_EMPTY_LINES
);

header(
    'Content-Type: text/csv; charset=utf-8'
);

header(
    'Content-Disposition: attachment; filename=mediciones.csv'
);

$output = fopen(
    'php://output',
    'w'
);


fputcsv(
    $output,
    [
        'Hora',
        'Temperatura',
        'pH',
        'Oxigeno',
        'Turbidez'
    ]
);


foreach ($data as $line) {

    $obj = json_decode(
        $line,
        true
    );

    if ($obj) {

        fputcsv(
            $output,
            [
                $obj['time'] ?? '',
                $obj['temp'] ?? '',
                $obj['ph'] ?? '',
                $obj['oxigeno'] ?? '',
                $obj['turbidez'] ?? ''
            ]
        );

    }
}


fclose($output);

exit;

?>
