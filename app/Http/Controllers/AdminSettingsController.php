<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminSettingsController extends Controller
{
    public function edit(): View
    {
        return view('admin.settings', ['settings' => Setting::current(), 'levels' => Setting::PHYSICS_LEVELS, 'maxLives' => Setting::MAX_LIVES]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'closed_title' => ['required', 'string', 'max:60'],
            'closed_message' => ['nullable', 'string', 'max:300'],
            'physics_difficulty' => ['required', 'integer', 'between:1,'.count(Setting::PHYSICS_LEVELS)],
            'lives' => ['required', 'integer', 'between:1,'.Setting::MAX_LIVES],
        ]);

        Setting::saveMany([
            'playing_disabled' => $request->boolean('playing_disabled'),
            'closed_title' => $data['closed_title'],
            'closed_message' => $data['closed_message'] ?? '',
            'physics_enabled' => $request->boolean('physics_enabled'),
            'physics_difficulty' => (int) $data['physics_difficulty'],
            'lives' => (int) $data['lives'],
        ]);

        return redirect()->route('admin.settings')->with('status', 'Settings saved.');
    }
}
