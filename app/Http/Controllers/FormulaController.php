<?php

namespace App\Http\Controllers;

use App\Models\Formula;
use App\Support\FormulaEvaluator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class FormulaController extends SettingsModuleController
{
    public function index(): View
    {
        return view('formulas.index', ['formulas' => Formula::orderBy('sort_order')->orderBy('id')->get()]);
    }

    public function create(): View
    {
        return view('formulas.form', ['formula' => new Formula(['applies_to' => 'sales', 'action' => 'add', 'is_active' => true]), 'available' => $this->availableVariables()]);
    }

    public function store(Request $request, FormulaEvaluator $evaluator): RedirectResponse
    {
        Formula::create($this->validated($request, $evaluator));

        return redirect()->route('formulas.index')->with('success', 'Fórmula creada. Se aplicará a los nuevos documentos y a los que se editen.');
    }

    public function edit(Formula $formula): View
    {
        return view('formulas.form', ['formula' => $formula, 'available' => $this->availableVariables($formula)]);
    }

    public function update(Request $request, Formula $formula, FormulaEvaluator $evaluator): RedirectResponse
    {
        $formula->update($this->validated($request, $evaluator, $formula));

        return redirect()->route('formulas.index')->with('success', 'Fórmula actualizada.');
    }

    public function destroy(Formula $formula): RedirectResponse
    {
        $formula->delete();

        return redirect()->route('formulas.index')->with('success', 'Fórmula eliminada.');
    }

    protected function availableVariables(?Formula $except = null): array
    {
        $codes = Formula::when($except, fn ($q) => $q->where('id', '!=', $except->id))->pluck('code')->map(fn ($c) => strtolower($c))->all();

        return array_merge(array_keys(Formula::VARIABLES), $codes);
    }

    protected function validated(Request $request, FormulaEvaluator $evaluator, ?Formula $formula = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'code' => ['required', 'regex:/^[a-zA-Z_][a-zA-Z0-9_]*$/', 'max:40', Rule::notIn(array_merge(array_keys(Formula::VARIABLES), FormulaEvaluator::FUNCTIONS))],
            'expression' => ['required', 'string', 'max:500'],
            'applies_to' => ['required', Rule::in(array_keys(Formula::APPLIES_TO))],
            'action' => ['required', Rule::in(array_keys(Formula::ACTIONS))],
            'description' => ['nullable', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ], [
            'code.regex' => 'El código solo puede contener letras, números y guiones bajos, y no puede empezar por un número.',
            'code.not_in' => 'El código no puede coincidir con una variable o función del sistema.',
        ], ['name' => 'nombre', 'code' => 'código', 'expression' => 'expresión']);

        if ($error = $evaluator->validate($data['expression'], $this->availableVariables($formula))) {
            throw ValidationException::withMessages(['expression' => 'Expresión no válida: '.$error]);
        }

        $data['code'] = strtolower($data['code']);
        $data['sort_order'] = $data['sort_order'] ?? 0;
        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }
}
