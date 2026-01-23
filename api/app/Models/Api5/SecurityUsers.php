<?php

namespace App\Models\Api5;

//use Illuminate\Database\Eloquent\Factories\HasFactory;
//use Illuminate\Database\Eloquent\Model;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\NumericId;

class SecurityUsers extends Authenticatable implements JWTSubject
{
    use HasFactory;
    use Notifiable;
    use NumericId;

    public $timestamps = false;
    protected $casts = [
        'date_of_birth' => 'date:Y-m-d',
    ];
    protected $table = "security_users";

    protected $appends = ['full_name', 'name_with_id'];


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
        'super_admin', 'status', 'last_login',
        'failed_logins', 'photo_name',
        'photo_content', 'preferred_language',
        'is_student', 'is_staff', 'is_guardian',
        'modified_user_id', 'modified',
        'created_user_id', 'created'
    ];
    protected $hidden = [
        'password', 'remember_token',
    ];

    protected $primaryKey = 'id';
    public $incrementing = false;

    protected static function boot(): void
    {
        parent::boot();
        self::bootNumericId();
    }


/**
 * @OA\PathItem(
 *     path="/api/v5/security-users"
 * )
 */
public function _swaggerPath() {}

/**
 * @OA\Get(
 *     path="/api/v5/security-users",
 *     summary="Get list of SecurityUsers",
 *     tags={"SecurityUsers"},
 *     @OA\Parameter(
 *         name="limit",
 *         in="query",
 *         required=false,
 *         description="Maximum number of results to return",
 *         @OA\Schema(type="number")
 *     ),
 *     @OA\Parameter(
 *         name="page",
 *         in="query",
 *         required=false,
 *         description="Page number for paginated results",
 *         @OA\Schema(type="number")
 *     ),
 *     @OA\Parameter(
 *         name="orderby",
 *         in="query",
 *         required=false,
 *         description="Field to order results by",
 *         @OA\Schema(type="string")
 *     ),
 *     @OA\Parameter(
 *         name="order",
 *         in="query",
 *         required=false,
 *         description="Order direction: asc or desc",
 *         @OA\Schema(type="string", enum={"asc", "desc"})
 *     ),
 *     @OA\Parameter(
 *         name="_fields",
 *         in="query",
 *         required=false,
 *         description="Comma-separated list of fields to include in response",
 *         @OA\Schema(type="string")
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Successful operation",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(
 *                 property="message",
 *                 type="string",
 *                 example="Successful."
 *             ),
 *             @OA\Property(
 *                 property="data",
 *                 type="array",
 *                 @OA\Items(
 *                     type="object",
                          @OA\Property(property="id", type="integer", example=null),
                          @OA\Property(property="username", type="string", example=null),
                          @OA\Property(property="password", type="string", example=null),
                          @OA\Property(property="openemis_no", type="string", example=null),
                          @OA\Property(property="first_name", type="string", example=null),
                          @OA\Property(property="middle_name", type="string", example=null),
                          @OA\Property(property="third_name", type="string", example=null),
                          @OA\Property(property="last_name", type="string", example=null),
                          @OA\Property(property="preferred_name", type="string", example=null),
                          @OA\Property(property="email", type="string", example=null),
                          @OA\Property(property="mobile_number", type="string", example=null),
                          @OA\Property(property="address", type="string", example=null),
                          @OA\Property(property="postal_code", type="string", example=null),
                          @OA\Property(property="address_area_id", type="integer", example=null),
                          @OA\Property(property="birthplace_area_id", type="integer", example=null),
                          @OA\Property(property="gender_id", type="integer", example=null),
                          @OA\Property(property="date_of_birth", type="string", format="date", example=null),
                          @OA\Property(property="date_of_death", type="string", format="date", example=null),
                          @OA\Property(property="nationality_id", type="integer", example=null),
                          @OA\Property(property="identity_type_id", type="integer", example=null),
                          @OA\Property(property="identity_number", type="string", example=null),
                          @OA\Property(property="external_reference", type="string", example=null),
                          @OA\Property(property="super_admin", type="integer", example=null),
                          @OA\Property(property="status", type="integer", example=null),
                          @OA\Property(property="last_login", type="string", format="date-time", example=null),
                          @OA\Property(property="failed_logins", type="integer", example=null),
                          @OA\Property(property="photo_name", type="string", example=null),
                          @OA\Property(property="photo_content", type="string", example=null),
                          @OA\Property(property="preferred_language", type="string", example=null),
                          @OA\Property(property="is_student", type="integer", example=null),
                          @OA\Property(property="is_staff", type="integer", example=null),
                          @OA\Property(property="is_guardian", type="integer", example=null),
                          @OA\Property(property="modified_user_id", type="integer", example=null),
                          @OA\Property(property="modified", type="string", format="date-time", example=null),
                          @OA\Property(property="created_user_id", type="integer", example=null),
                          @OA\Property(property="created", type="string", format="date-time", example=null)
 *                 )
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=401,
 *         description="Unauthorized"
 *     )
 * )
 */
