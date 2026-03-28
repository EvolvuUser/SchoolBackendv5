<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;

class DropdownOptionController extends Controller
{
    public function getByCode(string $code): JsonResponse
    {
        $dropdown = DB::table('dropdown_master')
            ->where('code', $code)
            ->first();

        if (!$dropdown) {
            return response()->json(['message' => 'Dropdown not found'], 404);
        }

        $options = DB::table('dropdown_options')
            ->where('dropdown_id', $dropdown->id)
            ->orderBy('sequence')
            ->get();

        return response()->json($options);
    }

    public function index(Request $request, int $id): JsonResponse
    {
        $perPage = min($request->get('per_page', 50), 100);

        $options = DB::table('dropdown_options')
            ->where('dropdown_id', $id)
            ->orderBy('sequence')
            ->paginate($perPage);

        return response()->json($options);
    }

    public function store(Request $request, int $id): JsonResponse
    {
        $exists = DB::table('dropdown_master')->where('id', $id)->exists();

        if (!$exists) {
            return response()->json(['message' => 'Dropdown not found'], 404);
        }

        $data = $request->validate([
            'value' => "required|string|max:100|unique:dropdown_options,value,NULL,id,dropdown_id,$id",
            'label' => 'required|string|max:255',
            'sequence' => 'sometimes|integer',
            'parent_id' => 'nullable|exists:dropdown_options,id',
            'is_active' => 'sometimes|boolean'
        ]);

        $optionId = DB::transaction(function () use ($data, $id) {
            return DB::table('dropdown_options')->insertGetId([
                'dropdown_id' => $id,
                'value' => $data['value'],
                'label' => $data['label'],
                'sequence' => $data['sequence'] ?? 0,
                'parent_id' => $data['parent_id'] ?? null,
                'is_active' => $data['is_active'] ?? true,
                'created_at' => now(),
                'updated_at' => now()
            ]);
        });

        return response()->json([
            'message' => 'Created',
            'id' => $optionId
        ], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $option = DB::table('dropdown_options')->where('id', $id)->first();

        if (!$option) {
            return response()->json(['message' => 'Option not found'], 404);
        }

        $data = $request->validate([
            'value' => "required|string|max:100|unique:dropdown_options,value,$id,id,dropdown_id,$option->dropdown_id",
            'label' => 'required|string|max:255',
            'sequence' => 'sometimes|integer',
            'parent_id' => 'nullable|exists:dropdown_options,id',
            'is_active' => 'sometimes|boolean'
        ]);

        DB::transaction(function () use ($data, $id) {
            DB::table('dropdown_options')->where('id', $id)->update([
                'value' => $data['value'],
                'label' => $data['label'],
                'sequence' => $data['sequence'] ?? 0,
                'parent_id' => $data['parent_id'] ?? null,
                'is_active' => $data['is_active'] ?? true,
                'updated_at' => now()
            ]);
        });

        return response()->json(['message' => 'Updated']);
    }

    public function destroy(int $id): JsonResponse
    {
        $deleted = DB::table('dropdown_options')->where('id', $id)->delete();

        if (!$deleted) {
            return response()->json(['message' => 'Option not found'], 404);
        }

        return response()->json(['message' => 'Deleted']);
    }

    public function children(int $id): JsonResponse
    {
        $children = DB::table('dropdown_options')
            ->where('parent_id', $id)
            ->orderBy('sequence')
            ->get();

        return response()->json($children);
    }
}