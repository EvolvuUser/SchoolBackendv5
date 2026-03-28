<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\JsonResponse;

class DropdownController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = min($request->get('per_page', 20), 100);

        $data = DB::table('dropdown_master')
            ->orderByDesc('id')
            ->paginate($perPage);

        return response()->json($data);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'code' => 'required|string|max:100|unique:dropdown_master,code',
            'name' => 'required|string|max:255',
            'module' => 'required|string|max:100',
            'is_active' => 'sometimes|boolean'
        ]);

        try {
            $id = DB::transaction(function () use ($data) {
                return DB::table('dropdown_master')->insertGetId([
                    'code' => $data['code'],
                    'name' => $data['name'],
                    'module' => $data['module'],
                    'is_active' => $data['is_active'] ?? true,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            });

            return response()->json([
                'message' => 'Created',
                'id' => $id
            ], 201);

        } catch (\Throwable $e) {
            Log::error('Dropdown store failed', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'message' => 'Something went wrong'
            ], 500);
        }
    }

    public function show(int $id): JsonResponse
    {
        $dropdown = DB::table('dropdown_master')->where('id', $id)->first();

        if (!$dropdown) {
            return response()->json(['message' => 'Not found'], 404);
        }

        return response()->json($dropdown);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $exists = DB::table('dropdown_master')->where('id', $id)->exists();

        if (!$exists) {
            return response()->json(['message' => 'Not found'], 404);
        }

        $data = $request->validate([
            'code' => "required|string|max:100|unique:dropdown_master,code,$id",
            'name' => 'required|string|max:255',
            'module' => 'required|string|max:100',
            'is_active' => 'sometimes|boolean'
        ]);

        try {
            DB::transaction(function () use ($data, $id) {
                DB::table('dropdown_master')->where('id', $id)->update([
                    'code' => $data['code'],
                    'name' => $data['name'],
                    'module' => $data['module'],
                    'is_active' => $data['is_active'] ?? true,
                    'updated_at' => now()
                ]);
            });

            return response()->json(['message' => 'Updated']);

        } catch (\Throwable $e) {
            Log::error('Dropdown update failed', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'message' => 'Something went wrong'
            ], 500);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            $deleted = DB::table('dropdown_master')->where('id', $id)->delete();

            if (!$deleted) {
                return response()->json(['message' => 'Not found'], 404);
            }

            return response()->json(['message' => 'Deleted']);

        } catch (\Throwable $e) {
            Log::error('Dropdown delete failed', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'message' => 'Something went wrong'
            ], 500);
        }
    }
}