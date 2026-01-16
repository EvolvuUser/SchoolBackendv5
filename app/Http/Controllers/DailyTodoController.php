<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\DailyTodo;
use App\Models\Event;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Facades\JWTAuth;
use Exception;

class DailyTodoController extends Controller
{
    private function authenticateUser()
    {
        try {
            return JWTAuth::parseToken()->authenticate();
        } catch (JWTException $e) {
            return null;
        }
    }

    /**
     * Get all todos for logged-in user (dashboard list)
     */
    public function index(Request $request)
    {
        try {
            $this->authenticateUser();

            $reg_id = JWTAuth::getPayload()->get('reg_id');
            $login_type = JWTAuth::getPayload()->get('role_id');

            // Get timezone from client (fallback if not sent)
            $timezone = $request->timezone ?? 'Asia/Kolkata';

            // Validate timezone
            if (!in_array($timezone, timezone_identifiers_list())) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Invalid timezone'
                ], 400);
            }

            // Today's start & end in USER timezone
            $startOfDay = Carbon::now($timezone)->startOfDay()->timezone('UTC');
            $endOfDay = Carbon::now($timezone)->endOfDay()->timezone('UTC');

            $today = Carbon::today();

            $todos = DailyTodo::where('reg_id', $reg_id)
                ->where('login_type', $login_type)
                ->where(function ($query) use ($today) {
                    // Pending tasks → always visible
                    $query
                        ->where('is_completed', 0)
                        // Completed tasks → only if due_date is today or future
                        ->orWhere(function ($q) use ($today) {
                            $q
                                ->where('is_completed', 1)
                                ->whereDate('due_date', '>=', $today);
                        });
                })
                // 🔥 Pending tasks first
                ->orderByRaw('is_completed ASC')
                // Optional: nearer due dates first
                ->orderBy('due_date', 'asc')
                // Latest created last
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'status' => 'success',
                'data' => $todos
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch todos',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function showAll(Request $request)
    {
        try {
            $this->authenticateUser();

            $reg_id = JWTAuth::getPayload()->get('reg_id');
            $login_type = JWTAuth::getPayload()->get('role_id');

            $todos = DailyTodo::where('reg_id', $reg_id)
                ->where('login_type', $login_type)
                // 🔥 Pending tasks first
                ->orderByRaw('is_completed ASC')
                // Optional: nearer due dates first
                ->orderBy('due_date', 'asc')
                // Latest created last
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'status' => 'success',
                'data' => $todos
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch todos',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create a new todo
     */
    public function store(Request $request)
    {
        try {
            $user = $this->authenticateUser();
            $reg_id = JWTAuth::getPayload()->get('reg_id');
            $login_type = JWTAuth::getPayload()->get('role_id');

            $validator = Validator::make($request->all(), [
                'title' => 'required|string|max:255',
                'description' => 'nullable|string',
                'due_date' => 'nullable|date'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'validation_error',
                    'errors' => $validator->errors()
                ], 422);
            }

            $todo = DailyTodo::create([
                'title' => $request->title,
                'description' => $request->description,
                'due_date' => $request->due_date,
                'login_type' => $login_type,
                'reg_id' => $reg_id,
                'is_completed' => false
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Todo created successfully',
                'data' => $todo
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create todo',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Show a specific todo
     */
    public function show($id)
    {
        try {
            $user = $this->authenticateUser();
            $reg_id = JWTAuth::getPayload()->get('reg_id');
            $login_type = JWTAuth::getPayload()->get('role_id');

            $todo = DailyTodo::where('id', $id)
                ->where('reg_id', $reg_id)
                ->where('login_type', $login_type)
                ->first();

            if (!$todo) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Todo not found'
                ], 404);
            }

            return response()->json([
                'status' => 'success',
                'data' => $todo
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch todo',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update a todo
     */
    public function update(Request $request, $id)
    {
        try {
            $user = $this->authenticateUser();
            $reg_id = JWTAuth::getPayload()->get('reg_id');
            $login_type = JWTAuth::getPayload()->get('role_id');

            $validator = Validator::make($request->all(), [
                'title' => 'required|string|max:255',
                'description' => 'nullable|string',
                'is_completed' => 'nullable|boolean',
                'due_date' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'validation_error',
                    'errors' => $validator->errors()
                ], 422);
            }

            $todo = DailyTodo::where('id', $id)
                ->where('reg_id', $reg_id)
                ->where('login_type', $login_type)
                ->first();

            if (!$todo) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Todo not found'
                ], 404);
            }

            $todo->update($request->only(['title', 'description', 'is_completed' , 'due_date']));

            return response()->json([
                'status' => 'success',
                'message' => 'Todo updated successfully',
                'data' => $todo
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update todo',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete a todo
     */
    public function destroy($id)
    {
        try {
            $user = $this->authenticateUser();
            $reg_id = JWTAuth::getPayload()->get('reg_id');
            $login_type = JWTAuth::getPayload()->get('role_id');

            $todo = DailyTodo::where('id', $id)
                ->where('reg_id', $reg_id)
                ->where('login_type', $login_type)
                ->first();

            if (!$todo) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Todo not found'
                ], 404);
            }

            $todo->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Todo deleted successfully'
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete todo',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Toggle completion status
     */
    public function toggleCompletion($id)
    {
        try {
            $user = $this->authenticateUser();
            $reg_id = JWTAuth::getPayload()->get('reg_id');
            $login_type = JWTAuth::getPayload()->get('role_id');

            $todo = DailyTodo::where('id', $id)
                ->where('reg_id', $reg_id)
                ->where('login_type', $login_type)
                ->first();

            if (!$todo) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Todo not found'
                ], 404);
            }

            $todo->is_completed = !$todo->is_completed;
            $todo->save();

            return response()->json([
                'status' => 'success',
                'message' => 'Todo status updated',
                'data' => $todo
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to toggle todo status',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
