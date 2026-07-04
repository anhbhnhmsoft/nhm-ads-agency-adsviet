<?php

namespace App\Http\Controllers;

use App\Core\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LocaleController extends Controller
{
    public function update(Request $request): RedirectResponse
    {
        $supported = ['vi', 'en', 'zh'];
        $locale = $request->input('locale');

        if (!in_array($locale, $supported, true)) {
            $locale = config('app.locale');
        }

        session(['locale' => $locale]);

        $user = Auth::user();
        if ($user && $user->language !== $locale) {
            $user->language = $locale;
            $user->save();
        }

        return back();
    }
}

