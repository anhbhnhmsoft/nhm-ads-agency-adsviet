<?php

namespace App\Http\Controllers;

use App\Core\Controller;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        return $this->rendering('dashboard/index',[]);
    }

}
