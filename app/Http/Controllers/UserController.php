<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserController extends Controller
{
    public function showProfile(Request $request): View
    {
        $user = $request->user();

        abort_unless($user instanceof User, 403);

        return view('user.profile', compact('user'));
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $user = $request->user();

        abort_unless($user instanceof User, 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'company_name' => ['nullable', 'string', 'max:255'],
        ]);

        $user->update($validated);

        return redirect()
            ->route('profile.show')
            ->with('status', 'Los datos de tu perfil se actualizaron correctamente.');
    }
}
