<?php

namespace App\Controllers;

use App\Core\Controller;

/**
 * Controlador del panel principal (dashboard).
 *
 * Gestionar:
 *  - Mostrar el panel de inicio del usuario autenticado.
 */
class DashboardController extends Controller{
    /**
     * Mostrar la vista del dashboard.
     *
     * @return void
     */
    public function index(): void {
        $this->view('dashboard');
    }
}
