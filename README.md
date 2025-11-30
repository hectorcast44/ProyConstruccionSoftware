# Agenda Escolar

**Agenda Escolar** es una aplicación web diseñada para ayudar a los estudiantes a gestionar sus materias, actividades y calificaciones de manera eficiente. El proyecto implementa una arquitectura **Modelo-Vista-Controlador (MVC)** personalizada en PHP, separando claramente la lógica de negocio, la interfaz de usuario y el control de flujo.

## 🚀 Características Principales

*   **Gestión de Materias**: Alta, baja y visualización de asignaturas.
*   **Control de Actividades**: Registro de tareas y exámenes por materia.
*   **Seguimiento de Calificaciones**: Cálculo automático de promedios y progreso.
*   **Arquitectura MVC**: Código organizado, mantenible y escalable.
*   **API RESTful**: Comunicación eficiente entre el frontend y el backend.

## 🏗️ Arquitectura del Proyecto

El proyecto sigue una estructura estricta para garantizar la seguridad y la mantenibilidad:

*   **`app/`**: Contiene toda la lógica privada del servidor (Controladores, Modelos, Vistas, Configuración). No es accesible directamente desde el navegador.
*   **`public/`**: Es el único punto de acceso público. Contiene el `index.php` (Front Controller) y los recursos estáticos (JS, CSS, Imágenes).

### Flujo de la Aplicación
1.  Todas las peticiones son interceptadas por `public/index.php`.
2.  El **Router** (`app/Core/Router.php`) dirige la petición al controlador correspondiente.
3.  El **Controlador** interactúa con los **Modelos** para obtener datos.
4.  El **Controlador** carga una **Vista** (HTML) o devuelve una respuesta JSON (API).

## 🛠️ Requisitos e Instalación

### Requisitos Previos
*   **XAMPP** (o cualquier servidor con PHP 8.0+ y MySQL/MariaDB).
*   **Composer** (Gestor de dependencias de PHP).

### Pasos de Instalación

1.  **Clonar el repositorio** en tu carpeta `htdocs`:
    ```bash
    cd c:\xampp\htdocs
    git clone <URL_DEL_REPOSITORIO> ProyConstruccionSoftware
    ```

2.  **Instalar dependencias**:
    ```bash
    cd ProyConstruccionSoftware/Code
    composer install
    ```

3.  **Configurar la Base de Datos**:
    *   Abre phpMyAdmin (`http://localhost/phpmyadmin`).
    *   Crea una nueva base de datos llamada `agenda_escolar`.
    *   Importa el archivo `Code/agenda_escolar.sql`.

4.  **Ejecutar la aplicación**:
    *   Abre tu navegador y visita: `http://localhost/ProyConstruccionSoftware/Code/public/`

## 👥 Autores


*   **KarenCampos842** - *Desarrolladora Frontend*
*   **HectorCast44** - *Desarrollador Backend*
*   **JosephGarcia24** - *Desarrollador Backend*
*   **RubenPerez55** - *Desarrollador Frontend*


---
*Proyecto desarrollado para la materia de Construcción de Software.*
