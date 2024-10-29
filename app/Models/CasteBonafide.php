<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CasteBonafide extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $primaryKey = 'sr_no';
    protected $table ='bonafide_caste_certificate';
    protected $fillable = [
        'sr_no','reg_no','stud_name','father_name','section_id','class_division','caste','religion','birth_place','dob','dob_words','stud_id_no','stu_aadhaar_no','admission_class_when','nationality','prev_school_class','admission_date','class_when_learning','progress','behaviour','leaving_reason','lc_date_n_no','subcaste','mother_tongue','stud_id','issue_date_bonafide','academic_yr','IsGenerated','IsDeleted','IsIssued','generated_by'
    ];
}
