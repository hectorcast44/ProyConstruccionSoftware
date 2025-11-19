# API Reference - Módulo de Actividades

## 📌 Endpoints Disponibles

### 1. Actividades (`/php/api/actividad.php`)

#### GET - Obtener Actividades (con filtros)
```http
GET /php/api/actividad.php
GET /php/api/actividad.php?id_materia=2
GET /php/api/actividad.php?estado=pendiente
GET /php/api/actividad.php?buscar=examen
```

**Parámetros Query:**
| Parámetro | Tipo | Requerido | Descripción |
|-----------|------|-----------|-------------|
| `id_materia` | int | No | Filtrar por materia específica |
| `id_tipo_actividad` | int | No | Filtrar por tipo de actividad |
| `estado` | string | No | Valores: `pendiente`, `en proceso`, `completado` |
| `fecha_desde` | date | No | Formato: YYYY-MM-DD |
| `fecha_hasta` | date | No | Formato: YYYY-MM-DD |
| `buscar` | string | No | Buscar en nombre de actividad |

**Respuesta Exitosa (200):**
```json
{
  "status": "success",
  "data": [
    {
      "id_actividad": 1,
      "id_materia": 2,
      "id_tipo_actividad": 1,
      "id_usuario": 1,
      "nombre_actividad": "Tarea 1: Integrales",
      "fecha_entrega": "2025-11-15",
      "estado": "pendiente",
      "puntos_posibles": 10.00,
      "puntos_obtenidos": null,
      "nombre_tipo": "Tarea",
      "nombre_materia": "Cálculo Integral"
    }
  ],
  "total": 1
}
```

---

#### POST - Crear Actividad
```http
POST /php/api/actividad.php
Content-Type: application/json
```

**Body:**
```json
{
  "id_materia": 2,
  "id_tipo_actividad": 1,
  "nombre_actividad": "Tarea 5: Series infinitas",
  "fecha_entrega": "2025-12-01",
  "estado": "pendiente",
  "puntos_posibles": 15.00,
  "puntos_obtenidos": null
}
```

**Validaciones:**
- ✓ Todos los campos son obligatorios excepto `puntos_posibles` y `puntos_obtenidos`
- ✓ Estado debe ser: `pendiente`, `en proceso` o `completado`
- ✓ Si hay `puntos_obtenidos`, debe haber `puntos_posibles` > 0
- ✓ `puntos_obtenidos` ≤ `puntos_posibles`
- ✓ `puntos_obtenidos` ≥ 0

**Respuesta Exitosa (201):**
```json
{
  "status": "success",
  "message": "Actividad creada.",
  "id_actividad": 22
}
```

**Errores Comunes:**
```json
// 400 - Estado inválido
{
  "status": "error",
  "message": "Estado inválido. Los estados válidos son: pendiente, en proceso, completado."
}

// 400 - Puntos obtenidos sin puntos posibles
{
  "status": "error",
  "message": "RF-034: No se pueden asignar 'Puntos obtenidos' si la actividad no tiene 'Puntos posibles'."
}

// 400 - Puntos obtenidos mayores
{
  "status": "error",
  "message": "RF-036: 'Puntos obtenidos' no pueden ser mayores a 'Puntos posibles'."
}
```

---

#### PUT - Editar Actividad
```http
PUT /php/api/actividad.php
Content-Type: application/json
```

**Body:**
```json
{
  "id_actividad": 22,
  "id_materia": 2,
  "id_tipo_actividad": 1,
  "nombre_actividad": "Tarea 5: Series infinitas (ACTUALIZADA)",
  "fecha_entrega": "2025-12-05",
  "estado": "en proceso",
  "puntos_posibles": 15.00,
  "puntos_obtenidos": 10.00
}
```

**Validaciones Adicionales:**
- ✓ No se puede cambiar estado desde `completado` a otro estado
- ✓ Todas las validaciones de POST aplican

**Respuesta Exitosa (200):**
```json
{
  "status": "success",
  "message": "Actividad actualizada."
}
```

