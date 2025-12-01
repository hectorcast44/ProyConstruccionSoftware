<?php

namespace App\Controllers;

use App\Core\Controller;

/**
 * Controlador de materias del usuario.
 *
 * Gestionar:
 *  - Mostrar la lista de materias inscritas por el usuario.
 */
class MisMateriasController extends Controller {
    /**
     * Mostrar la vista "Mis materias".
     *
     * @return void
     */
    public function index(): void {
        $this->view('mis-materias');
    }
}
