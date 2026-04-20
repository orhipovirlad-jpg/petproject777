<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class CabinetController extends Controller
{
    public function show(): RedirectResponse
    {
        return redirect()->route('cabinet.login-page');
    }

    public function showLogin(Request $request): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('workbook.products');
        }

        return view('cabinet.login');
    }

    public function showRegister(Request $request): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('workbook.products');
        }

        return view('cabinet.register');
    }

    public function register(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:190'],
            'email' => ['required', 'email', 'max:190', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = User::query()->create([
            'name' => trim((string) $validated['name']),
            'email' => strtolower(trim((string) $validated['email'])),
            'password' => Hash::make((string) $validated['password']),
        ]);

        $request->session()->regenerate();
        Auth::login($user);

        return redirect()
            ->route('workbook.products')
            ->with('success', 'Регистрация завершена. Добро пожаловать в кабинет.');
    }

    public function login(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:190'],
            'password' => ['required', 'string'],
        ]);

        $credentials = [
            'email' => strtolower(trim((string) $validated['email'])),
            'password' => (string) $validated['password'],
        ];

        if (!Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors([
                    'auth' => 'Неверный email или пароль.',
                ]);
        }

        $request->session()->regenerate();

        return redirect()
            ->route('workbook.products')
            ->with('success', 'Вы успешно вошли в кабинет.');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()
            ->route('cabinet.login-page')
            ->with('success', 'Вы вышли из кабинета.');
    }
}
