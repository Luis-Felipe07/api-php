<?php

// Aquí configuro los datos de conexión para la base de datos
define('SERVIDOR_BD', 'localhost');     
define('USUARIO_BD', 'root');          
define('CLAVE_BD', '');              
define('NOMBRE_BD', 'sistema_autenticacion'); 
define('CARACTERES_BD', 'utf8mb4');    

/**
 * Esta función me permite establecer una conexión a la base de datos
 * 
 * @return PDO Objeto de conexión PDO
 */
function obtenerConexion() {
    static $conexion;
    
    // Si ya hay una conexión abierta, simplemente la devuelvo
    if ($conexion instanceof PDO) {
        return $conexion;
    }
    
    // Armo el DSN que necesito para conectarme con PDO
    $dsn = "mysql:host=" . SERVIDOR_BD . ";dbname=" . NOMBRE_BD . ";charset=" . CARACTERES_BD;
    
    // Defino las opciones para personalizar el comportamiento de PDO
    $opciones = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,  
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,  
        PDO::ATTR_EMULATE_PREPARES => false, 
        PDO::ATTR_PERSISTENT => true   
    ];
    
    try {
        // Creo y devuelvo una nueva instancia de conexión PDO
        $conexion = new PDO($dsn, USUARIO_BD, CLAVE_BD, $opciones);
        return $conexion;
    } catch (PDOException $e) {
        // Si estuviera en producción, no mostraria detalles del error al usuario
        // En vez de eso, registraria el error y mostraria un mensaje genérico
        error_log("Error de conexión a la base de datos: " . $e->getMessage());
        die("No se pudo conectar a la base de datos. Por favor, contacte al administrador.");
    }
}

// Esta función la uso para limpiar los datos antes de usarlos y así prevenir inyecciones SQL y otros ataques
function limpiarDato($dato) {
    $dato = trim($dato);
    $dato = stripslashes($dato);
    $dato = htmlspecialchars($dato);
    return $dato;
}
?>
