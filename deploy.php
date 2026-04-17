<?php
// Asegurarnos de que PHP se ejecuta en la carpeta correcta
chdir(__DIR__);

// Ejecutar el comando de Git y capturar el resultado
$output = shell_exec('git pull origin main 2>&1');

// Mostrar el resultado (útil para diagnosticar errores)
echo "<h3>Resultado del Deploy:</h3>";
echo "<pre>$output</pre>";
?>