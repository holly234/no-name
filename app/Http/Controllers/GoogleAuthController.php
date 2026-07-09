<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class GoogleAuthController extends Controller
{
    public function redirect()
    {
        return response('Google OAuth redirect placeholder.', 501);
    }

    public function callback()
    {
        return response('Google OAuth callback placeholder.', 501);
    }
}
