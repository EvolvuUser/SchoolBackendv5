<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class LeavingCertificate extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $primaryKey = 'sr_no';
    protected $table ='leaving_certificate';
    protected $fillable = [
        'grn_no','issue_date','stud_id_no','aadhar_no','stud_name','mid_name','last_name','father_name','mother_name','nationality','mother_tongue','religion','caste','subcaste','birth_place','dob','dob_words','dob_proof','last_school_attended_standard','date_of_admission','admission_class','leaving_date','standard_studying','last_exam','subjects_studied','promoted_to','attendance','fee_month','part_of','games','application_date','conduct','reason_leaving','remark','stud_id','academic_yr','IsGenerated','IsDelete','IsIssued','generated_by','udise_pen_no','state'
    ];
}
