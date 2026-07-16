<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class LegalController extends Controller
{
    public function privacy(): View { return view('legal.show', ['page' => 'privacy']); }
    public function terms(): View { return view('legal.show', ['page' => 'terms']); }
    public function dataDeletion(): View { return view('legal.show', ['page' => 'data-deletion']); }
}