public function _swaggerList() {}

/**
 * @OA\Post(
 *     path="/api/v5/security-users",
 *     summary="Create a new SecurityUsers",
 *     tags={"SecurityUsers"},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             type="object",
                     @OA\Property(property="id", type="integer", example=null),
                     @OA\Property(property="username", type="string", example=null),
                     @OA\Property(property="password", type="string", example=null),
                     @OA\Property(property="openemis_no", type="string", example=null),
                     @OA\Property(property="first_name", type="string", example=null),
                     @OA\Property(property="middle_name", type="string", example=null),
                     @OA\Property(property="third_name", type="string", example=null),
                     @OA\Property(property="last_name", type="string", example=null),
                     @OA\Property(property="preferred_name", type="string", example=null),
                     @OA\Property(property="email", type="string", example=null),
                     @OA\Property(property="mobile_number", type="string", example=null),
                     @OA\Property(property="address", type="string", example=null),
                     @OA\Property(property="postal_code", type="string", example=null),
                     @OA\Property(property="address_area_id", type="integer", example=null),
                     @OA\Property(property="birthplace_area_id", type="integer", example=null),
                     @OA\Property(property="gender_id", type="integer", example=null),
                     @OA\Property(property="date_of_birth", type="string", format="date", example=null),
                     @OA\Property(property="date_of_death", type="string", format="date", example=null),
                     @OA\Property(property="nationality_id", type="integer", example=null),
                     @OA\Property(property="identity_type_id", type="integer", example=null),
                     @OA\Property(property="identity_number", type="string", example=null),
                     @OA\Property(property="external_reference", type="string", example=null),
                     @OA\Property(property="super_admin", type="integer", example=null),
                     @OA\Property(property="status", type="integer", example=null),
                     @OA\Property(property="last_login", type="string", format="date-time", example=null),
                     @OA\Property(property="failed_logins", type="integer", example=null),
                     @OA\Property(property="photo_name", type="string", example=null),
                     @OA\Property(property="photo_content", type="string", example=null),
                     @OA\Property(property="preferred_language", type="string", example=null),
                     @OA\Property(property="is_student", type="integer", example=null),
                     @OA\Property(property="is_staff", type="integer", example=null),
                     @OA\Property(property="is_guardian", type="integer", example=null),
                     @OA\Property(property="modified_user_id", type="integer", example=null),
                     @OA\Property(property="modified", type="string", format="date-time", example=null),
                     @OA\Property(property="created_user_id", type="integer", example=null),
                     @OA\Property(property="created", type="string", format="date-time", example=null)
 *         )
 *     ),
 *     @OA\Response(
 *         response=201,
 *         description="Created successfully"
 *     ),
 *     @OA\Response(
 *         response=400,
 *         description="Invalid data"
 *     ),
 *     @OA\Response(
 *         response=401,
 *         description="Unauthorized"
 *     )
 * )
 */
