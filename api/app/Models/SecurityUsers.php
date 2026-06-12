<?php

namespace App\Models;

//use Illuminate\Database\Eloquent\Factories\HasFactory;
//use Illuminate\Database\Eloquent\Model;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\NumericId;
use App\Models\Concerns\UserActivityLog; //POCOR-9697: Wave-3 audit-log trait
use App\Models\Concerns\HidesSuperAdmins; //POCOR-9710: row-level super_admin visibility lock

class SecurityUsers extends Authenticatable implements JWTSubject
{
    use HasFactory;
    use Notifiable;
    use NumericId;
    use UserActivityLog; //POCOR-9697: log every create/update/delete to user_activities
    use HidesSuperAdmins; //POCOR-9710: super_admin = 1 rows invisible to non-super-admin callers

    public $timestamps = false;
    protected $casts = [
        'date_of_birth' => 'date:Y-m-d',
    ];
    protected $table = "security_users";

    protected $appends = ['full_name', 'name_with_id'];


    //POCOR-9697: super_admin removed from $fillable — only a dedicated, audited
    //promotion endpoint should ever set it. Direct DB updates remain possible
    //for migrations / seeders that use forceFill() or raw queries.
    protected $fillable = [
        'id', 'username', 'password',
        'openemis_no', 'first_name',
        'middle_name', 'third_name',
        'last_name', 'preferred_name', 'email',
        'mobile_number', 'address', 'postal_code',
        'address_area_id', 'birthplace_area',
        'gender_id', 'date_of_birth',
        'date_of_death', 'nationality_id',  'identity_type_id',
        'identity_number', 'external_reference',
        'status', 'last_login',
        'failed_logins', 'photo_name',
        'photo_content', 'preferred_language',
        'is_student', 'is_staff', 'is_guardian',
        'modified_user_id', 'modified',
        'created_user_id', 'created'
    ];
    //POCOR-9697: super_admin hidden from API responses — knowing who is a
    //super_admin is itself sensitive information (it tells an attacker which
    //accounts to phish).
    protected $hidden = [
        'password', 'remember_token', 'super_admin',
    ];

    protected $primaryKey = 'id';
    public $incrementing = false;

    protected static function boot(): void
    {
        parent::boot();
        self::bootNumericId();
    }








    //POCOR-9697: hash plaintext passwords on assignment. Bcrypt hashes
    //($2y$/$2a$/$2b$ with the standard 60-char length) pass through unchanged
    //so seeders, imports, and re-saves of an already-loaded row remain safe.
    public function setPasswordAttribute($value): void
    {
        if (is_string($value) && $value !== '' && !preg_match('/^\$2[aby]\$/', $value)) {
            $value = Hash::make($value);
        }
        $this->attributes['password'] = $value;
    }

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

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
        return $this->hasOne(InstitutionStudent::class, 'student_id', 'id')->orderBy('created', 'DESC');
    }

    public function nationality()
    {
        return $this->belongsTo(Nationalities::class, 'nationality_id', 'id');
    }


    public function identityType()
    {
        return $this->belongsTo(IdentityTypes::class, 'identity_type_id', 'id');
    }

    public function getFullName()
    {
        $nameParts = [
            $this->attributes['first_name'] ?? '',
            isset($this->attributes['middle_name']) ? $this->attributes['middle_name'] : '',
            isset($this->attributes['third_name']) ? $this->attributes['third_name'] : '',
            $this->attributes['last_name'] ?? '',
        ];

        // Filter out empty parts and join with a single space
        return implode(' ', array_filter($nameParts));
    }

    public function getFullNameAttribute()
    {
        $nameParts = [
            $this->attributes['first_name'] ?? '',
            isset($this->attributes['middle_name']) ? $this->attributes['middle_name'] : '',
            isset($this->attributes['third_name']) ? $this->attributes['third_name'] : '',
            $this->attributes['last_name'] ?? '',
        ];

        // Filter out empty parts and join with a single space
        return implode(' ', array_filter($nameParts));
    }


    public function getNameWithIdAttribute()
    {
        $nameParts = [
            $this->attributes['openemis_no'] ?? '',
            $this->attributes['first_name'] ?? '',
            isset($this->attributes['middle_name']) ? $this->attributes['middle_name'] : '',
            isset($this->attributes['third_name']) ? $this->attributes['third_name'] : '',
            $this->attributes['last_name'] ?? '',
        ];

        // Filter out empty parts and join with a single space
        return implode(' ', array_filter($nameParts));
    }


    public function specialNeed()
    {
        return $this->hasOne(UserSpecialNeedsAssessment::class, 'security_user_id', 'id');

    }

    public function institutionStaff()
    {
        return $this->belongsTo(InstitutionStaff::class, 'id', 'staff_id');

    }


    //For POCOR-8536 Start...
    public function institutionStudents()
    {
        return $this->hasMany(InstitutionStudent::class, 'student_id', 'id')->orderBy('created', 'DESC');
    }

    public function institutionStaffs()
    {
        return $this->hasMany(InstitutionStaff::class, 'staff_id', 'id');
    }
    //For POCOR-8536 End...


    //POCOR-8639
    public function userContacts()
    {
        return $this->hasMany(UserContacts::class, 'security_user_id'); // Use 'security_user_id' as the foreign key
    }

    // Scope to include gender details
    public function scopeWithGender($query)
    {
        return $query->with('gender:id,name');
    }

}
