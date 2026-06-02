<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PublicStorefrontController extends Controller
{
    public function index(Request $request, $slug)
    {
        return view('public.storefront', compact('slug'));
    }
}
