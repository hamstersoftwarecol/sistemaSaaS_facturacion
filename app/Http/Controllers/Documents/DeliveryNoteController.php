<?php

namespace App\Http\Controllers\Documents;

class DeliveryNoteController extends DocumentController
{
    protected string $type = 'delivery_note';

    protected static string $module = 'delivery_notes';
}
