<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class BonafideCertificate extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $primaryKey = 'sr_no';
    protected $table ='bonafide_certificate';
    protected $fillable = [
        'sr_no','stud_name','father_name','class_division','dob','dob_words','purpose','stud_id','nationality','issue_date_bonafide','academic_yr','IsGenerated','IsDeleted','IsIssued','generated_by','mother_name','subcaste','state','permant_add','reg_no','state','caste','religion','birth_place'
    ];
}
