document.addEventListener('DOMContentLoaded', function() {
    // Verifico el token; si no existe, regreso al login
    const token = localStorage.getItem('tokenUsuario');
    if (!token) {
      window.location.href = 'login.html';
      return;
    }
  
    // Recupero datos del usuario y muestro un saludo 
    const datos = JSON.parse(localStorage.getItem('datosUsuario') || '{}');
    const nombre = datos.nombre || datos.usuario || 'Usuario';
    document.getElementById('saludo').textContent =
      `¡Bienvenido, ${nombre}! Has iniciado sesión correctamente.`;
  
    // Manejador de cierre de sesión
    document.getElementById('btnLogout').addEventListener('click', function() {
      localStorage.removeItem('tokenUsuario');
      localStorage.removeItem('datosUsuario');
      window.location.href = 'login.html';
    });
  });
  