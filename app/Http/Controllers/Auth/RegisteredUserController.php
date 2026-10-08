<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Services\CompanyProvisioner;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    public const CURRENCIES = [
        'USD' => '$', 'COP' => '$', 'MXN' => '$', 'EUR' => '€', 'PEN' => 'S/', 'CLP' => '$', 'ARS' => '$',
    ];

    /**
     * Formulario de alta de una nueva empresa (tenant).
     */
    public function create(): View
    {
        return view('auth.register', ['plans' => Plan::active()->get()]);
    }

    /**
     * Registra la empresa, su propietario y la configuración inicial.
     */
    public function store(Request $request, CompanyProvisioner $provisioner): RedirectResponse
    {
        $data = $request->validate([
            'company_name' => ['required', 'string', 'max:150'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'plan' => ['nullable', Rule::exists('plans', 'slug')->where('is_active', true)],
            'currency' => ['nullable', Rule::in(array_keys(self::CURRENCIES))],
        ], [], ['company_name' => 'nombre de la empresa', 'name' => 'nombre', 'password' => 'contraseña']);

        $currency = $data['currency'] ?? 'USD';
        $plan = isset($data['plan']) ? Plan::where('slug', $data['plan'])->first() : null;

        $user = $provisioner->provision($data + [
            'currency' => $currency,
            'currency_symbol' => self::CURRENCIES[$currency],
        ], $plan);

        event(new Registered($user));

        Auth::login($user);

        return redirect()->route('dashboard')->with('success', '¡Bienvenido! Su empresa está lista. Complete el perfil de la empresa y cree su primera factura.');
    }
}
