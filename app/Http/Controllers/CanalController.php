<?php

namespace App\Http\Controllers;

use App\Models\Canal;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CanalController extends Controller
{
    public function index(Request $request)
    {
        // Recogemos el valor de búsqueda y de registros por página
        $search = $request->input('search');
        $perPage = $request->input('per_page', 10);

        // Consultamos filtrando si existe un término de búsqueda
        $canales = Canal::when($search, function ($query, $search) {
                $query->where('nombre', 'like', "%{$search}%");
            })
            ->oldest()
            ->paginate($perPage)
            ->withQueryString();

        return Inertia::render('Admin/Canales/Index', [
            'canales' => $canales,
            'filters' => $request->only(['search', 'per_page']),
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

    public function update(Request $request, Canal $canal)
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
            'enlace_streaming' => 'required|string',
            'logo' => 'nullable|url',
            'estado' => 'nullable|in:Activo,Inactivo',
        ]);

        $canal->update([
            'nombre' => $request->nombre,
            'enlace_streaming' => $request->enlace_streaming,
            'logo' => $request->logo,
            'estado' => $request->estado ?? 'Activo',
        ]);

        return redirect()->back()->with('success', 'Canal actualizado exitosamente.');
    }

    // Método para eliminar un canal
    public function destroy(Canal $canal)
    {
        $canal->delete();

        return redirect()->back()->with('success', 'Canal eliminado exitosamente.');
    }
}
