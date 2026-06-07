<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Auction category management (spec §8.2). Gated by 'categories.manage'
 * (SUPER_ADMIN and CONTENT_ADMIN).
 */
class AdminCategoryController extends Controller
{
    public function index(): View
    {
        $this->authorize('categories.manage');

        $categories = Category::withCount('auctions')->orderBy('name_ar')->get();

        return view('admin.categories.index', compact('categories'));
    }

    public function create(): View
    {
        $this->authorize('categories.manage');

        return view('admin.categories.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('categories.manage');

        $category = Category::create($this->validateData($request));

        AuditLog::log('CATEGORY_CREATED', 'Category', (string) $category->id, null, null, [
            'name' => $category->name_ar,
        ]);

        return redirect()->route('admin.categories.index')
            ->with('success', __('admin.categories.flash_created'));
    }

    public function edit(Category $category): View
    {
        $this->authorize('categories.manage');

        return view('admin.categories.edit', compact('category'));
    }

    public function update(Request $request, Category $category): RedirectResponse
    {
        $this->authorize('categories.manage');

        $category->update($this->validateData($request));

        AuditLog::log('CATEGORY_UPDATED', 'Category', (string) $category->id);

        return redirect()->route('admin.categories.index')
            ->with('success', __('admin.categories.flash_updated'));
    }

    public function destroy(Category $category): RedirectResponse
    {
        $this->authorize('categories.manage');

        if ($category->auctions()->exists()) {
            return back()->withErrors(['delete' => __('admin.categories.cannot_delete_has_auctions')]);
        }

        $id = $category->id;
        $category->delete();

        AuditLog::log('CATEGORY_DELETED', 'Category', (string) $id);

        return redirect()->route('admin.categories.index')
            ->with('success', __('admin.categories.flash_deleted'));
    }

    private function validateData(Request $request): array
    {
        $validated = $request->validate([
            'name_ar' => ['required', 'string', 'max:100'],
            'name_fr' => ['nullable', 'string', 'max:100'],
            'name_en' => ['nullable', 'string', 'max:100'],
            'icon' => ['nullable', 'string', 'max:100'],
        ]);

        $validated['is_active'] = $request->boolean('is_active');

        return $validated;
    }
}
