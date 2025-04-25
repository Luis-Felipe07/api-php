document.addEventListener('DOMContentLoaded', function() {
    // Aquí obtengo mis referencias a los elementos del DOM 
    const miFormularioRegistro = document.getElementById('formularioRegistro');
    const miElementoMensaje = document.getElementById('mensaje');
    
    // Creo esta función para mostrar mensajes al usuario de forma bonita
    function mostrarMiMensaje(texto, tipo) {
        miElementoMensaje.textContent = texto;
        miElementoMensaje.className = 'mensaje ' + tipo;
        miElementoMensaje.style.display = 'block';
        
        // Programo un temporizador para que el mensaje desaparezca después de 5 segundos
        setTimeout(() => {
            miElementoMensaje.style.display = 'none';
        }, 5000);
    }
    
    // Configuro el evento para cuando el usuario envíe el formulario
    miFormularioRegistro.addEventListener('submit', function(miEvento) {
        miEvento.preventDefault();
        
        // Recojo todos los valores que ha introducido el usuario en el formulario
        const miNombre = document.getElementById('nombre').value;
        const miUsuario = document.getElementById('usuario').value;
        const miCorreo = document.getElementById('correo').value;
        const miContrasena = document.getElementById('contrasena').value;
        const miConfirmacionContrasena = document.getElementById('confirmarContrasena').value;
        
        // Verifico que el usuario haya rellenado todos los campos
        if (!miNombre || !miUsuario || !miCorreo || !miContrasena || !miConfirmacionContrasena) {
            mostrarMiMensaje('Por favor, completa todos los campos', 'error');
            return;
        }
        
        // Me aseguro de que las contraseñas coincidan antes de enviar nada
        if (miContrasena !== miConfirmacionContrasena) {
            mostrarMiMensaje('Las contraseñas no coinciden', 'error');
            return;
        }
        
        // Preparo mis datos en un objeto para enviarlos a la API
        const misDatosUsuario = {
            nombre: miNombre,
            usuario: miUsuario,
            correo: miCorreo,
            contrasena: miContrasena
        };
        
        // Uso fetch para enviar los datos a el API de registro en PHP
        fetch('api/registro.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(misDatosUsuario)
        })
        .then(miRespuesta => {
            // Verifico si la respuesta de la API es correcta
            if (!miRespuesta.ok) {
                throw new Error('Error en la respuesta del servidor');
            }
            // Convierto la respuesta a formato JSON para poder trabajar con ella
            return miRespuesta.json();
        })
        .then(misDatos => {
            // Compruebo si el registro fue exitoso según lo que me diga la API
            if (misDatos.exito) {
                mostrarMiMensaje(misDatos.mensaje || '¡Registro exitoso!', 'exito');
                // Reseteo mi formulario para dejarlo limpio
                miFormularioRegistro.reset();
                
                // Redirijo al usuario a la página de login después de esperar 2 segundos
                setTimeout(() => {
                    window.location.href = 'login.html';
                }, 2000);
            } else {
                // Si hubo un problema, muestro el mensaje de error
                mostrarMiMensaje(misDatos.mensaje || 'Error en el registro', 'error');
            }
        })
        .catch(miError => {
            // Capturo cualquier error que pueda ocurrir durante la comunicación
            console.error('Error:', miError);
            mostrarMiMensaje('Error al conectar con el servidor', 'error');
        });
    });
});