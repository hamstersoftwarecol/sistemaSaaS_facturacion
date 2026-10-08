<?php

namespace App\Http\Controllers;

use App\Models\CustomField;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CustomFieldController extends SettingsModuleController
{
    public function index(): View
    {
        return view('custom-fields.index', [
            'fields' => CustomField::orderBy('entity')->orderBy('sort_order')->get()->groupBy('entity'),
        ]);
    }

    public function create(Request $request): View
    {
        return view('custom-fields.form', ['field' => new CustomField([
            'entity' => $request->input('entity', 'customer'), 'type' => 'text', 'is_active' => true,
        ])]);
    }

    public function store(Request $request): RedirectResponse
    {
        CustomField::create($this->validated($request));

        return redirect()->route('custom-fields.index')->with('success', 'Campo personalizado creado.');
    }

    public function edit(CustomField $customField): View
    {
        return view('custom-fields.form', ['field' => $customField]);
    }

    public function update(Request $request, CustomField $customField): RedirectResponse
    {
        $customField->update($this->validated($request, $customField));

        return redirect()->route('custom-fields.index')->with('success', 'Campo personalizado actualizado.');
    }

    public function destroy(CustomField $customField): RedirectResponse
    {
        $customField->delete();

        return redirect()->route('custom-fields.index')->with('success', 'Campo eliminado. Los valores guardados se conservan en los registros.');
    }

    protected function validated(Request $request, ?CustomField $field = null): array
    {
        $data = $request->validate([
            'entity' => ['required', Rule::in(array_keys(CustomField::ENTITIES))],
            'label' => ['required', 'string', 'max:80'],
            'type' => ['required', Rule::in(array_keys(CustomField::TYPES))],
            'options' => ['nullable', 'required_if:type,select', 'string', 'max:1000'],
            'is_required' => ['nullable', 'boolean'],
            'show_on_document' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ], ['options.required_if' => 'Indique las opciones de la lista (una por línea o separadas por coma).'], ['label' => 'etiqueta']);

        $data['name'] = $field?->name ?? Str::slug($data['label'], '_');
        $data['options'] = $data['type'] === 'select'
            ? collect(preg_split('/[\r\n,]+/', (string) $data['options']))->map(fn ($o) => trim($o))->filter()->unique()->values()->all()
            : null;
        $data['is_required'] = $request->boolean('is_required');
        $data['show_on_document'] = $request->boolean('show_on_document');
        $data['is_active'] = $request->boolean('is_active');
        $data['sort_order'] = $data['sort_order'] ?? 0;

        return $data;
    }
}
