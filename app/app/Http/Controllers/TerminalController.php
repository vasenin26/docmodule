<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Illuminate\Http\Request;

class TerminalController extends Controller
{
    public function index(Request $request)
    {
        return Inertia::render('terminals/Index');
    }
}
