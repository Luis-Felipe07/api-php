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
        'exito' => false,
        'mensaje' => 'Método no permitido. Utiliza POST para esta petición.'
    ]);
    exit();
}

// Obtengo los datos enviados en la petición
$datos = json_decode(file_get_contents("php://input"), true);

// Verifico que lleguen todos los datos necesarios
if (!isset($datos['nombre']) || !isset($datos['usuario']) || !isset($datos['correo']) || !isset($datos['contrasena'])) {
    echo json_encode([
        'exito' => false,
        'mensaje' => 'Faltan datos necesarios para el registro.'
    ]);
    exit();
}

// Limpio los datos para prevenir inyecciones sql
$nombre = limpiarDato($datos['nombre']);
$usuario = limpiarDato($datos['usuario']);
$correo = limpiarDato($datos['correo']);
$contrasena = $datos['contrasena']; // No limpio la contraseña aquí, la procesaré aparte

// Valido el formato del correo electrónico
if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
    echo json_encode([
        'exito' => false,
        'mensaje' => 'El formato del correo electrónico no es válido.'
    ]);
    exit();
}

// Valido que la contraseña cumpla con requisitos mínimos
if (strlen($contrasena) < 6) {
    echo json_encode([
        'exito' => false,
        'mensaje' => 'La contraseña debe tener al menos 6 caracteres.'
    ]);
    exit();
}

// Intentar registrar al usuario
try {
    // Obtengo la conexión a mi base de datos
    $miConexion = obtenerConexion();
    
    // Primero verifico si el usuario ya existe
    $miConsultaUsuario = "SELECT id FROM usuarios WHERE usuario = ? OR correo = ?";
    $miSentencia = $miConexion->prepare($miConsultaUsuario);
    $miSentencia->execute([$usuario, $correo]);
    
    if ($miSentencia->rowCount() > 0) {
        echo json_encode([
            'exito' => false,
            'mensaje' => 'El nombre de usuario o correo electrónico ya está registrado.'
        ]);
        exit();
    }
    
    // Hash de la contraseña 
    $hashContrasena = password_hash($contrasena, PASSWORD_DEFAULT);
    
    // Preparo mi consulta SQL para insertar el nuevo usuario
    $miConsultaInsertar = "INSERT INTO usuarios (nombre, usuario, correo, contrasena) VALUES (?, ?, ?, ?)";
    $miSentenciaInsertar = $miConexion->prepare($miConsultaInsertar);
    $miSentenciaInsertar->execute([$nombre, $usuario, $correo, $hashContrasena]);
    
    // Verifico si la inserción fue exitosa
    if ($miSentenciaInsertar->rowCount() > 0) {
        echo json_encode([
            'exito' => true,
            'mensaje' => '¡Registro exitoso! Ahora puedes iniciar sesión.',
            'id_usuario' => $miConexion->lastInsertId()
        ]);
    } else {
        echo json_encode([
            'exito' => false,
            'mensaje' => 'No se pudo completar el registro. Intenta de nuevo más tarde.'
        ]);
    }
    
} catch (PDOException $miError) {
    // Registro el error en mis logs para revisarlo después
    error_log("Error en registro.php: " . $miError->getMessage());
    
    // Devuelvo un mensaje genérico al usuario
    echo json_encode([
        'exito' => false,
        'mensaje' => 'Ocurrió un error durante el registro. Por favor, intenta más tarde.'
    ]);
}
?>