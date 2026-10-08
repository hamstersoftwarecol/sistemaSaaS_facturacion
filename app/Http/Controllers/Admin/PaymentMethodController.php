<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PaymentMethod;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PaymentMethodController extends Controller
{
    public function index(): View
    {
        return view('admin.payment-methods.index', ['methods' => PaymentMethod::orderBy('sort_order')->get()]);
    }

    public function create(): View
    {
        return view('admin.payment-methods.form', ['method' => new PaymentMethod(['type' => 'bank_transfer', 'is_active' => true])]);
    }

    public function store(Request $request): RedirectResponse
    {
        PaymentMethod::create($this->validated($request));

        return redirect()->route('admin.payment-methods.index')->with('success', 'Método de pago creado.');
    }

    public function edit(PaymentMethod $paymentMethod): View
    {
        return view('admin.payment-methods.form', ['method' => $paymentMethod]);
    }

    public function update(Request $request, PaymentMethod $paymentMethod): RedirectResponse
    {
        $paymentMethod->update($this->validated($request));

        return redirect()->route('admin.payment-methods.index')->with('success', 'Método de pago actualizado.');
    }

    public function destroy(PaymentMethod $paymentMethod): RedirectResponse
    {
        $paymentMethod->delete();

        return back()->with('success', 'Método de pago eliminado.');
    }

    protected function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'type' => ['required', Rule::in(array_keys(PaymentMethod::TYPES))],
            'instructions' => ['nullable', 'string', 'max:3000'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ], [], ['name' => 'nombre', 'instructions' => 'instrucciones']);

        $data['sort_order'] = $data['sort_order'] ?? 0;
        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }
}
