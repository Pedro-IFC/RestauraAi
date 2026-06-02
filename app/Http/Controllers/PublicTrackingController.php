<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PublicTrackingController extends Controller
{
    public function index(Request $request, $slug)
    {
        return view('public.storefront', compact('slug'));
    }

    public function show(Request $request, $slug)
    {
    }
    public function updateBudgetStatus(Request $request, $slug)
    {
    }
}
