<?php

namespace App\Http\Controllers\Documents;

class DebitNoteController extends DocumentController
{
    protected string $type = 'debit_note';

    protected static string $module = 'debit_notes';
}
