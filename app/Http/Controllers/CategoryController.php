<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CategoryController extends SettingsModuleController
{
    public function index(Request $request): View
    {
        $type = array_key_exists($request->input('type'), Category::TYPES) ? $request->input('type') : 'product';

        return view('categories.index', [
            'type' => $type,
            'categories' => Category::ofType($type)->get(),
        ]);
    }

    public function create(Request $request): View
    {
        return view('categories.form', ['category' => new Category(['type' => $request->input('type', 'product'), 'color' => '#6366f1'])]);
    }

    public function store(Request $request): RedirectResponse
    {
        $category = Category::create($this->validated($request));

        return redirect()->route('categories.index', ['type' => $category->type])->with('success', 'Categoría creada.');
    }

    public function edit(Category $category): View
    {
        return view('categories.form', compact('category'));
    }

    public function update(Request $request, Category $category): RedirectResponse
    {
        $category->update($this->validated($request));

        return redirect()->route('categories.index', ['type' => $category->type])->with('success', 'Categoría actualizada.');
    }

    public function destroy(Category $category): RedirectResponse
    {
        $type = $category->type;
        $category->delete();

        return redirect()->route('categories.index', ['type' => $type])->with('success', 'Categoría eliminada.');
    }

    protected function validated(Request $request): array
    {
        return $request->validate([
            'type' => ['required', Rule::in(array_keys(Category::TYPES))],
            'name' => ['required', 'string', 'max:100'],
            'color' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
        ], [], ['name' => 'nombre']);
    }
}
