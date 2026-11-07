<?php

namespace App\Http\Controllers;

use App\Http\Requests\CategoryStoreRequest;
use App\Http\Requests\CategoryUpdateRequest;
use App\Models\Category;
use App\Models\User;
use App\Services\CategoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function __construct(
        protected CategoryService $categoryService
    ) {}

    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Category::class);

        $query = Category::withCount('products')->latest();

        if ($request->filled('q')) {
            $search = '%'.$request->string('q')->trim().'%';
            $query->where('name', 'like', $search);
        }

        if ($request->filled('status')) {
            $status = $request->string('status')->toString();
            if ($status === 'active') {
                $query->where('is_active', true);
            } elseif ($status === 'inactive') {
                $query->where('is_active', false);
            }
        }

        $perPage = (int) $request->input('per_page', 15);
        if (! in_array($perPage, [15, 25, 50, 100], true)) {
            $perPage = 15;
        }

        $categories = $query->paginate($perPage)->withQueryString();

        return view('categories.index', [
            'categories' => $categories,
        ]);
    }

    public function create(): View
    {
        Gate::authorize('create', Category::class);

        return view('categories.create');
    }

    public function store(CategoryStoreRequest $request): RedirectResponse
    {
        Gate::authorize('create', Category::class);

        /** @var User $actor */
        $actor = $request->user();

        $category = $this->categoryService->create(
            data: $request->validated(),
            actor: $actor,
            ip: $request->ip(),
            userAgent: $request->userAgent()
        );

        return redirect()->route('categories.index')
            ->with('success', "Kategori [{$category->name}] berhasil ditambahkan.");
    }

    public function edit(Category $category): View
    {
        Gate::authorize('update', $category);

        return view('categories.edit', [
            'category' => $category,
        ]);
    }

    public function update(CategoryUpdateRequest $request, Category $category): RedirectResponse
    {
        Gate::authorize('update', $category);

        /** @var User $actor */
        $actor = $request->user();

        $this->categoryService->update(
            category: $category,
            data: $request->validated(),
            actor: $actor,
            ip: $request->ip(),
            userAgent: $request->userAgent()
        );

        return redirect()->route('categories.index')
            ->with('success', "Kategori [{$category->name}] berhasil diperbarui.");
    }

    public function toggleStatus(Request $request, Category $category): RedirectResponse
    {
        Gate::authorize('toggleStatus', $category);

        /** @var User $actor */
        $actor = $request->user();

        $this->categoryService->toggleStatus(
            category: $category,
            actor: $actor,
            ip: $request->ip(),
            userAgent: $request->userAgent()
        );

        $statusStr = $category->is_active ? 'diaktifkan' : 'dinonaktifkan';

        return back()->with('success', "Kategori [{$category->name}] berhasil {$statusStr}.");
    }
}
