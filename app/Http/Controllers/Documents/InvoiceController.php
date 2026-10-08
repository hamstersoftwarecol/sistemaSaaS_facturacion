<?php

namespace App\Http\Controllers\Documents;

class InvoiceController extends DocumentController
{
    protected string $type = 'invoice';

    protected static string $module = 'invoices';
}
