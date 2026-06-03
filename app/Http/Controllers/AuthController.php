<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    /**
     * Exibe a tela de login
     */
    public function showLoginForm()
    {
        return view('auth.login');
    }

    /**
     * Processa a tentativa de login
     */
    public function login(Request $request)
    {
        // 1. Valida os dados enviados
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        // 2. Tenta autenticar o usuário
        if (Auth::attempt($credentials, $request->boolean('remember'))) {

            // Proteção contra Session Fixation
            $request->session()->regenerate();

            if (Auth::user()->role === 'superadmin') {
                return redirect()->intended(route('planos.index'));
            }

            // Redireciona para o painel (ou para a página que ele tentou acessar antes)
            return redirect()->intended(route('tenant.dashboard'));
        }

        // 3. Se falhar, volta com erro
        return back()->withErrors([
            'email' => 'As credenciais informadas estão incorretas.',
        ])->onlyInput('email');
    }

    /**
     * Realiza o logout do sistema
     */
    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
