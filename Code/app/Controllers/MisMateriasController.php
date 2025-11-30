<?php

namespace App\Controllers;

use App\Core\Controller;

class MisMateriasController extends Controller
{
    public function index()
    {
        $this->view('mis-materias');
    }
}
