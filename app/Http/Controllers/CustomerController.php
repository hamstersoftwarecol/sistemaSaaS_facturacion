<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\CustomField;
use App\Models\Document;
use App\Models\PriceList;
use App\Services\LedgerService;
use App\Support\Tenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CustomerController extends ModuleController
{
    protected static string $module = 'customers';

    protected static array $extraActions = ['ledger' => 'view'];

    public function index(Request $request): View
    {
        $query = Customer::with('priceList');

        if ($search = $request->string('q')->trim()->value()) {
            $query->where(fn ($q) => $q->where('name', 'like', "%{$search}%")
                ->orWhere('tax_id', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")
                ->orWhere('code', 'like', "%{$search}%"));
        }

        return view('customers.index', [
            'customers' => $query->orderBy('name')->paginate(15)->withQueryString(),
        ]);
    }

    public function create(): View
    {
        return view('customers.form', $this->formData(new Customer(['is_active' => true])));
    }

    public function store(Request $request): RedirectResponse
    {
        $customer = Customer::create($this->validated($request));

        if ($request->input('redirect') === 'back') {
            return back()->with('success', 'Cliente creado.');
        }

        return redirect()->route('customers.show', $customer)->with('success', 'Cliente creado correctamente.');
    }

    public function show(Customer $customer): View
    {
        $documents = Document::where('customer_id', $customer->id)->with('branch')->latest('date')->latest('id')->limit(15)->get();

        return view('customers.show', [
            'customer' => $customer->load('priceList'),
            'documents' => $documents,
            'payments' => $customer->payments()->latest('date')->limit(10)->get(),
            'balance' => $customer->balance(),
            'invoiced' => (float) $customer->invoices()->where('status', '!=', 'cancelled')->sum('total'),
            'paid' => (float) $customer->payments()->sum('amount'),
            'overdue' => Document::ofType('invoice')->where('customer_id', $customer->id)->whereIn('status', ['unpaid', 'partial'])->whereDate('due_date', '<', today())->sum('balance'),
            'customFields' => CustomField::for('customer'),
        ]);
    }

    public function edit(Customer $customer): View
    {
        return view('customers.form', $this->formData($customer));
    }

    public function update(Request $request, Customer $customer): RedirectResponse
    {
        $customer->update($this->validated($request, $customer));

        return redirect()->route('customers.show', $customer)->with('success', 'Cliente actualizado.');
    }

    public function destroy(Customer $customer): RedirectResponse
    {
        if ($customer->documents()->exists() || $customer->payments()->exists()) {
            return back()->with('error', 'No se puede eliminar un cliente con documentos o pagos. Puede desactivarlo.');
        }

        $customer->delete();

        return redirect()->route('customers.index')->with('success', 'Cliente eliminado.');
    }

    public function ledger(Request $request, Customer $customer, LedgerService $ledger): View
    {
        return view('ledger.show', [
            'party' => $customer,
            'partyType' => 'customer',
            'ledger' => $ledger->customer($customer, $request->input('from'), $request->input('to')),
            'company' => Tenant::company(),
        ]);
    }

    protected function validated(Request $request, ?Customer $customer = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'code' => ['nullable', 'string', 'max:30'],
            'tax_id' => ['nullable', 'string', 'max:40'],
            'email' => ['nullable', 'email', 'max:150'],
            'phone' => ['nullable', 'string', 'max:40'],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:100'],
            'country' => ['nullable', 'string', 'max:100'],
            'price_list_id' => ['nullable', Rule::exists('price_lists', 'id')->where('company_id', Tenant::id())],
            'credit_limit' => ['nullable', 'numeric', 'min:0'],
            'opening_balance' => ['nullable', 'numeric'],
            'payment_terms' => ['nullable', 'integer', 'min:0', 'max:365'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['nullable', 'boolean'],
        ] + CustomField::rulesFor('customer'), [], ['name' => 'nombre', 'tax_id' => 'NIT / identificación'] + CustomField::attributesFor('customer'));

        $data['is_active'] = $request->boolean('is_active');
        $data['credit_limit'] = $data['credit_limit'] ?? 0;
        $data['opening_balance'] = $data['opening_balance'] ?? 0;
        $data['payment_terms'] = $data['payment_terms'] ?? 0;
        $data['custom_fields'] = CustomField::valuesFrom('customer', $request);

        return $data;
    }

    protected function formData(Customer $customer): array
    {
        return [
            'customer' => $customer,
            'priceLists' => PriceList::where('is_active', true)->orderBy('name')->get(),
            'customFields' => CustomField::for('customer'),
        ];
    }
}
