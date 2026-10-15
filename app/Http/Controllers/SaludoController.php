<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class SaludoController extends Controller
{
    /**
     * Muestra el formulario de saludo.
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        return view('saludo.create');
    }

    /**
     * Procesa el formulario y muestra el saludo.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\View\View
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
        ]);

        $nombre = $request->input('nombre');

        return view('saludo.index', compact('nombre'));
    }
}