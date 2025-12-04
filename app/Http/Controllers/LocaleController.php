<?php

namespace App\Http\Controllers;

use App\Core\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LocaleController extends Controller
{
    public function update(Request $request): RedirectResponse
    {
        $supported = ['vi', 'en'];
        $locale = $request->input('locale');

        if (!in_array($locale, $supported, true)) {
            $locale = config('app.locale');
        }

        session(['locale' => $locale]);

        return back();
    }
}

