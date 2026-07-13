<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function login()
    {
        return view("pages.auth.signin");
    }

    public function handleLogin(LoginRequest $request)
{
    $credentials = $request->only('email', 'password');

    $remember = $request->boolean('remember');

    if (!Auth::attempt($credentials, $remember)) {
        return back()
            ->withErrors([
                'email' => 'These credentials do not match our records.'
            ])
            ->withInput();
    }

    $request->session()->regenerate();

    return redirect()->route('dashboard');
}

    public function register(RegisterRequest $request)
    {
        // register logic (keyin qilamiz)
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}