**Errores:**
```json
// 400 - Transición de estado inválida
{
  "status": "error",
  "message": "No se puede cambiar el estado de una actividad completada a otro estado."
}

// 404 - Actividad no encontrada
{
  "status": "error",
  "message": "Actividad no encontrada o no pertenece al usuario."
}
```

---

#### DELETE - Eliminar Actividad
```http
DELETE /php/api/actividad.php?id=22
```

**Parámetros URL:**
| Parámetro | Tipo | Requerido | Descripción |
|-----------|------|-----------|-------------|
| `id` | int | Sí | ID de la actividad a eliminar |

**Validaciones:**
- ✓ Solo se pueden eliminar actividades NO calificables (`puntos_posibles` = NULL)

**Respuesta Exitosa (200):**
```json
{
  "status": "success",
  "message": "Actividad eliminada."
}
```

**Error:**
```json
// 400 - Actividad calificable
{
  "status": "error",
  "message": "RF-003: No se puede eliminar una actividad que es calificable."
}

// 404 - No encontrada
{
  "status": "error",
  "message": "Actividad no encontrada o no pertenece al usuario."
}
```

---

### 2. Tipos de Actividad (`/php/api/tipo_actividad.php`)

#### GET - Obtener Todos los Tipos
```http
GET /php/api/tipo_actividad.php
```

**Respuesta (200):**
```json
{
  "status": "success",
  "data": [
    { "id_tipo_actividad": 1, "nombre_tipo": "Tarea" },
    { "id_tipo_actividad": 2, "nombre_tipo": "Proyecto" },
    { "id_tipo_actividad": 3, "nombre_tipo": "Examen" }
  ]
}
```

---

#### GET - Obtener Tipo Específico
```http
GET /php/api/tipo_actividad.php?id=1
```

**Respuesta (200):**
```json
{
  "status": "success",
  "data": {
    "id_tipo_actividad": 1,
    "nombre_tipo": "Tarea"
  }
}
```

**Error (404):**
```json
{
  "status": "error",
  "message": "Tipo de actividad no encontrado."
}
```

---

#### POST - Crear Tipo
```http
POST /php/api/tipo_actividad.php
Content-Type: application/json
```

**Body:**
```json
{
  "nombre_tipo": "Práctica de Laboratorio"
}
```

**Validaciones:**
- ✓ `nombre_tipo` es obligatorio y no vacío
- ✓ El nombre debe ser único por usuario

**Respuesta (201):**
```json
{
  "status": "success",
  "message": "Tipo de actividad creado exitosamente.",
  "id_tipo_actividad": 6
}
```

**Error (400):**
```json
{
  "status": "error",
  "message": "Ya existe un tipo de actividad con ese nombre."
}
```

---

#### PUT - Editar Tipo
```http
PUT /php/api/tipo_actividad.php
Content-Type: application/json
```

**Body:**
```json
{
  "id_tipo_actividad": 6,
  "nombre_tipo": "Práctica de Laboratorio Avanzada"
}
```

**Validaciones:**
- ✓ El tipo debe existir y pertenecer al usuario
- ✓ El nuevo nombre no debe estar en uso (por otro tipo)

**Respuesta (200):**
```json
{
  "status": "success",
  "message": "Tipo de actividad actualizado exitosamente."
}
```

---

#### DELETE - Eliminar Tipo
```http
DELETE /php/api/tipo_actividad.php?id=6
```

**Validaciones:**
- ✓ El tipo no debe estar siendo usado en actividades
- ✓ El tipo no debe estar siendo usado en ponderaciones

**Respuesta (200):**
```json
{
  "status": "success",
  "message": "Tipo de actividad eliminado exitosamente."
}
```

**Error (400):**
```json
{
  "status": "error",
  "message": "No se puede eliminar este tipo de actividad porque está siendo usado en 5 actividad(es)."
}
```

---

## 🔒 Autenticación

Todos los endpoints requieren sesión activa:

