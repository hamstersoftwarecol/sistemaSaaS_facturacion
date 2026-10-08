<?php

namespace App\Http\Controllers;

use App\Models\DocumentItem;
use App\Models\TaxRate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class TaxRateController extends SettingsModuleController
{
    public function index(): View
    {
        return view('tax-rates.index', ['taxRates' => TaxRate::orderBy('rate')->get()]);
    }

    public function create(): View
    {
        return view('tax-rates.form', ['taxRate' => new TaxRate(['is_active' => true])]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->save(new TaxRate, $request);

        return redirect()->route('tax-rates.index')->with('success', 'Tipo impositivo creado.');
    }

    public function edit(TaxRate $taxRate): View
    {
        return view('tax-rates.form', compact('taxRate'));
    }

    public function update(Request $request, TaxRate $taxRate): RedirectResponse
    {
        $this->save($taxRate, $request);

        return redirect()->route('tax-rates.index')->with('success', 'Tipo impositivo actualizado.');
    }

    public function destroy(TaxRate $taxRate): RedirectResponse
    {
        if (DocumentItem::where('tax_rate_id', $taxRate->id)->exists()) {
            $taxRate->update(['is_active' => false]);

            return back()->with('warning', 'El impuesto se usa en documentos: se ha desactivado.');
        }

        $taxRate->delete();

        return back()->with('success', 'Tipo impositivo eliminado.');
    }

    protected function save(TaxRate $taxRate, Request $request): void
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:60'],
            'rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'is_default' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ], [], ['name' => 'nombre', 'rate' => 'tasa']);

        $data['is_default'] = $request->boolean('is_default');
        $data['is_active'] = $request->boolean('is_active') || $data['is_default'];

        DB::transaction(function () use ($taxRate, $data) {
            if ($data['is_default']) {
                TaxRate::where('id', '!=', $taxRate->id ?? 0)->update(['is_default' => false]);
            }
            $taxRate->fill($data)->save();
        });
    }
}
