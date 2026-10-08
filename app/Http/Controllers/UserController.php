<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Role;
use App\Models\User;
use App\Services\SubscriptionService;
use App\Support\Tenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class UserController extends ModuleController
{
    protected static string $module = 'users';

    protected function scoped()
    {
        return User::where('company_id', Tenant::id());
    }

    protected function findUser(User $user): User
    {
        abort_unless($user->company_id === Tenant::id(), 404);
        abort_if($user->is_owner && ! auth()->user()->is_owner, 403, 'Solo el propietario puede modificar su propia cuenta.');

        return $user;
    }

    public function index(): View
    {
        return view('users.index', ['users' => $this->scoped()->with(['role', 'branch'])->orderByDesc('is_owner')->orderBy('name')->get()]);
    }

    public function create(): View
    {
        return view('users.form', $this->formData(new User(['is_active' => true])));
    }

    public function store(Request $request, SubscriptionService $subscriptions): RedirectResponse
    {
        if (! $subscriptions->canCreate(Tenant::company(), 'users')) {
            return back()->withInput()->with('error', $subscriptions->limitMessage('users'));
        }

        $data = $this->validated($request);
        User::create($data + ['company_id' => Tenant::id(), 'email_verified_at' => now()]);

        return redirect()->route('users.index')->with('success', 'Usuario creado. Ya puede iniciar sesión con su correo y contraseña.');
    }

    public function edit(User $user): View
    {
        return view('users.form', $this->formData($this->findUser($user)));
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $this->findUser($user);
        $data = $this->validated($request, $user);

        if ($user->is_owner) {
            $data['is_active'] = true;
            unset($data['role_id']);
        }
        if (empty($data['password'])) {
            unset($data['password']);
        }

        $user->update($data);

        return redirect()->route('users.index')->with('success', 'Usuario actualizado.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        $this->findUser($user);

        if ($user->is_owner || $user->id === $request->user()->id) {
            return back()->with('error', 'No puede eliminar al propietario de la empresa ni su propio usuario.');
        }

        $user->delete();

        return redirect()->route('users.index')->with('success', 'Usuario eliminado.');
    }

    protected function validated(Request $request, ?User $user = null): array
    {
        $companyId = Tenant::id();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:150', Rule::unique('users')->ignore($user?->id)],
            'phone' => ['nullable', 'string', 'max:40'],
            'role_id' => [$user?->is_owner ? 'nullable' : 'required', Rule::exists('roles', 'id')->where('company_id', $companyId)],
            'branch_id' => ['nullable', Rule::exists('branches', 'id')->where('company_id', $companyId)],
            'password' => [$user ? 'nullable' : 'required', 'confirmed', Password::defaults()],
            'is_active' => ['nullable', 'boolean'],
        ], [], ['name' => 'nombre', 'role_id' => 'rol', 'password' => 'contraseña']);

        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }

    protected function formData(User $user): array
    {
        return [
            'user' => $user,
            'roles' => Role::orderBy('name')->get(),
            'branches' => Branch::orderByDesc('is_main')->orderBy('name')->get(),
        ];
    }
}
