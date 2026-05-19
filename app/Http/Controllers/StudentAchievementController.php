<?php 

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Storage;

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

    // DONE
    public function childrens(Request $req) {
        $user = $this->authenticateUser();
        $payload = JWTAuth::getPayload();
        $parent_id = $payload->get("reg_id");
        $academic_year = $payload->get('academic_year');
        $students = DB::table("student")->where('parent_id' , $parent_id)->where('academic_yr' , $academic_year)->get();

        return response()->json([
            'data' => $students,
        ],200);
    }

    // DONE
    public function index(Request $req)
    {
        $query = DB::table('student_achievements');

        $user = $this->authenticateUser();
        $payload = JWTAuth::getPayload();
        $academic_year = $payload->get('academic_year');
        $role_id = $payload->get('role_id');
        $parent_id = $payload->get("reg_id");

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
        ]
        ,200);
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
            'is_external' => $role_id == "P" ? 1 : 0,
            'created_at' => now(),
        ]);
        return response()->json(['id' => $id], 201);
    }

    // DONE
    public function uploadFile(Request $req, $id)
    {
        $user = $this->authenticateUser();
        $payload = JWTAuth::getPayload();
        $short_name = $payload->get("short_name");
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