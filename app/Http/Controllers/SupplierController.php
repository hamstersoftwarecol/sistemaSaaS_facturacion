<?php

namespace App\Http\Controllers;

use App\Models\CustomField;
use App\Models\Document;
use App\Models\Supplier;
use App\Services\LedgerService;
use App\Support\Tenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SupplierController extends ModuleController
{
    protected static string $module = 'suppliers';

    protected static array $extraActions = ['ledger' => 'view'];

    public function index(Request $request): View
    {
        $query = Supplier::query();

        if ($search = $request->string('q')->trim()->value()) {
            $query->where(fn ($q) => $q->where('name', 'like', "%{$search}%")
                ->orWhere('tax_id', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")
                ->orWhere('contact_name', 'like', "%{$search}%"));
        }

        return view('suppliers.index', ['suppliers' => $query->orderBy('name')->paginate(15)->withQueryString()]);
    }

    public function create(): View
    {
        return view('suppliers.form', ['supplier' => new Supplier(['is_active' => true]), 'customFields' => CustomField::for('supplier')]);
    }

    public function store(Request $request): RedirectResponse
    {
        $supplier = Supplier::create($this->validated($request));

        return redirect()->route('suppliers.show', $supplier)->with('success', 'Proveedor creado correctamente.');
    }

    public function show(Supplier $supplier): View
    {
        return view('suppliers.show', [
            'supplier' => $supplier,
            'documents' => Document::where('supplier_id', $supplier->id)->latest('date')->latest('id')->limit(15)->get(),
            'payments' => $supplier->payments()->latest('date')->limit(10)->get(),
            'balance' => $supplier->balance(),
            'purchased' => (float) $supplier->documents()->where('type', 'purchase_order')->where('status', 'received')->sum('total'),
            'paid' => (float) $supplier->payments()->sum('amount'),
            'customFields' => CustomField::for('supplier'),
        ]);
    }

    public function edit(Supplier $supplier): View
    {
        return view('suppliers.form', ['supplier' => $supplier, 'customFields' => CustomField::for('supplier')]);
    }

    public function update(Request $request, Supplier $supplier): RedirectResponse
    {
        $supplier->update($this->validated($request));

        return redirect()->route('suppliers.show', $supplier)->with('success', 'Proveedor actualizado.');
    }

    public function destroy(Supplier $supplier): RedirectResponse
    {
        if ($supplier->documents()->exists() || $supplier->payments()->exists()) {
            return back()->with('error', 'No se puede eliminar un proveedor con documentos o pagos. Puede desactivarlo.');
        }

        $supplier->delete();

        return redirect()->route('suppliers.index')->with('success', 'Proveedor eliminado.');
    }

    public function ledger(Request $request, Supplier $supplier, LedgerService $ledger): View
    {
        return view('ledger.show', [
            'party' => $supplier,
            'partyType' => 'supplier',
            'ledger' => $ledger->supplier($supplier, $request->input('from'), $request->input('to')),
            'company' => Tenant::company(),
        ]);
    }

    protected function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'code' => ['nullable', 'string', 'max:30'],
            'tax_id' => ['nullable', 'string', 'max:40'],
            'email' => ['nullable', 'email', 'max:150'],
            'phone' => ['nullable', 'string', 'max:40'],
            'contact_name' => ['nullable', 'string', 'max:150'],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:100'],
            'country' => ['nullable', 'string', 'max:100'],
            'opening_balance' => ['nullable', 'numeric'],
            'payment_terms' => ['nullable', 'integer', 'min:0', 'max:365'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['nullable', 'boolean'],
        ] + CustomField::rulesFor('supplier'), [], ['name' => 'nombre'] + CustomField::attributesFor('supplier'));

        $data['is_active'] = $request->boolean('is_active');
        $data['opening_balance'] = $data['opening_balance'] ?? 0;
        $data['payment_terms'] = $data['payment_terms'] ?? 0;
        $data['custom_fields'] = CustomField::valuesFrom('supplier', $request);

        return $data;
    }
}
