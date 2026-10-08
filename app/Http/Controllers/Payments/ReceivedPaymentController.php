<?php

namespace App\Http\Controllers\Payments;

class ReceivedPaymentController extends PaymentController
{
    protected string $direction = 'in';

    protected static string $module = 'payments_received';
}
