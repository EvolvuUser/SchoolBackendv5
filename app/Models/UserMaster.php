<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Tymon\JWTAuth\Contracts\JWTSubject;

class UserMaster extends Authenticatable implements JWTSubject
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $table = 'user_master';
    protected $primaryKey = 'user_id'; 
    public $incrementing = false; 
    protected $keyType = 'string'; 
    protected $fillable = ['user_id','name','password','reg_id','role_id','answer_one','answer_two','IsDelete'];

    public function getTeacher()
    {
        return $this->belongsTo(Teacher::class, 'reg_id');  
    }

    public function getJWTIdentifier()
    {
        return $this->getKey();  // Typically `id` of the user
    }

    /**
     * Get the custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return [];  // Add custom claims if necessary, or leave it empty
    }
}
