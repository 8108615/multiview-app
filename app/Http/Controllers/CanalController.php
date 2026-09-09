<?php

namespace App\Http\Controllers;

use App\Models\Canal;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CanalController extends Controller
{
    public function index()
    {
        $canales = Canal::latest()->get();

        return Inertia::render('Admin/Canales/Index', [
            'canales' => $canales
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
            'enlace_streaming' => 'required|string',
            'logo' => 'nullable|url',
            'estado' => 'nullable|in:Activo,Inactivo',
        ]);

        Canal::create([
            'nombre' => $request->nombre,
            'enlace_streaming' => $request->enlace_streaming,
            'logo' => $request->logo,
            'estado' => $request->estado ?? 'Activo',
        ]);

        return redirect()->back()->with('success', 'Canal creado exitosamente.');
    }

    // Método para eliminar un canal
    public function destroy(Canal $canal)
    {
        $canal->delete();

        return redirect()->back()->with('success', 'Canal eliminado exitosamente.');
    }
}
