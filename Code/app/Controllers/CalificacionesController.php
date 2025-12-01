<?php

namespace App\Controllers;

use App\Core\Controller;

/**
 * Controlador de calificaciones del usuario.
 *
 * Gestionar:
 *  - Mostrar el resumen de calificaciones del usuario.
 *  - Mostrar el detalle de calificaciones por materia.
 */
class CalificacionesController extends Controller {
    /**
     * Mostrar la vista principal "Mis calificaciones".
     *
     * @return void
     */
    public function index(): void {
        $this->view('mis-calificaciones');
    }

    /**
     * Mostrar la vista de detalle de calificaciones por materia.
     *
     * @return void
     */
    public function detalle(): void {
        $this->view('mis-calificaciones-detalle');
    }
}
