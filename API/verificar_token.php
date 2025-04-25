<?php
// Configuro los encabezados para permitir peticiones AJAX y JSON
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Incluyo mi archivo de conexión a la base de datos
require_once '../CONFIG/conexion.php';

// Verifico que la petición sea POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    // Devuelvo un error si no es POST
    echo json_encode([
        'valido' => false,
        'mensaje' => 'Método no permitido. Utiliza POST para esta petición.'
    ]);
    exit();
}

// Obtengo el token del encabezado Authorization
$miHeaders = getallheaders();
$miAutorizacion = isset($miHeaders['Authorization']) ? $miHeaders['Authorization'] : '';

// Verifico si el token está presente
if (empty($miAutorizacion) || !preg_match('/Bearer\s+(.*)/', $miAutorizacion, $miCoincidencias)) {
    echo json_encode([
        'valido' => false,
        'mensaje' => 'Token no proporcionado o formato inválido.'
    ]);
    exit();
}

// Extraigo el token de la cadena 
$miToken = $miCoincidencias[1];

try {
    // Obtengo la conexión a mi base de datos
    $miConexion = obtenerConexion();
    
    // Preparo mi consulta SQL para verificar el token
    $miConsulta = "SELECT s.id, s.id_usuario, s.fecha_expiracion, u.nombre, u.usuario, u.correo 
                  FROM sesiones s
                  JOIN usuarios u ON s.id_usuario = u.id
                  WHERE s.token = ? AND s.fecha_expiracion > NOW()";
    $miSentencia = $miConexion->prepare($miConsulta);
    $miSentencia->execute([$miToken]);
    
    // Verifico si el token existe y es válido
    if ($miSentencia->rowCount() > 0) {
        $miSesion = $miSentencia->fetch();
        
        // Devuelvo la información
        echo json_encode([
            'valido' => true,
            'mensaje' => 'Token válido',
            'datosUsuario' => [
                'id' => $miSesion['id_usuario'],
                'nombre' => $miSesion['nombre'],
                'usuario' => $miSesion['usuario'],
                'correo' => $miSesion['correo']
            ]
        ]);
    } else {
        // Si el token no es válido o expiró
        echo json_encode([
            'valido' => false,
            'mensaje' => 'Token inválido o expirado.'
        ]);
    }
    
} catch (PDOException $miError) {
    // Registro el error en mis logs para revisarlo después
    error_log("Error en verificar_token.php: " . $miError->getMessage());
    
    // Devuelvo un mensaje genérico
    echo json_encode([
        'valido' => false,
        'mensaje' => 'Error al verificar el token. Por favor, inicia sesión nuevamente.'
    ]);
}
?>