<?php

namespace App\Controllers;

use App\Core\Controller;

class CalificacionesController extends Controller
{
    public function index()
    {
        $this->view('mis-calificaciones');
    }

    public function detalle()
    {
        $this->view('mis-calificaciones-detalle');
    }
}
