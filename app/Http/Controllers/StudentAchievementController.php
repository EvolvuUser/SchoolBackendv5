<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Facades\JWTAuth;

class StudentAchievementController extends Controller
{
    private function authenticateUser()
    {
        try {
            return JWTAuth::parseToken()->authenticate();
        } catch (JWTException $e) {
            return null;
        }
    }

    public function erpIndex(Request $req)
    {
        $user = $this->authenticateUser();
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $payload = JWTAuth::getPayload();
        if (in_array($payload->get('role_id'), ['P', 'S'], true)) {
            return response()->json(['message' => 'ERP access required.'], 403);
        }

        $filters = $req->validate([
            'class_id' => 'required|integer|min:1',
            'section_id' => 'required|integer|min:1',
            'student_id' => 'nullable|integer|min:1',
            'academic_year' => 'nullable|string|max:11',
        ]);

        $academicYear = $filters['academic_year'] ?? $payload->get('academic_year');
        if (!$academicYear) {
            return response()->json([
                'message' => 'An academic year is required in the request or token.',
                'errors' => ['academic_year' => ['The academic year field is required.']],
            ], 422);
        }

        $query = DB::table('student_achievements as a')
            ->join('student as s', 'a.student_id', '=', 's.student_id')
            ->leftJoin('class as c', 's.class_id', '=', 'c.class_id')
            ->leftJoin('section as sec', 's.section_id', '=', 'sec.section_id')
            ->where('s.class_id', $filters['class_id'])
            ->where('s.section_id', $filters['section_id'])
            ->where('s.academic_yr', $academicYear)
            ->where('a.academic_year', $academicYear)
            ->select(
                'a.*',
                's.first_name',
                's.mid_name',
                's.last_name',
                's.roll_no',
                's.class_id',
                's.section_id',
                'c.name as class_name',
                'sec.name as section_name'
            );

        if (isset($filters['student_id'])) {
            $query->where('a.student_id', $filters['student_id']);
        }

        $achievements = $query
            ->orderBy('s.class_id')
            ->orderBy('s.section_id')
            ->orderBy('s.student_id')
            ->orderByDesc('a.achievement_date')
            ->orderByDesc('a.id')
            ->get();

        // Fetch attachments together to avoid one query per achievement.
        $files = DB::table('achievement_files')
            ->whereIn('achievement_id', $achievements->pluck('id'))
            ->orderBy('id')
            ->get()
            ->map(function ($file) {
                $file->file_url = asset('storage/' . $file->file_url);
                return $file;
            })
            ->groupBy('achievement_id');

        $achievements->transform(function ($achievement) use ($files) {
            $achievement->student_name = collect([
                $achievement->first_name,
                $achievement->mid_name,
                $achievement->last_name,
            ])->filter(fn($name) => $name !== null && trim($name) !== '')->implode(' ');
            $achievement->is_verified = (bool) $achievement->is_verified;
            $achievement->is_external = (bool) $achievement->is_external;
            $achievement->files = $files->get($achievement->id, collect())->values();
            $achievement->files_count = $achievement->files->count();
            return $achievement;
        });

        return response()->json([
            'data' => $achievements,
            'filters' => [
                'class_id' => (int) $filters['class_id'],
                'section_id' => (int) $filters['section_id'],
                'student_id' => isset($filters['student_id']) ? (int) $filters['student_id'] : null,
                'academic_year' => $academicYear,
            ],
            'meta' => [
                'total' => $achievements->count(),
            ],
        ]);
    }

    // DONE
    public function childrens(Request $req)
    {
        $user = $this->authenticateUser();
        $payload = JWTAuth::getPayload();
        $parent_id = $payload->get('reg_id');
        $academic_year = $payload->get('academic_year');
        // dd($parent_id,$academic_year);
        $student_id = $req->query('student_id');
        $students = DB::table('student as s')
            ->leftJoin('class as c', 's.class_id', '=', 'c.class_id')
            ->leftJoin('section as sec', 's.section_id', '=', 'sec.section_id')
            ->where('s.parent_id', $parent_id)
            ->where('s.academic_yr', $academic_year)
            ->orderByRaw('CASE WHEN s.student_id = ? THEN 0 ELSE 1 END', [$student_id])
            ->select(
                's.*',
                'c.name as class_name',
                'sec.name as section_name'
            )
            ->get();

        return response()->json([
            'data' => $students,
        ], 200);
    }

