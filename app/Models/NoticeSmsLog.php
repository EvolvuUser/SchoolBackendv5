<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NoticeSmsLog extends Model
{
    use HasFactory;
    protected $table = 'notice_sms_log'; // Replace with your actual table name
    public $timestamps = false;
    protected $primaryKey = 'notice_id'; // Replace with your actual primary key
    protected $fillable = [
        'sms_status', 'stu_teacher_id', 'notice_id', 'phone_no',
        'sms_date'
    ];
}
