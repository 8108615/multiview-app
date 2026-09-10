<?php

namespace App\Http\Controllers;

use App\Models\Canal;
use Inertia\Inertia;

class MultiviewController extends Controller
{
    public function index()
    {
        $canales = Canal::where('estado', 'Activo')->get();

        return Inertia::render('Admin/Multiview/Index', [
            'canales' => $canales
        ]);
    }
}
