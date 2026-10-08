<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class BranchSwitchController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        abort_if($request->user()->branch_id, 403, 'Su usuario está asignado a una sucursal fija.');

        $branchId = $request->integer('branch_id');

        if ($branchId && ! Branch::whereKey($branchId)->exists()) {
            abort(404);
        }

        $request->session()->put('branch_id', $branchId ?: null);

        return back()->with('success', $branchId ? 'Sucursal activa: '.Branch::find($branchId)->name : 'Mostrando todas las sucursales.');
    }
}
