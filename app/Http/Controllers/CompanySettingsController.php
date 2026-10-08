<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Customer;
use App\Models\Document;
use App\Models\DocumentItem;
use App\Support\Codes;
use App\Support\Tenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CompanySettingsController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('can:settings.view', only: ['edit', 'invoice', 'preview']),
            new Middleware('can:settings.edit', only: ['update', 'updateInvoice']),
        ];
    }

    public function edit(): View
    {
        return view('settings.company', ['company' => Tenant::company()]);
    }

    public function update(Request $request): RedirectResponse
    {
        $company = Tenant::company();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'legal_name' => ['nullable', 'string', 'max:200'],
            'tax_id' => ['nullable', 'string', 'max:40'],
            'email' => ['nullable', 'email', 'max:150'],
            'phone' => ['nullable', 'string', 'max:40'],
            'website' => ['nullable', 'string', 'max:150'],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:100'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'country' => ['nullable', 'string', 'max:100'],
            'currency' => ['required', 'string', 'size:3'],
            'currency_symbol' => ['required', 'string', 'max:8'],
            'logo' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:1024'],
            'remove_logo' => ['nullable', 'boolean'],
        ], [], ['name' => 'nombre comercial', 'currency' => 'moneda', 'currency_symbol' => 'símbolo de moneda']);

        if ($request->boolean('remove_logo') && $company->logo_path) {
            Storage::disk('public')->delete($company->logo_path);
            $data['logo_path'] = null;
        }

        if ($request->hasFile('logo')) {
            if ($company->logo_path) {
                Storage::disk('public')->delete($company->logo_path);
            }
            $data['logo_path'] = $request->file('logo')->store("companies/{$company->id}", 'public');
        }

        unset($data['logo'], $data['remove_logo']);
        $data['currency'] = strtoupper($data['currency']);
        $company->update($data);

        return back()->with('success', 'Perfil de la empresa actualizado.');
    }

    public function invoice(): View
    {
        return view('settings.invoice', ['company' => Tenant::company(), 'styles' => Company::INVOICE_STYLES]);
    }

    public function updateInvoice(Request $request): RedirectResponse
    {
        $company = Tenant::company();
        $prefixRules = collect(Document::TYPES)->mapWithKeys(fn ($c, $type) => ["prefix_{$type}" => ['nullable', 'string', 'max:10']])->all();

        $data = $request->validate([
            'invoice_style' => ['required', Rule::in(array_keys(Company::INVOICE_STYLES))],
            'primary_color' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'invoice_terms' => ['nullable', 'string', 'max:3000'],
            'invoice_notes' => ['nullable', 'string', 'max:3000'],
            'invoice_footer' => ['nullable', 'string', 'max:255'],
        ] + $prefixRules, [], ['primary_color' => 'color principal']);

        $settings = $company->settings ?? [];
        foreach (array_keys(Document::TYPES) as $type) {
            $settings["prefix_{$type}"] = $data["prefix_{$type}"] ?: Document::TYPES[$type]['prefix'];
            unset($data["prefix_{$type}"]);
        }

        $company->update($data + ['settings' => $settings]);

        return back()->with('success', 'Configuración de documentos guardada.');
    }

    /**
     * Vista previa de un estilo de factura con datos de ejemplo.
     */
    public function preview(string $style): View
    {
        abort_unless(array_key_exists($style, Company::INVOICE_STYLES), 404);
        $company = Tenant::company();

        $document = Document::ofType('invoice')->with(['items', 'customer'])->latest('id')->first();

        if (! $document) {
            $document = new Document([
                'type' => 'invoice', 'number' => $company->prefix('invoice').'00000', 'date' => today(), 'due_date' => today()->addDays(30),
                'status' => 'unpaid', 'subtotal' => 1250, 'tax_total' => 237.5, 'total' => 1487.5, 'balance' => 1487.5, 'amount_paid' => 0,
                'discount_total' => 0, 'adjustments' => [], 'notes' => $company->invoice_notes, 'terms' => $company->invoice_terms,
                'verification_code' => 'ejemplo',
            ]);
            $document->setRelation('customer', new Customer(['name' => 'Cliente de ejemplo S.A.S.', 'tax_id' => '900.123.456-7', 'address' => 'Calle 10 # 20-30', 'city' => 'Bogotá', 'email' => 'cliente@ejemplo.com']));
            $document->setRelation('items', collect([
                new DocumentItem(['description' => 'Servicio de consultoría', 'quantity' => 10, 'unit' => 'hora', 'unit_price' => 80, 'discount_percent' => 0, 'tax_rate' => 19, 'subtotal' => 800, 'tax_amount' => 152, 'total' => 952]),
                new DocumentItem(['description' => 'Licencia de software anual', 'quantity' => 1, 'unit' => 'und', 'unit_price' => 450, 'discount_percent' => 0, 'tax_rate' => 19, 'subtotal' => 450, 'tax_amount' => 85.5, 'total' => 535.5]),
            ]));
        }

        return view('documents.print', [
            'document' => $document,
            'company' => $company,
            'style' => $style,
            'color' => $company->primary_color ?: '#4f46e5',
            'logo' => $company->logoDataUri(),
            'qr' => Codes::qrDataUri(route('verify.document', $document->verification_code ?? 'ejemplo')),
            'customFields' => collect(),
            'partyFields' => collect(),
            'preview' => true,
        ]);
    }
}
