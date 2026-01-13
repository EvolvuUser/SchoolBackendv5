<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NoticeDetail extends Model
{
    use HasFactory;

    protected $table = 'notice_detail';  // Replace with your actual table name
    public $timestamps = false;

    protected $fillable = [
        'notice_id', 'image_name', 'file_size'
    ];
}