```php
// Antes de cada request, el usuario debe estar autenticado
session_start();
$_SESSION['id_usuario'] = 1; // Ejemplo
```

**Error de Autenticación (401):**
```json
{
  "status": "error",
  "message": "No autorizado. Por favor, inicie sesión."
}
```

---

## 🎨 Códigos de Estado HTTP

| Código | Significado | Uso |
|--------|-------------|-----|
| 200 | OK | GET, PUT, DELETE exitosos |
| 201 | Created | POST exitoso |
| 204 | No Content | OPTIONS (CORS preflight) |
| 400 | Bad Request | Validación fallida, datos inválidos |
| 401 | Unauthorized | Sin sesión activa |
| 404 | Not Found | Recurso no encontrado |
| 405 | Method Not Allowed | Método HTTP no soportado |
| 500 | Internal Server Error | Error del servidor |

---

## 🧪 Ejemplos de Uso con JavaScript

### Obtener actividades con filtros
```javascript
async function obtenerActividadesPendientes(idMateria) {
  const response = await fetch(
    `/php/api/actividad.php?id_materia=${idMateria}&estado=pendiente`
  );
  const data = await response.json();
  
  if (data.status === 'success') {
    console.log(`Encontradas ${data.total} actividades`);
    return data.data;
  } else {
    throw new Error(data.message);
  }
}
```

### Crear actividad
```javascript
async function crearActividad(actividad) {
  const response = await fetch('/php/api/actividad.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(actividad)
  });
  
  const data = await response.json();
  
  if (response.ok) {
    console.log(`Actividad creada con ID: ${data.id_actividad}`);
    return data.id_actividad;
  } else {
    throw new Error(data.message);
  }
}
```

### Cambiar estado de actividad
```javascript
async function completarActividad(idActividad, datosCompletos) {
  // Primero obtener los datos actuales
  const response = await fetch('/php/api/actividad.php');
  const { data } = await response.json();
  const actividad = data.find(a => a.id_actividad === idActividad);
  
  // Actualizar con nuevo estado
  return await fetch('/php/api/actividad.php', {
    method: 'PUT',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
      id_actividad: idActividad,
      ...actividad,
      estado: 'completado',
      ...datosCompletos
    })
  });
}
```

### Gestionar tipos de actividades
```javascript
// Obtener todos los tipos
const tipos = await fetch('/php/api/tipo_actividad.php')
  .then(r => r.json())
  .then(d => d.data);

// Crear nuevo tipo
await fetch('/php/api/tipo_actividad.php', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({ nombre_tipo: 'Quiz' })
});

// Eliminar tipo (si no está en uso)
await fetch('/php/api/tipo_actividad.php?id=6', {
  method: 'DELETE'
});
```

---

## 📋 Checklist de Testing

```bash
# ✅ Actividades
[ ] GET todas las actividades
[ ] GET con filtro por materia
[ ] GET con filtro por estado
[ ] GET con búsqueda por nombre
[ ] GET con múltiples filtros combinados
[ ] POST crear actividad válida
[ ] POST crear actividad con estado inválido (debe fallar)
[ ] POST crear actividad con puntos inválidos (debe fallar)
[ ] PUT editar actividad
[ ] PUT cambiar estado de completado a pendiente (debe fallar)
[ ] DELETE actividad no calificable
[ ] DELETE actividad calificable (debe fallar)

# ✅ Tipos de Actividad
[ ] GET todos los tipos
[ ] GET tipo específico por ID
[ ] POST crear tipo
[ ] POST crear tipo duplicado (debe fallar)
[ ] PUT editar tipo
[ ] PUT editar con nombre duplicado (debe fallar)
[ ] DELETE tipo sin uso
[ ] DELETE tipo en uso (debe fallar)

# ✅ Autenticación
[ ] Cualquier request sin sesión (debe retornar 401)
```

---

**Última Actualización:** 18 de noviembre de 2025  
**Versión API:** 1.0  
**Contacto:** [Tu Correo/Equipo]
