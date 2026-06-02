<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class KanbanController extends Controller
{
    public function index(Request $request, $slug)
    {
        // Lógica para exibir o quadro Kanban
    }

    public function updateCardPosition(Request $request, $slug)
    {
        // Lógica para atualizar a posição dos cartões no Kanban
    }
}
