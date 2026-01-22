<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notice extends Model
{
    use HasFactory;

    protected $table = 'notice';  // Replace with your actual table name
    public $timestamps = false;
    protected $primaryKey = 'notice_id';  // Replace with your actual primary key

    protected $fillable = [
        'subject', 'notice_desc', 'teacher_id', 'notice_type',
        'academic_yr', 'publish', 'unq_id', 'notice_date', 'class_id'
    ];

    public function classes()
    {
        return $this->belongsTo(ClassModel::class, 'class_id', 'class_id');
    }
}
