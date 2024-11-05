<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
class PercentageCertificate extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $primaryKey = 'sr_no';
    protected $table ='percentage_certificate';
    protected $fillable = [
        'sr_no','stud_name','roll_no','percentage','total','class_division','certi_issue_date','stud_id','academic_yr','IsGenerated','IsDeleted','IsIssued','generated_by'
    ];
}
