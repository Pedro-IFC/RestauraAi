<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PublicServiceOrderController extends Controller
{
    public function create(Request $request, $slug)
    {
        return view('public.service_order.create');
    }

    public function store(Request $request, $slug)
    {
    }
}
