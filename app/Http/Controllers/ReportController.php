<?php

namespace App\Http\Controllers;

use App\Services\ReportService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller implements HasMiddleware
{
    public const REPORTS = [
        'ventas' => ['method' => 'sales', 'title' => 'Ventas', 'description' => 'Facturación por periodo, cliente y producto', 'dated' => true, 'icon' => 'chart'],
        'compras' => ['method' => 'purchases', 'title' => 'Compras', 'description' => 'Órdenes de compra recibidas por proveedor', 'dated' => true, 'icon' => 'cart'],
        'impuestos' => ['method' => 'tax', 'title' => 'Impuestos', 'description' => 'Impuesto repercutido vs. soportado por tipo', 'dated' => true, 'icon' => 'percent'],
        'perdidas-y-ganancias' => ['method' => 'profitLoss', 'title' => 'Pérdidas y ganancias', 'description' => 'Ingresos, costo de ventas, gastos y utilidad', 'dated' => true, 'icon' => 'scale'],
        'cuentas-por-cobrar' => ['method' => 'receivables', 'title' => 'Cuentas por cobrar', 'description' => 'Antigüedad de saldos de clientes', 'dated' => false, 'icon' => 'inbox'],
        'cuentas-por-pagar' => ['method' => 'payables', 'title' => 'Cuentas por pagar', 'description' => 'Antigüedad de saldos con proveedores', 'dated' => false, 'icon' => 'outbox'],
        'inventario' => ['method' => 'inventory', 'title' => 'Inventario valorizado', 'description' => 'Existencias, valor al costo y alertas de stock', 'dated' => false, 'icon' => 'box'],
        'flujo-de-caja' => ['method' => 'cashflow', 'title' => 'Flujo de caja', 'description' => 'Entradas y salidas por cuenta de efectivo y bancos', 'dated' => true, 'icon' => 'cash'],
        'gastos' => ['method' => 'expenses', 'title' => 'Gastos', 'description' => 'Gastos por categoría y mes', 'dated' => true, 'icon' => 'receipt'],
        'clientes' => ['method' => 'customers', 'title' => 'Ventas por cliente', 'description' => 'Facturación y saldo de cada cliente', 'dated' => true, 'icon' => 'users'],
    ];

    public static function middleware(): array
    {
        return [new Middleware('can:reports.view')];
    }

    public function index(): View
    {
        return view('reports.index', ['reports' => self::REPORTS]);
    }

    public function show(Request $request, string $report, ReportService $service): View|StreamedResponse
    {
        $definition = self::REPORTS[$report];
        $from = $request->input('from', now()->startOfMonth()->toDateString());
        $to = $request->input('to', now()->toDateString());

        $data = $definition['dated'] ? $service->{$definition['method']}($from, $to) : $service->{$definition['method']}();

        if ($request->input('export') === 'csv') {
            return $this->csv($report, $data);
        }

        return view('reports.'.$definition['method'], [
            'report' => $report,
            'definition' => $definition,
            'from' => $from,
            'to' => $to,
            'data' => $data,
        ]);
    }

    /**
     * Exportación CSV de la tabla principal del informe.
     */
    protected function csv(string $report, array $data): StreamedResponse
    {
        [$headers, $rows] = match ($report) {
            'ventas' => [['Número', 'Fecha', 'Cliente', 'Subtotal', 'Impuestos', 'Total', 'Pagado', 'Saldo'],
                $data['invoices']->map(fn ($d) => [$d->number, $d->date->format('Y-m-d'), $d->customer?->name, $d->subtotal, $d->tax_total, $d->total, $d->amount_paid, $d->balance])],
            'compras' => [['Número', 'Fecha', 'Proveedor', 'Estado', 'Total', 'Saldo'],
                $data['orders']->map(fn ($d) => [$d->number, $d->date->format('Y-m-d'), $d->supplier?->name, $d->statusLabel(), $d->total, $d->balance])],
            'cuentas-por-cobrar', 'cuentas-por-pagar' => [['Tercero', 'Documentos', 'Al día', '1-30', '31-60', '61-90', '+90', 'Total'],
                $data['rows']->map(fn ($r) => [$r['party']?->name, $r['count'], $r['current'], $r['d30'], $r['d60'], $r['d90'], $r['over90'], $r['total']])],
            'inventario' => [['SKU', 'Producto', 'Existencias', 'Costo', 'Valor costo', 'Valor venta'],
                $data['rows']->map(fn ($r) => [$r['product']->sku, $r['product']->name, $r['stock'], $r['product']->cost_price, $r['cost_value'], $r['sale_value']])],
            'gastos' => [['Fecha', 'Descripción', 'Categoría', 'Proveedor', 'Importe'],
                $data['expenses']->map(fn ($e) => [$e->date->format('Y-m-d'), $e->description, $e->category?->name, $e->supplier?->name, $e->amount])],
            'clientes' => [['Cliente', 'Facturas', 'Facturado', 'Saldo'],
                $data['rows']->map(fn ($r) => [$r['customer']->name, $r['count'], $r['total'], $r['balance']])],
            'flujo-de-caja' => [['Cuenta', 'Saldo inicial', 'Entradas', 'Salidas', 'Saldo final'],
                $data['accounts']->map(fn ($r) => [$r['account']->name, $r['opening'], $r['in'], $r['out'], $r['closing']])],
            'impuestos' => [['Tipo', 'Tasa %', 'Base', 'Impuesto'],
                $data['sales']->map(fn ($r) => ['Ventas', $r['rate'], $r['base'], $r['tax']])->concat($data['purchases']->map(fn ($r) => ['Compras', $r['rate'], $r['base'], $r['tax']]))],
            default => [['Concepto', 'Importe'], collect([
                ['Ingresos por ventas', $data['revenue'] ?? 0], ['Devoluciones', $data['returns'] ?? 0], ['Costo de ventas', $data['cogs'] ?? 0],
                ['Utilidad bruta', $data['gross'] ?? 0], ['Otros ingresos', $data['otherIncomeTotal'] ?? 0], ['Gastos', $data['expensesTotal'] ?? 0], ['Utilidad neta', $data['net'] ?? 0],
            ])],
        };

        return response()->streamDownload(function () use ($headers, $rows) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, $headers, ';');
            foreach ($rows as $row) {
                fputcsv($out, array_map(fn ($v) => is_numeric($v) ? str_replace('.', ',', (string) $v) : $v, $row), ';');
            }
            fclose($out);
        }, "informe-{$report}-".now()->format('Ymd').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
