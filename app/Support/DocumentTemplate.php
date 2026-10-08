<?php

namespace App\Support;

use App\Models\Company;
use App\Models\Document;
use Illuminate\Support\Collection;

/**
 * Variables comunes a las cinco plantillas de impresión de documentos.
 */
class DocumentTemplate
{
    public static function vars(Document $document, Company $company, Collection $customFields, Collection $partyFields): array
    {
        $party = $document->party();
        $isSales = $document->cfg('party') === 'customer';

        $partyTitle = match (true) {
            $document->type === 'delivery_note' => 'Entregar a',
            $document->type === 'quote' => 'Presupuesto para',
            $isSales => 'Facturar a',
            default => 'Proveedor',
        };

        return [
            'party' => $party,
            'isSales' => $isSales,
            'partyTitle' => $partyTitle,
            'docTitle' => $document->cfg('title'),
            'm' => fn ($value) => money($value, $company->currency_symbol),
            'dueLabel' => $document->type === 'quote' ? 'Válido hasta' : ($document->type === 'purchase_order' ? 'Fecha de pago' : 'Vencimiento'),
            'showPayments' => in_array($document->type, ['invoice', 'purchase_order']) && (float) $document->amount_paid > 0,
            'partyExtra' => $partyFields->filter(fn ($f) => filled($party?->custom_fields[$f->name] ?? null)),
            'docExtra' => $customFields->filter(fn ($f) => filled($document->custom_fields[$f->name] ?? null)),
            'companyLines' => collect([
                $company->legal_name && $company->legal_name !== $company->name ? $company->legal_name : null,
                $company->tax_id ? 'NIT/RUC: '.$company->tax_id : null,
                $company->fullAddress() ?: null,
                collect([$company->phone, $company->email])->filter()->implode(' · ') ?: null,
                $company->website,
            ])->filter()->values(),
            'partyLines' => $party ? collect([
                $party->tax_id ? 'NIT/ID: '.$party->tax_id : null,
                collect([$party->address, $party->city, $party->country])->filter()->implode(', ') ?: null,
                collect([$party->phone, $party->email])->filter()->implode(' · ') ?: null,
            ])->filter()->values() : collect(),
        ];
    }
}
