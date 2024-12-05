<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubstituteTeacher extends Model
{
    protected $table = 'substitute_teacher';
    protected $fillable =['class_id','section_id','subject_id','period','date','teacher_id','sub_teacher_id','academic_yr'];
}
