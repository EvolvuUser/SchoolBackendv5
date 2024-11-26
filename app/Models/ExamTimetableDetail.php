<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExamTimetableDetail extends Model
{
    use HasFactory;
    protected $table = 'exam_timetable_details';
    public $timestamps = false;
    protected $fillable = ['exam_tt_id','date','subject_rc_id','study_leave'];
}
