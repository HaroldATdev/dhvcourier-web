<?php
/**
 * DHV Deployment Script - Token Based
 * Uso: dhvcourier.com/tu-archivo.php?auth_key=TU_TOKEN_AQUI
 */

// 1. CONFIGURACIÓN
// Usa un token alfanumérico complejo
$secret_token = 'DHV_PRO_2026_TRX_9921_SECRET'; 

// 2. VALIDACIÓN
if (!isset($_GET['auth_key']) || $_GET['auth_key'] !== $secret_token) {
    header('HTTP/1.1 404 Not Found'); // Engañamos al atacante fingiendo que el archivo no existe
    exit;
}

// 3. EJECUCIÓN DE COMANDOS
// Usamos una función para ejecutar, así es más fácil de mantener
function run_task($cmd) {
    echo "<b>> $cmd</b>\n";
    $result = shell_exec($cmd . " 2>&1");
    echo htmlspecialchars($result) . "\n";
}

header('Content-Type: text/plain'); // Lo vemos como texto plano en el navegador
echo "--- DHV DEPLOYMENT SYSTEM --- \n\n";

try {
    // Comandos de Git
    // Asegúrate de que el usuario del servidor tenga los permisos necesarios
    run_task('git fetch --all');
    run_task('git reset --hard origin/main');
    
    // Si necesitas limpiar caché de WordPress o plugins específicos
    // run_task('wp cache flush'); 

    echo "\nActualización completada correctamente.";
} catch (Exception $e) {
    echo "Error durante el despliegue: " . $e->getMessage();
}