document.addEventListener("DOMContentLoaded", () => {
  const btnCrear = document.getElementById("crear-modal");
  const modal = document.getElementById("modal-nueva");
  const tbody = document.getElementById("tabla-body");

  btnCrear.addEventListener("click", () => {
    // Leer valores del modal
    const actividad = document.getElementById("actividad").value;
    const materia = document.getElementById("materia").value;
    const tipo = document.getElementById("tipo").value;
    const puntajeMax = document.getElementById("puntaje-max").value;
    const puntaje = document.getElementById("puntaje").value;
    const fecha = document.getElementById("fecha").value;

    // Crear fila
    const fila = document.createElement("tr");
    fila.innerHTML = `
      <td>${actividad}</td>
      <td>${materia}</td>
      <td>${tipo}</td>
      <td>${puntajeMax}</td>
      <td>${puntaje}</td>
      <td>${fecha}</td>
    `;

    // Insertar en tabla
    tbody.appendChild(fila);

    // Cerrar el modal
    modal.close();

    // Limpiar el formulario
    document.querySelector("#modal-nueva form").reset();

    // Volver a aplicar feather icons
    if (window.feather) feather.replace();
  });
});
