<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

class DashboardController extends Controller
{
    public function index()
    {
        return view('dashboard.index');
    }

    public function truncate()
    {
        Artisan::call('migrate:fresh --seed');
        Artisan::call('optimize:clear');

        return 'Truncated and seeded the database';
    }
}
