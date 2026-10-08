<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Support\Permissions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class RoleController extends ModuleController
{
    protected static string $module = 'roles';

    public function index(): View
    {
        return view('roles.index', ['roles' => Role::withCount('users')->orderBy('name')->get(), 'total' => count(Permissions::all())]);
    }

    public function create(): View
    {
        return view('roles.form', ['role' => new Role(['permissions' => []]), 'groups' => config('permissions.groups')]);
    }

    public function store(Request $request): RedirectResponse
    {
        Role::create($this->validated($request));

        return redirect()->route('roles.index')->with('success', 'Rol creado.');
    }

    public function edit(Role $role): View
    {
        return view('roles.form', ['role' => $role, 'groups' => config('permissions.groups')]);
    }

    public function update(Request $request, Role $role): RedirectResponse
    {
        $role->update($this->validated($request, $role));

        return redirect()->route('roles.index')->with('success', 'Rol actualizado.');
    }

    public function destroy(Role $role): RedirectResponse
    {
        if ($role->is_system || $role->users()->exists()) {
            return back()->with('error', 'No se puede eliminar un rol del sistema o con usuarios asignados.');
        }

        $role->delete();

        return redirect()->route('roles.index')->with('success', 'Rol eliminado.');
    }

    protected function validated(Request $request, ?Role $role = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:60'],
            'description' => ['nullable', 'string', 'max:255'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => [Rule::in(Permissions::all())],
        ], [], ['name' => 'nombre']);

        $data['permissions'] = array_values($data['permissions'] ?? []);

        return $data;
    }
}
