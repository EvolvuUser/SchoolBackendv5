<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ExamTimetable extends Model
{
    use HasFactory;
    protected $table = 'exam_timetable';
    public $timestamps = false;
    protected $fillable = ['description', 'exam_id', 'class_id', 'publish', 'academic_yr'];
}
