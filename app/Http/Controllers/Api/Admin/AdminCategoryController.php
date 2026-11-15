<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminAuditLog;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AdminCategoryController extends Controller
{
    private function rules(?Category $category = null): array
    {
        return [
            'name' => ['required', 'string', 'max:100', Rule::unique('categories', 'name')->ignore($category?->id)],
            'description' => 'nullable|string|max:500',
            // Solo clases de Font Awesome: el icono se pinta como clase CSS.
            'icon' => ['nullable', 'string', 'max:50', 'regex:/^fa-(solid|regular|brands) fa-[a-z0-9-]+$/'],
            'color' => ['nullable', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
        ];
    }

    /** Slug único a partir del nombre: "Historia", "historia-2"... */
    private function uniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name) ?: 'categoria';
        $slug = $base;

        for ($n = 2; Category::where('slug', $slug)->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))->exists(); $n++) {
            $slug = "{$base}-{$n}";
        }

        return $slug;
    }

    public function index(): JsonResponse
    {
        // Recuento real (tests_count es un contador que podría desincronizarse).
        return response()->json(Category::withCount('tests as tests_total')->orderBy('name')->get());
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate($this->rules());

        $category = DB::transaction(function () use ($data, $request) {
            $category = Category::create($data + ['slug' => $this->uniqueSlug($data['name'])]);
            AdminAuditLog::record($request->user(), 'category.created', 'category', $category->id, ['name' => $category->name]);

            return $category;
        });

        return response()->json(['message' => 'Categoría creada.', 'category' => $category], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $category = Category::findOrFail($id);
        $data = $request->validate($this->rules($category));

        DB::transaction(function () use ($category, $data, $request) {
            $changes = array_keys(array_diff_assoc(array_map('strval', $data), array_map('strval', $category->only(array_keys($data)))));
            if ($data['name'] !== $category->name) {
                $data['slug'] = $this->uniqueSlug($data['name'], $category->id);
            }
            $category->update($data);
            AdminAuditLog::record($request->user(), 'category.updated', 'category', $category->id, ['fields' => $changes]);
        });

        return response()->json(['message' => 'Categoría actualizada.', 'category' => $category->fresh()]);
    }

    /** Los tests de la categoría se quedan sin ella; no se borran. */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $category = Category::withCount('tests as tests_total')->findOrFail($id);

        DB::transaction(function () use ($category, $request) {
            AdminAuditLog::record($request->user(), 'category.deleted', 'category', $category->id, [
                'name' => $category->name,
                'tests' => $category->tests_total,
            ]);
            $category->delete();
        });

        return response()->json(['message' => 'Categoría eliminada.']);
    }
}
