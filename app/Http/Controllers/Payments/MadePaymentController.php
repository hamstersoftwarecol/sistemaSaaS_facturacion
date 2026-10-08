<?php

namespace App\Http\Controllers\Payments;

class MadePaymentController extends PaymentController
{
    protected string $direction = 'out';

    protected static string $module = 'payments_made';
}
