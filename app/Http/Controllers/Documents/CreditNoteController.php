<?php

namespace App\Http\Controllers\Documents;

class CreditNoteController extends DocumentController
{
    protected string $type = 'credit_note';

    protected static string $module = 'credit_notes';
}
