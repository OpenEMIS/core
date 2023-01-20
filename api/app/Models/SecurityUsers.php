<?php

namespace App\Models;

//use Illuminate\Database\Eloquent\Factories\HasFactory;
//use Illuminate\Database\Eloquent\Model;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class SecurityUsers extends Authenticatable implements JWTSubject
{
    //use HasFactory;
    use Notifiable;

    public $timestamps = false;
    protected $casts = [
        'date_of_birth' => 'date:Y-m-d',
    ];
    protected $table = "security_users";



    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return [];
    }



    public function nationalities()
    {
        return $this->hasMany(UserNationalities::class, 'security_user_id', 'id');
    }


    public function identities()
    {
        return $this->hasMany(UserIdentities::class, 'security_user_id', 'id');
    }


    public function gender()
    {
        return $this->belongsTo(Gender::class, 'gender_id', 'id');
    }

    public function institutionStudent()
    {
        return $this->hasOne(InstitutionStudent::class, 'student_id', 'id');
    }
}
