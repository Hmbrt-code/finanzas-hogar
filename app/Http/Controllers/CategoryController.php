<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CategoryController extends Controller
{
    public function index()
    {
        $household = auth()->user()->member->household;

        // Lista plana con parent_id — el árbol se construye en el frontend
        $categories = $household->categories()
            ->orderBy('parent_id')
            ->orderBy('type')
            ->orderBy('name')
            ->get(['id', 'parent_id', 'name', 'type', 'icon', 'color']);

        return Inertia::render('Categories/Index', [
            'categories' => $categories,
        ]);
    }

    public function store(Request $request)
    {
        $household = auth()->user()->member->household;

        $validated = $request->validate([
            'name'      => 'required|string|max:100',
            'type'      => 'required|in:income,expense',
            'icon'      => 'nullable|string|max:50',
            'color'     => 'nullable|string|max:7',
            'parent_id' => 'nullable|integer|exists:categories,id',
        ]);

        // Si tiene padre, hereda su tipo
        if (! empty($validated['parent_id'])) {
            $parent = Category::find($validated['parent_id']);
            abort_if($parent->household_id !== $household->id, 403);
            $validated['type'] = $parent->type;
        }

        $household->categories()->create($validated);

        return back();
    }

    public function update(Request $request, Category $category)
    {
        $this->authorizeCategory($category);
        $household = auth()->user()->member->household;

        $validated = $request->validate([
            'name'      => 'required|string|max:100',
            'type'      => 'required|in:income,expense',
            'icon'      => 'nullable|string|max:50',
            'color'     => 'nullable|string|max:7',
            'parent_id' => 'nullable|integer|exists:categories,id',
        ]);

        // Evitar ciclos: no puede ser su propio padre ni hijo suyo
        if (! empty($validated['parent_id'])) {
            abort_if($validated['parent_id'] === $category->id, 422);
            $parent = Category::find($validated['parent_id']);
            abort_if($parent->household_id !== $household->id, 403);
            $validated['type'] = $parent->type;
        }

        $category->update($validated);

        return back();
    }

    public function destroy(Category $category)
    {
        $this->authorizeCategory($category);
        $category->delete();
        return back();
    }

    private function authorizeCategory(Category $category): void
    {
        $householdId = auth()->user()->member->household_id;
        abort_if($category->household_id !== $householdId, 403);
    }
}
