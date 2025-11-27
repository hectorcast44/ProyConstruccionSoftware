<?php

namespace App\Controllers;

use App\Core\Controller;

class DashboardController extends Controller
{
    public function index()
    {
        // In a real MVC, we would pass data here.
        // For now, we serve the static HTML structure which fetches data via AJAX.
        // We will refactor the HTML to be a PHP view.
        $this->view('dashboard');
    }
}
