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

// Verifico que lleguen los datos necesarios
if (!isset($datos['usuario']) || !isset($datos['contrasena'])) {
    echo json_encode([
        'exito' => false,
        'mensaje' => 'Faltan datos necesarios para el login.'
    ]);
    exit();
}

// Limpio los datos para prevenir inyecciones
$usuario = limpiarDato($datos['usuario']);
$contrasena = $datos['contrasena'];

// Intentar autenticar al usuario
try {
    // Obtengo la conexión a mi base de datos
    $miConexion = obtenerConexion();
    
    // Preparo mi consulta SQL (acepto login con usuario o correo)
    $miConsulta = "SELECT id, nombre, usuario, correo, contrasena FROM usuarios WHERE usuario = ? OR correo = ?";
    $miSentencia = $miConexion->prepare($miConsulta);
    $miSentencia->execute([$usuario, $usuario]);
    
    // Si el usuario existe, verifico la contraseña
    if ($miSentencia->rowCount() > 0) {
        $miUsuario = $miSentencia->fetch();
        
        // Verifico si la contraseña coincide
        if (password_verify($contrasena, $miUsuario['contrasena'])) {
            // Genero un token para la sesión 
            $miToken = generarToken($miUsuario['id']);
            
            // Actualizo la fecha de último acceso
            $miActualizacion = "UPDATE usuarios SET ultimo_acceso = NOW() WHERE id = ?";
            $miConexion->prepare($miActualizacion)->execute([$miUsuario['id']]);
            
            // Almaceno el token en la base de datos 
            almacenarToken($miConexion, $miUsuario['id'], $miToken);
            
            // Quito la contraseña del array de datos del usuario
            unset($miUsuario['contrasena']);
            
            // Devuelvo los datos del usuario y el token
            echo json_encode([
                'exito' => true,
                'mensaje' => '¡Bienvenido, ' . $miUsuario['nombre'] . '!',
                'token' => $miToken,
                'datosUsuario' => $miUsuario
            ]);
        } else {
            // Contraseña incorrecta
            echo json_encode([
                'exito' => false,
                'mensaje' => 'Contraseña incorrecta. Inténtalo de nuevo.'
            ]);
            
            // Registrar intento fallido para bloqueo por seguridad
            registrarIntentoFallido($miConexion, $usuario);
        }
    } else {
        // Usuario no encontrado
        echo json_encode([
            'exito' => false,
            'mensaje' => 'Usuario no encontrado. Verifica los datos o regístrate.'
        ]);
    }
    
} catch (PDOException $miError) {
    // Registro el error en mis logs para revisarlo después
    error_log("Error en login.php: " . $miError->getMessage());
    
    // Devuelvo un mensaje genérico al usuario
    echo json_encode([
        'exito' => false,
        'mensaje' => 'Ocurrió un error durante el login. Por favor, intenta más tarde.'
    ]);
}

/**
 * Función para generar un token único para la sesión del usuario
 * 
 * @param int $idUsuario ID del usuario
 * @return string Token generado
 */
function generarToken($idUsuario) {
    // Genero un token único con información aleatoria y el ID del usuario
    $miToken = bin2hex(random_bytes(32)) . '_' . $idUsuario;
    
    // Un token real podría ser un JWT firmado, pero para este ejemplo uso uno simple
    return $miToken;
}

/**
 * Función para almacenar el token en la base de datos
 * 
 * @param PDO $conexion Conexión a la base de datos
 * @param int $idUsuario ID del usuario
 * @param string $token Token generado
 */
function almacenarToken($conexion, $idUsuario, $token) {
    // Establezco una fecha de expiración (1 día)
    $fechaExpiracion = date('Y-m-d H:i:s', strtotime('+1 day'));
    
    // Obtengo información sobre el dispositivo y la IP (básico)
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'desconocida';
    $dispositivo = $_SERVER['HTTP_USER_AGENT'] ?? 'desconocido';
    
    // Guardo el token en la base de datos
    $consulta = "INSERT INTO sesiones (id_usuario, token, fecha_expiracion, ip, dispositivo) 
                VALUES (?, ?, ?, ?, ?)";
    $conexion->prepare($consulta)->execute([$idUsuario, $token, $fechaExpiracion, $ip, $dispositivo]);
}

/**
 * Función para registrar intentos fallidos de login
 * 
 * @param PDO $conexion Conexión a la base de datos
 * @param string $usuario Nombre de usuario o correo
 */
function registrarIntentoFallido($conexion, $usuario) {
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'desconocida';
    
    // Verifico si ya hay intentos registrados para este usuario/IP
    $consulta = "SELECT intentos FROM intentos_login WHERE usuario = ? AND ip = ?";
    $sentencia = $conexion->prepare($consulta);
    $sentencia->execute([$usuario, $ip]);
    
    if ($sentencia->rowCount() > 0) {
        // Si ya existe, incremento el contador
        $resultado = $sentencia->fetch();
        $intentos = $resultado['intentos'] + 1;
        
        // Si llega a 5 intentos, bloqueo por 15 minutos
        $bloqueado = $intentos >= 5 ? date('Y-m-d H:i:s', strtotime('+15 minutes')) : null;
        
        $actualizar = "UPDATE intentos_login SET intentos = ?, bloqueado_hasta = ?, 
                      ultimo_intento = NOW() WHERE usuario = ? AND ip = ?";
        $conexion->prepare($actualizar)->execute([$intentos, $bloqueado, $usuario, $ip]);
    } else {
        // Si es el primer intento, creo un nuevo registro
        $insertar = "INSERT INTO intentos_login (usuario, ip) VALUES (?, ?)";
        $conexion->prepare($insertar)->execute([$usuario, $ip]);
    }
}
?>