    // DONE
    public function index(Request $req)
    {
        $query = DB::table('student_achievements');

        $user = $this->authenticateUser();
        $payload = JWTAuth::getPayload();
        $academic_year = $payload->get('academic_year');
        $role_id = $payload->get('role_id');
        $parent_id = $payload->get('reg_id');

        // currently data is filtered to student_id

        if ($req->reg_no) {
            $query->where('reg_no', $req->reg_no);
        }

        if ($req->student_id) {
            $query->where('student_id', $req->student_id);
        }

        if ($req->academic_year) {
            $query->where('academic_year', $req->academic_year);
        }

        return response()->json([
            'data' => $query->orderBy('achievement_date', 'desc')->get(),
        ],
            200);
    }

    // DONE
    public function store(Request $req)
    {
        $user = $this->authenticateUser();
        $payload = JWTAuth::getPayload();
        $academic_year = $req->academic_year ?? $payload->get('academic_year');
        $role_id = $payload->get('role_id');
        $id = DB::table('student_achievements')->insertGetId([
            'reg_no' => $req->reg_no,
            'student_id' => $req->student_id,
            'academic_year' => $academic_year,
            'title' => $req->title,
            'description' => $req->description,
            'achievement_date' => $req->achievement_date,
            'type' => $req->type,
            'level' => $req->level,
            'organization_name' => $req->organization_name,
            'event_name' => $req->event_name,
            'score' => $req->score,
            'position' => $req->position,
            'is_external' => $role_id == 'P' ? 1 : 0,
            'created_at' => now(),
        ]);
        return response()->json(['id' => $id], 201);
    }

    // DONE
    public function uploadFile(Request $req, $id)
    {
        $user = $this->authenticateUser();
        $payload = JWTAuth::getPayload();
        $short_name = $payload->get('short_name');
        if (!$req->hasFile('file')) {
            return response()->json(['message' => 'No file'], 400);
        }

        $file = $req->file('file');
        $path = $file->store($short_name . '/achievements', 'public');

        DB::table('achievement_files')->insert([
            'achievement_id' => $id,
            'file_url' => $path,
            'file_type' => $file->getClientOriginalExtension(),
            'uploaded_at' => now(),
        ]);

        return response()->json(['file_url' => $path]);
    }

    // DONE
    public function getFiles($id)
    {
        $user = $this->authenticateUser();

        $files = DB::table('achievement_files')
            ->where('achievement_id', $id)
            ->get();

        return $files->map(function ($file) {
            $file->file_url = asset('storage/' . $file->file_url);
            return $file;
        });
    }

    // DONE
    public function deleteFile($fileId)
    {
        $user = $this->authenticateUser();

        $file = DB::table('achievement_files')
            ->where('id', $fileId)
            ->first();

        if (!$file) {
            return response()->json([
                'message' => 'File not found'
            ], 404);
        }

        // physically delete file
        if ($file->file_url && Storage::disk('public')->exists($file->file_url)) {
            Storage::disk('public')->delete($file->file_url);
        }

        // remove db record
        DB::table('achievement_files')
            ->where('id', $fileId)
            ->delete();

        return response()->json([
            'message' => 'File deleted successfully'
        ]);
    }

    // DONE
    public function show($id)
    {
        $user = $this->authenticateUser();
        $achievement = DB::table('student_achievements')->where('id', $id)->first();

        if (!$achievement) {
            return response()->json(['message' => 'Not found'], 404);
        }

        return $achievement;
    }

    // DONE
    public function update(Request $req, $id)
    {
        $user = $this->authenticateUser();
        DB::table('student_achievements')
            ->where('id', $id)
            ->update([
                'title' => $req->title,
                'description' => $req->description,
                'achievement_date' => $req->achievement_date,
                'type' => $req->type,
                'level' => $req->level,
                'organization_name' => $req->organization_name,
                'event_name' => $req->event_name,
                'score' => $req->score,
                'position' => $req->position,
                'is_external' => $req->is_external,
            ]);
        return response()->json(['message' => 'Updated']);
    }

    // DONE
    public function destroy($id)
    {
        $user = $this->authenticateUser();

        // get all files first
        $files = DB::table('achievement_files')
            ->where('achievement_id', $id)
            ->get();

        // physically delete files
        foreach ($files as $file) {
            if ($file->file_url && Storage::disk('public')->exists($file->file_url)) {
                Storage::disk('public')->delete($file->file_url);
            }
        }

        // delete db records
        DB::table('achievement_files')
            ->where('achievement_id', $id)
            ->delete();

        DB::table('student_achievements')
            ->where('id', $id)
            ->delete();

        return response()->json([
            'message' => 'Deleted successfully'
        ]);
    }

    // DONE
    public function verify($id)
    {
        $user = $this->authenticateUser();
        DB::table('student_achievements')
            ->where('id', $id)
            ->update(['is_verified' => 1]);
        return response()->json(['message' => 'Verified']);
    }
}
