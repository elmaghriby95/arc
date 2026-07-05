<?php

namespace App\Http\Controllers;

use App\Models\Language;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LocaleController extends Controller
{
    public function switch(Request $request, Language $language): RedirectResponse
    {
        if (! $language->is_active) {
            abort(404);
        }

        $request->session()->put('locale', $language->code);

        if ($user = $request->user()) {
            $user->update(['language_id' => $language->id]);
        }

        return back();
    }
}
