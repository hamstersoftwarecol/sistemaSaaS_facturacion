<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Plan;
use Illuminate\View\View;

class LandingController extends Controller
{
    public function home(): View
    {
        return view('landing.home', ['plans' => Plan::active()->get(), 'styles' => Company::INVOICE_STYLES]);
    }

    public function pricing(): View
    {
        return view('landing.pricing', ['plans' => Plan::active()->get()]);
    }
}
