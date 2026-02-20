<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\DailyTodo;
use App\Models\Event;
use App\Models\StaffNotice;
use App\Models\Teacher;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Validator;

class GuideNavigationController extends Controller
{
    public function menus() {
        $menus = DB::table('menus')->get();
        return response()->json($menus);
    }
    public function createHelpGuides(Request $request)
    {
        // Validation
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'required|boolean',

            'steps' => 'required|array|min:1',
            'steps.*.menu_id' => 'nullable|integer',
            'steps.*.route' => 'required|string|max:255',
            'steps.*.title' => 'required|string|max:255',
            'steps.*.content' => 'required|string',
            'steps.*.step_order' => 'required|integer',
            'steps.*.is_active' => 'required|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }

        DB::beginTransaction();

        try {

            // Insert Guide
            $guideId = DB::table('help_guides')->insertGetId([
                'name' => $request->name,
                'description' => $request->description,
                'is_active' => $request->is_active,
                'created_at' => now(),
                'updated_at' => now()
            ]);

            // Insert Steps
            foreach ($request->steps as $step) {

                DB::table('help_guide_steps')->insert([
                    'guide_id' => $guideId,
                    'menu_id' => $step['menu_id'] ?? null,
                    'route' => $step['route'],
                    'title' => $step['title'],
                    'content' => $step['content'],
                    'step_order' => $step['step_order'],
                    'is_active' => $step['is_active'],
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Help guide created successfully'
            ]);

        } catch (\Exception $e) {

            DB::rollBack();

            return response()->json([
                'status' => false,
                'message' => 'Something went wrong',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
