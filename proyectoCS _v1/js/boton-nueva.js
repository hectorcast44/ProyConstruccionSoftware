fetch('../partials/boton-nueva.html')
    .then(response => response.text())
    .then(html => {
        document.getElementById('contenedor-boton').innerHTML = html;
        feather.replace(); // sin esta línea el ícono no aparece
})
    .catch(err => console.error('Error al cargar el botón:', err));