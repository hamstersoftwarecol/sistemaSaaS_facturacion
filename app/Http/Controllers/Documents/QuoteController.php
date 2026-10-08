<?php

namespace App\Http\Controllers\Documents;

class QuoteController extends DocumentController
{
    protected string $type = 'quote';

    protected static string $module = 'quotes';
}
