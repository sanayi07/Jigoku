const form = document.getElementById('registroForm');
const loading = document.getElementById('loading');

form.addEventListener('submit', async function(e) {
  e.preventDefault();
  loading.style.display = 'block';

  try {
    const resp = await fetch('registro.php', {
      method: 'POST',
      body: new FormData(form),
    });

    // 1) Loguea el texto crudo
    const text = await resp.text();
    console.log('Raw response:', text);

    // 2) Intenta parsear JSON
    let data;
    try {
      data = JSON.parse(text);
    } catch (parseErr) {
      throw new Error('Respuesta no es JSON válido');
    }
    console.log('Parsed JSON:', data);

    // 3) Manejo de la respuesta
    if (data.status === 'success') {
      alert(data.message || '¡Registro exitoso! (sin mensaje)');
      // Opcional: redirigir
      // window.location.href = 'bienvenida.html';
    } else if (data.status === 'error') {
      alert('Hubo un error: ' + (data.message || 'sin detalle'));
    } else {
      alert('Respuesta inesperada del servidor.');
    }

  } catch (err) {
    console.error('Error en el flujo de registro:', err);
    alert('Hubo un error de comunicación: ' + err.message);
  } finally {
    loading.style.display = 'none';
  }
});
