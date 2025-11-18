<?php

namespace App\Http\Controllers;

use App\Core\Controller;

class DashboardController extends Controller
{
    public function index()
    {
        return $this->rendering('dashboard/index', []);
    }
}
