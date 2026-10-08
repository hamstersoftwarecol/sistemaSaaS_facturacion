<?php

namespace App\Http\Controllers\Payments;

use App\Http\Controllers\ModuleController;
use App\Models\Account;
use App\Models\Customer;
use App\Models\Document;
use App\Models\Payment;
use App\Models\Supplier;
use App\Services\DocumentService;
use App\Support\Tenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Pagos recibidos de clientes (direction=in) y pagos a proveedores (direction=out).
 */
abstract class PaymentController extends ModuleController
{
    protected string $direction;

    protected static array $extraActions = ['receipt' => 'view'];

    public function __construct(protected DocumentService $documents) {}

    protected function meta(): array
    {
        return $this->direction === 'in'
            ? ['route' => 'payments-received', 'title' => 'Pagos recibidos', 'label' => 'Pago recibido', 'party' => 'customer', 'party_label' => 'Cliente', 'doc_type' => 'invoice', 'doc_label' => 'Factura', 'prefix' => 'RC-']
            : ['route' => 'payments-made', 'title' => 'Pagos a proveedores', 'label' => 'Pago a proveedor', 'party' => 'supplier', 'party_label' => 'Proveedor', 'doc_type' => 'purchase_order', 'doc_label' => 'Orden de compra', 'prefix' => 'PP-'];
    }

    protected function ensureDirection(Payment $payment): void
    {
        abort_unless($payment->direction === $this->direction, 404);
    }

    public function index(Request $request): View
    {
        $meta = $this->meta();
        $query = Payment::where('direction', $this->direction)->forCurrentBranch()
            ->with([$meta['party'], 'document', 'account']);

        if ($search = $request->string('q')->trim()->value()) {
            $query->where(function ($q) use ($search, $meta) {
                $q->where('number', 'like', "%{$search}%")->orWhere('reference', 'like', "%{$search}%")
                    ->orWhereHas($meta['party'], fn ($p) => $p->where('name', 'like', "%{$search}%"));
            });
        }
        if ($method = $request->input('method')) {
            $query->where('method', $method);
        }
        if ($from = $request->input('from')) {
            $query->whereDate('date', '>=', $from);
        }
        if ($to = $request->input('to')) {
            $query->whereDate('date', '<=', $to);
        }

        return view('payments.index', [
            'meta' => $meta,
            'total' => (clone $query)->sum('amount'),
            'payments' => $query->latest('date')->latest('id')->paginate(15)->withQueryString(),
        ]);
    }

    public function create(Request $request): View
    {
        $payment = new Payment(['date' => today(), 'method' => 'cash', 'direction' => $this->direction]);

        if ($documentId = $request->integer('document_id')) {
            $document = Document::where('type', $this->meta()['doc_type'])->find($documentId);
            if ($document) {
                $payment->document_id = $document->id;
                $payment->{$this->meta()['party'].'_id'} = $document->{$this->meta()['party'].'_id'};
                $payment->amount = max(0, (float) $document->balance);
            }
        } elseif ($partyId = $request->integer($this->meta()['party'].'_id')) {
            $payment->{$this->meta()['party'].'_id'} = $partyId;
        }

        return view('payments.form', $this->formData($payment));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        $payment = DB::transaction(function () use ($data) {
            $sequence = (int) Payment::where('direction', $this->direction)->max('sequence') + 1;
            $payment = Payment::create($data + [
                'direction' => $this->direction,
                'sequence' => $sequence,
                'number' => $this->meta()['prefix'].str_pad((string) $sequence, 5, '0', STR_PAD_LEFT),
            ]);
            $this->refreshDocument($payment->document_id);

            return $payment;
        });

        return redirect()->route($this->meta()['route'].'.show', $payment)
            ->with('success', $this->meta()['label'].' '.$payment->number.' registrado.');
    }

    public function show(Payment $payment): View
    {
        $this->ensureDirection($payment);
        $payment->load(['customer', 'supplier', 'document', 'account', 'user', 'branch']);

        return view('payments.show', ['payment' => $payment, 'meta' => $this->meta()]);
    }

    public function receipt(Payment $payment): View
    {
        $this->ensureDirection($payment);
        $payment->load(['customer', 'supplier', 'document', 'account', 'user']);

        return view('payments.receipt', ['payment' => $payment, 'meta' => $this->meta(), 'company' => Tenant::company()]);
    }

    public function edit(Payment $payment): View
    {
        $this->ensureDirection($payment);

        return view('payments.form', $this->formData($payment));
    }

    public function update(Request $request, Payment $payment): RedirectResponse
    {
        $this->ensureDirection($payment);
        $data = $this->validated($request, $payment);

        DB::transaction(function () use ($payment, $data) {
            $previous = $payment->document_id;
            $payment->update($data);
            $this->refreshDocument($previous);
            $this->refreshDocument($payment->document_id);
        });

        return redirect()->route($this->meta()['route'].'.show', $payment)->with('success', 'Pago actualizado.');
    }

    public function destroy(Payment $payment): RedirectResponse
    {
        $this->ensureDirection($payment);

        DB::transaction(function () use ($payment) {
            $documentId = $payment->document_id;
            $payment->delete();
            $this->refreshDocument($documentId);
        });

        return redirect()->route($this->meta()['route'].'.index')->with('success', 'Pago eliminado.');
    }

    protected function refreshDocument(?int $documentId): void
    {
        if ($documentId && $document = Document::find($documentId)) {
            $this->documents->refreshBalance($document);
        }
    }

    protected function validated(Request $request, ?Payment $payment = null): array
    {
        $companyId = Tenant::id();
        $meta = $this->meta();
        $partyKey = $meta['party'].'_id';

        $data = $request->validate([
            $partyKey => ['required', Rule::exists($meta['party'] === 'customer' ? 'customers' : 'suppliers', 'id')->where('company_id', $companyId)],
            'document_id' => ['nullable', Rule::exists('documents', 'id')->where('company_id', $companyId)->where('type', $meta['doc_type'])->where($partyKey, $request->input($partyKey))],
            'account_id' => ['required', Rule::exists('accounts', 'id')->where('company_id', $companyId)],
            'method' => ['required', Rule::in(array_keys(Payment::METHODS))],
            'amount' => ['required', 'numeric', 'gt:0'],
            'date' => ['required', 'date'],
            'reference' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ], [
            'document_id.exists' => 'El documento seleccionado no pertenece a este '.mb_strtolower($meta['party_label']).'.',
        ], [
            $partyKey => mb_strtolower($meta['party_label']),
            'account_id' => 'cuenta',
            'document_id' => mb_strtolower($meta['doc_label']),
        ]);

        return $data;
    }

    protected function formData(Payment $payment): array
    {
        $meta = $this->meta();
        $parties = $meta['party'] === 'customer'
            ? Customer::orderBy('name')->get(['id', 'name'])
            : Supplier::orderBy('name')->get(['id', 'name']);

        $documents = Document::ofType($meta['doc_type'])
            ->where(fn ($q) => $q->where('balance', '>', 0)->orWhere('id', $payment->document_id))
            ->where('status', '!=', 'cancelled')
            ->when($meta['doc_type'] === 'purchase_order', fn ($q) => $q->where(fn ($q) => $q->where('status', 'received')->orWhere('id', $payment->document_id)))
            ->orderBy('date')->get(['id', 'number', 'customer_id', 'supplier_id', 'total', 'balance', 'date', 'due_date']);

        return [
            'payment' => $payment,
            'meta' => $meta,
            'parties' => $parties,
            'documents' => $documents,
            'accounts' => Account::active()->get(),
        ];
    }
}
