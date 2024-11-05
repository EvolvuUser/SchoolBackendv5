<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
class PercentageMarksCertificate extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $table ='percentage_marks_certificate';
    protected $fillable = [
        'sr_no','c_sm_id','marks'
    ];
}
