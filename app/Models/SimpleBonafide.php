<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SimpleBonafide extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $primaryKey = 'sr_no';
    protected $table ='simple_bonafide_certificate';
    protected $fillable = [
        'sr_no','stud_name','father_name','class_division','dob','dob_words','stud_id','issue_date_bonafide','academic_yr','IsGenerated','IsDeleted','IsIssued','generated_by'
    ];
}