public function _swaggerCreate() {}


/**
 * @OA\Get(
 *     path="/api/v5/security-users/{id}",
 *     summary="Get SecurityUsers by ID",
 *     tags={"SecurityUsers"},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="ID of the SecurityUsers",
 *         @OA\Schema(type="string")
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Successful operation"
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Not found"
 *     )
 * )
 */
public function _swaggerView() {}

/**
 * @OA\Put(
 *     path="/api/v5/security-users/{id}",
 *     summary="Update SecurityUsers",
 *     tags={"SecurityUsers"},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="ID of the SecurityUsers",
 *         @OA\Schema(type="string")
 *     ),
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             type="object",
                     @OA\Property(property="id", type="integer", example=null),
                     @OA\Property(property="username", type="string", example=null),
                     @OA\Property(property="password", type="string", example=null),
                     @OA\Property(property="openemis_no", type="string", example=null),
                     @OA\Property(property="first_name", type="string", example=null),
                     @OA\Property(property="middle_name", type="string", example=null),
                     @OA\Property(property="third_name", type="string", example=null),
                     @OA\Property(property="last_name", type="string", example=null),
                     @OA\Property(property="preferred_name", type="string", example=null),
                     @OA\Property(property="email", type="string", example=null),
                     @OA\Property(property="mobile_number", type="string", example=null),
                     @OA\Property(property="address", type="string", example=null),
                     @OA\Property(property="postal_code", type="string", example=null),
                     @OA\Property(property="address_area_id", type="integer", example=null),
                     @OA\Property(property="birthplace_area_id", type="integer", example=null),
                     @OA\Property(property="gender_id", type="integer", example=null),
                     @OA\Property(property="date_of_birth", type="string", format="date", example=null),
                     @OA\Property(property="date_of_death", type="string", format="date", example=null),
                     @OA\Property(property="nationality_id", type="integer", example=null),
                     @OA\Property(property="identity_type_id", type="integer", example=null),
                     @OA\Property(property="identity_number", type="string", example=null),
                     @OA\Property(property="external_reference", type="string", example=null),
                     @OA\Property(property="super_admin", type="integer", example=null),
                     @OA\Property(property="status", type="integer", example=null),
                     @OA\Property(property="last_login", type="string", format="date-time", example=null),
                     @OA\Property(property="failed_logins", type="integer", example=null),
                     @OA\Property(property="photo_name", type="string", example=null),
                     @OA\Property(property="photo_content", type="string", example=null),
                     @OA\Property(property="preferred_language", type="string", example=null),
                     @OA\Property(property="is_student", type="integer", example=null),
                     @OA\Property(property="is_staff", type="integer", example=null),
                     @OA\Property(property="is_guardian", type="integer", example=null),
                     @OA\Property(property="modified_user_id", type="integer", example=null),
                     @OA\Property(property="modified", type="string", format="date-time", example=null),
                     @OA\Property(property="created_user_id", type="integer", example=null),
                     @OA\Property(property="created", type="string", format="date-time", example=null)
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Updated successfully"
 *     ),
 *     @OA\Response(
 *         response=400,
 *         description="Invalid data"
 *     ),
 *     @OA\Response(
 *         response=401,
 *         description="Unauthorized"
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Not found"
 *     )
 * )
 */
public function _swaggerUpdate() {}

/**
 * @OA\Delete(
 *     path="/api/v5/security-users/{id}",
 *     summary="Delete SecurityUsers",
 *     tags={"SecurityUsers"},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="ID of the SecurityUsers",
 *         @OA\Schema(type="string")
 *     ),
 *     @OA\Response(
 *         response=204,
 *         description="Deleted successfully"
 *     ),
 *     @OA\Response(
 *         response=401,
 *         description="Unauthorized"
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Not found"
 *     )
 * )
 */
public function _swaggerDelete() {}
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
