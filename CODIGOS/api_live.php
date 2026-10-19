<?php

$file = file("/var/www/html/data.json", FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

$ultimos = array_slice($file, -30);

echo json_encode($ultimos);

?>
