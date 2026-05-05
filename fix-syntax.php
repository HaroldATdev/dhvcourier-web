<?php
// Fix the syntax error by removing extra parenthesis

$file = __DIR__ . '/wp-content/plugins/wp-cargo-facturacion/includes/class-constructor-guia.php';
$content = file_get_contents($file);

// Buscar y reemplazar el patrón problemático
// Estamos buscando: )  \n	)\n),
// Y reemplazaremos con: )\n		),

$content = preg_replace(
    "/(\t{3}\))\n(\t{2}\))\n(\t)\),/",
    "$1\n\t\t),",
    $content
);

file_put_contents($file, $content);
echo "Archivo corregido correctamente!";
?>
