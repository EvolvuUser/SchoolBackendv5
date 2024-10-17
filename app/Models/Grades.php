<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Grades extends Model
{
    use HasFactory;

    protected $table ='grade';
    protected $primaryKey = 'grade_id'; 
    public $incrementing = true; 
    protected $fillable = ['grade_id','class_id','subject_type','name','mark_from','mark_upto','comment','academic_yr'];
}
