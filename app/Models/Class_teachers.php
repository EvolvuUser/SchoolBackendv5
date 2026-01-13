<?php

namespace App\Models;

use App\Models\Event;
use App\Models\Section;
use App\Models\Students;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Class_teachers extends Model
{
    protected $table = 'class_teachers';
    // protected $primaryKey = 'class_id';
    // public $incrementing = true;
    protected $fillable = ['class_id', 'section_id', 'teacher_id', 'academic_yr'];

    public function getClass()
    {
        return $this->belongsTo(Classes::class, 'class_id');
    }

    public function getDivision()
    {
        return $this->belongsTo(Division::class, 'section_id');
    }

    public function getTeacher()
    {
        return $this->belongsTo(Teacher::class, 'teacher_id');
    }

    public function division()
    {
        return $this->belongsTo(Section::class, 'section_id');
    }

    public function teacher()
    {
        return $this->belongsTo(Teacher::class, 'teacher_id');
    }
}
