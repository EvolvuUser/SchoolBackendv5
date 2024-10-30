<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CharacterCertificate extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $primaryKey = 'sr_no';
    protected $table ='character_certificate';
    protected $fillable = [
        'sr_no','stud_name','class_division','dob','dob_words','attempt','stud_id','issue_date_bonafide','academic_yr','IsGenerated','IsDeleted','IsIssued','generated_by'
    ];
}
