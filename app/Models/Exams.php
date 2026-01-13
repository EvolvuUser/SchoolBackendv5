<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Exams extends Model
{
    use HasFactory;

    protected $table = 'exam';
    protected $primaryKey = 'exam_id';
    public $incrementing = true;
    protected $fillable = ['exam_id', 'name', 'start_date', 'end_date', 'open_day', 'term_id', 'comment', 'academic_yr'];
}
