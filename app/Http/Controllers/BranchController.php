<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Services\SubscriptionService;
use App\Support\Tenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class BranchController extends ModuleController
{
    protected static string $module = 'branches';

    public function index(): View
    {
        return view('branches.index', ['branches' => Branch::withCount('users')->orderByDesc('is_main')->orderBy('name')->get()]);
    }

    public function create(): View
    {
        return view('branches.form', ['branch' => new Branch(['is_active' => true])]);
    }

    public function store(Request $request, SubscriptionService $subscriptions): RedirectResponse
    {
        if (! $subscriptions->canCreate(Tenant::company(), 'branches')) {
            return back()->withInput()->with('error', $subscriptions->limitMessage('branches'));
        }

        $this->save(new Branch, $request);

        return redirect()->route('branches.index')->with('success', 'Sucursal creada.');
    }

    public function edit(Branch $branch): View
    {
        return view('branches.form', ['branch' => $branch]);
    }

    public function update(Request $request, Branch $branch): RedirectResponse
    {
        $this->save($branch, $request);

        return redirect()->route('branches.index')->with('success', 'Sucursal actualizada.');
    }

    public function destroy(Branch $branch): RedirectResponse
    {
        if ($branch->is_main) {
            return back()->with('error', 'No se puede eliminar la sucursal principal.');
        }
        if (DB::table('documents')->where('branch_id', $branch->id)->exists() || DB::table('payments')->where('branch_id', $branch->id)->exists()) {
            return back()->with('error', 'La sucursal tiene operaciones registradas. Desactívela en lugar de eliminarla.');
        }

        $branch->delete();

        return redirect()->route('branches.index')->with('success', 'Sucursal eliminada.');
    }

    protected function save(Branch $branch, Request $request): void
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'code' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:150'],
            'phone' => ['nullable', 'string', 'max:40'],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:100'],
            'is_main' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ], [], ['name' => 'nombre']);

        $data['is_main'] = $request->boolean('is_main') || ($branch->exists && $branch->is_main);
        $data['is_active'] = $data['is_main'] || $request->boolean('is_active');

        DB::transaction(function () use ($branch, $data) {
            if ($data['is_main']) {
                Branch::where('id', '!=', $branch->id ?? 0)->update(['is_main' => false]);
            }
            $branch->fill($data)->save();
        });
    }
}
