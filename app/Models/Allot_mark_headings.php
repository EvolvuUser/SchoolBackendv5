<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Allot_mark_headings extends Model
{
    use HasFactory;

    protected $table ='allot_mark_headings';
    protected $primaryKey = 'allot_markheadings_id'; 
    public $incrementing = true; 
    protected $fillable = ['allot_markheadings_id','class_id','exam_id','sm_id','marks_headings_id','academic_yr','highest_marks'];

    public function getClass()
    {
        return $this->belongsTo(Classes::class, 'class_id');  
    }    

    public function getSubject()
    {
        return $this->belongsTo(SubjectForReportCard::class, 'sm_id');  
    }  
     
    public function getExam()
    {
        return $this->belongsTo(Exams::class, 'exam_id');  
    }

    public function getMarksheading()
    {
        return $this->belongsTo(MarksHeadings::class, 'marks_headings_id');  
    }
}
