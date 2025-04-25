

// Espero a que el DOM esté completamente cargado antes de ejecutarel código
document.addEventListener('DOMContentLoaded', function() {
    // Obtengo una referencia al formulario de login
    const miFormularioLogin = document.getElementById('formularioLogin');
    
    // Voy a añadir un div para mostrar mensajes al usuario
    const miContenedor = document.querySelector('body');
    const miElementoMensaje = document.createElement('div');
    miElementoMensaje.id = 'mensaje';
    miElementoMensaje.className = 'mensaje';
    miElementoMensaje.style.display = 'none';
    miContenedor.appendChild(miElementoMensaje);
    
    // Creo una función para mostrar mensajes al usuario
    function mostrarMiMensaje(texto, tipo) {
        miElementoMensaje.textContent = texto;
        miElementoMensaje.className = 'mensaje ' + tipo;
        miElementoMensaje.style.display = 'block';
        
        // Configuro un temporizador para ocultar el mensaje después de 4 segundos
        setTimeout(() => {
            miElementoMensaje.style.display = 'none';
        }, 4000);
    }
    
    // Añado un manejador de eventos para cuando se envíe el formulario
    miFormularioLogin.addEventListener('submit', function(miEvento) {
        // Evito que el formulario se envíe de forma predeterminada
        miEvento.preventDefault();
        
        // Obtengo los valores ingresados por el usuario
        const miUsuario = document.getElementById('usuario').value;
        const miContrasena = document.getElementById('contrasena').value;
        
        // Verifico que los campos no estén vacíos
        if (!miUsuario || !miContrasena) {
            mostrarMiMensaje('Por favor, completa todos los campos', 'error');
            return;
        }
        
        // Preparo los datos para enviar a mi API
        const misDatosLogin = {
            usuario: miUsuario,
            contrasena: miContrasena
        };
        
        // Muestro un mensaje mientras se procesa la solicitud
        mostrarMiMensaje('Verificando credenciales...', 'info');
        
        // Envío los datos a mi API PHP usando fetch
        fetch('api/login.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(misDatosLogin)
        })
        .then(miRespuesta => {
            // Verifico si la respuesta HTTP es correcta
            if (!miRespuesta.ok) {
                throw new Error('Error en la conexión con el servidor');
            }
            // Convierto la respuesta a formato JSON
            return miRespuesta.json();
        })
        .then(misDatos => {
            // Proceso la respuesta de mi API
            if (misDatos.exito) {
                mostrarMiMensaje(misDatos.mensaje || '¡Inicio de sesión exitoso!', 'exito');
                
                // Si el API devuelve un token, lo guardo en el almacenamiento local
                if (misDatos.token) {
                    localStorage.setItem('tokenUsuario', misDatos.token);
                }
                
                // Si el API devuelve datos del usuario, también los guardo
                if (misDatos.datosUsuario) {
                    localStorage.setItem('datosUsuario', JSON.stringify(misDatos.datosUsuario));
                }
                
                // Redirecciono a la pagina bienvenido después de 1.5 segundos
                setTimeout(() => {
                    window.location.href = 'bienvenido.html';
                }, 1500);
            } else {
                // Muestro el mensaje de error que envía mi API
                mostrarMiMensaje(misDatos.mensaje || 'Usuario o contraseña incorrectos', 'error');
            }
        })
        .catch(miError => {
            // Capturo y manejo cualquier error que ocurra durante la comunicación
            console.error('Error en mi solicitud:', miError);
            mostrarMiMensaje('Error al conectar con el servidor. Intenta nuevamente más tarde.', 'error');
        });
    });
    
    // Verifico si ya hay una sesión activa al cargar la página
    function verificarMiSesion() {
        const miToken = localStorage.getItem('tokenUsuario');
        if (miToken) {
            // Si existe un token, podría verificarlo con el servidor 
            fetch('api/verificar_token.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': 'Bearer ' + miToken
                }
            })
            .then(miRespuesta => miRespuesta.json())
            .then(misDatos => {
                if (misDatos.valido) {
                    window.location.href = 'bienvenido.html';
                } else {
                    // Si el token no es válido, lo elimino
                    localStorage.removeItem('tokenUsuario');
                    localStorage.removeItem('datosUsuario');
                }
            })
            .catch(miError => {
                console.error('Error al verificar sesión:', miError);
            });
        }
    }
    
    // Ejecuto la verificación de sesión al cargar la página
    verificarMiSesion();
});