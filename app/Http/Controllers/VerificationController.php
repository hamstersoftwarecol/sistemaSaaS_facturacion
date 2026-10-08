<?php

namespace App\Http\Controllers;

use App\Models\Document;
use Illuminate\View\View;

/**
 * Verificación pública de autenticidad de documentos mediante el código QR.
 */
class VerificationController extends Controller
{
    public function show(string $code): View
    {
        $document = Document::withoutGlobalScopes()->with(['company', 'customer' => fn ($q) => $q->withoutGlobalScopes(), 'supplier' => fn ($q) => $q->withoutGlobalScopes()])
            ->where('verification_code', $code)->first();

        return view('verify.show', ['document' => $document, 'code' => $code]);
    }
}
