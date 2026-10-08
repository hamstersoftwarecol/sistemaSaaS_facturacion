<?php

namespace App\Http\Controllers\Documents;

class PurchaseOrderController extends DocumentController
{
    protected string $type = 'purchase_order';

    protected static string $module = 'purchase_orders';
}
