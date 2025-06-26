<?php

namespace App\Models\Api5;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\InstitutionScope;
use App\Services\PermissionService;
use Illuminate\Database\Eloquent\Builder;
use Tymon\JWTAuth\Facades\JWTAuth;

class Institutions extends Model
{
    use HasFactory;
use InstitutionScope;
    // ✅ Allow mass assignment
    protected $fillable = ['id', 'name', 'alternative_name', 'code', 'address', 'postal_code', 'contact_person', 'telephone', 'fax', 'email', 'website', 'date_opened', 'year_opened', 'date_closed', 'year_closed', 'longitude', 'latitude', 'logo_name', 'logo_content', 'shift_type', 'classification', 'area_id', 'area_administrative_id', 'institution_locality_id', 'institution_type_id', 'institution_ownership_id', 'institution_status_id', 'institution_sector_id', 'institution_provider_id', 'institution_gender_id', 'security_group_id', 'modified_user_id', 'modified', 'created_user_id', 'created', 'area_id', 'area_administrative_id', 'institution_locality_id', 'institution_type_id', 'institution_ownership_id', 'institution_status_id', 'institution_sector_id', 'institution_provider_id', 'institution_gender_id', 'security_group_id', 'modified_user_id', 'created_user_id'];
    // ✅ Treat 'modified' and 'created' as timestamps
    protected $dates = ['modified', 'created'];

    public $timestamps = false;
    protected $table = "institutions";

    protected $appends = ['code_name'];


/**
 * @OA\PathItem(
 *     path="/api/v5/institutions"
 * )
 */
public function _swaggerPath() {}

/**
 * @OA\Get(
 *     path="/api/v5/institutions",
 *     summary="Get list of Institutions",
 *     tags={"Institutions"},
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
                          @OA\Property(property="name", type="string", example=null),
                          @OA\Property(property="alternative_name", type="string", example=null),
                          @OA\Property(property="code", type="string", example=null),
                          @OA\Property(property="address", type="string", example=null),
                          @OA\Property(property="postal_code", type="string", example=null),
                          @OA\Property(property="contact_person", type="string", example=null),
                          @OA\Property(property="telephone", type="string", example=null),
                          @OA\Property(property="fax", type="string", example=null),
                          @OA\Property(property="email", type="string", example=null),
                          @OA\Property(property="website", type="string", example=null),
                          @OA\Property(property="date_opened", type="string", format="date", example=null),
                          @OA\Property(property="year_opened", type="integer", example=null),
                          @OA\Property(property="date_closed", type="string", format="date", example=null),
                          @OA\Property(property="year_closed", type="integer", example=null),
                          @OA\Property(property="longitude", type="string", example=null),
                          @OA\Property(property="latitude", type="string", example=null),
                          @OA\Property(property="logo_name", type="string", example=null),
                          @OA\Property(property="logo_content", type="string", example=null),
                          @OA\Property(property="shift_type", type="integer", example=null),
                          @OA\Property(property="classification", type="integer", example=null),
                          @OA\Property(property="area_id", type="integer", example=null),
                          @OA\Property(property="area_administrative_id", type="integer", example=null),
                          @OA\Property(property="institution_locality_id", type="integer", example=null),
                          @OA\Property(property="institution_type_id", type="integer", example=null),
                          @OA\Property(property="institution_ownership_id", type="integer", example=null),
                          @OA\Property(property="institution_status_id", type="integer", example=null),
                          @OA\Property(property="institution_sector_id", type="integer", example=null),
                          @OA\Property(property="institution_provider_id", type="integer", example=null),
                          @OA\Property(property="institution_gender_id", type="integer", example=null),
                          @OA\Property(property="security_group_id", type="integer", example=null),
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
 *     path="/api/v5/institutions",
 *     summary="Create a new Institutions",
 *     tags={"Institutions"},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             type="object",
                     @OA\Property(property="id", type="integer", example=null),
                     @OA\Property(property="name", type="string", example=null),
                     @OA\Property(property="alternative_name", type="string", example=null),
                     @OA\Property(property="code", type="string", example=null),
                     @OA\Property(property="address", type="string", example=null),
                     @OA\Property(property="postal_code", type="string", example=null),
                     @OA\Property(property="contact_person", type="string", example=null),
                     @OA\Property(property="telephone", type="string", example=null),
                     @OA\Property(property="fax", type="string", example=null),
                     @OA\Property(property="email", type="string", example=null),
                     @OA\Property(property="website", type="string", example=null),
                     @OA\Property(property="date_opened", type="string", format="date", example=null),
                     @OA\Property(property="year_opened", type="integer", example=null),
                     @OA\Property(property="date_closed", type="string", format="date", example=null),
                     @OA\Property(property="year_closed", type="integer", example=null),
                     @OA\Property(property="longitude", type="string", example=null),
                     @OA\Property(property="latitude", type="string", example=null),
                     @OA\Property(property="logo_name", type="string", example=null),
                     @OA\Property(property="logo_content", type="string", example=null),
                     @OA\Property(property="shift_type", type="integer", example=null),
                     @OA\Property(property="classification", type="integer", example=null),
                     @OA\Property(property="area_id", type="integer", example=null),
                     @OA\Property(property="area_administrative_id", type="integer", example=null),
                     @OA\Property(property="institution_locality_id", type="integer", example=null),
                     @OA\Property(property="institution_type_id", type="integer", example=null),
                     @OA\Property(property="institution_ownership_id", type="integer", example=null),
                     @OA\Property(property="institution_status_id", type="integer", example=null),
                     @OA\Property(property="institution_sector_id", type="integer", example=null),
                     @OA\Property(property="institution_provider_id", type="integer", example=null),
                     @OA\Property(property="institution_gender_id", type="integer", example=null),
                     @OA\Property(property="security_group_id", type="integer", example=null),
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
 *     path="/api/v5/institutions/{id}",
 *     summary="Get Institutions by ID",
 *     tags={"Institutions"},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="ID of the Institutions",
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
 *     path="/api/v5/institutions/{id}",
 *     summary="Update Institutions",
 *     tags={"Institutions"},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="ID of the Institutions",
 *         @OA\Schema(type="string")
 *     ),
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             type="object",
                     @OA\Property(property="id", type="integer", example=null),
                     @OA\Property(property="name", type="string", example=null),
                     @OA\Property(property="alternative_name", type="string", example=null),
                     @OA\Property(property="code", type="string", example=null),
                     @OA\Property(property="address", type="string", example=null),
                     @OA\Property(property="postal_code", type="string", example=null),
                     @OA\Property(property="contact_person", type="string", example=null),
                     @OA\Property(property="telephone", type="string", example=null),
                     @OA\Property(property="fax", type="string", example=null),
                     @OA\Property(property="email", type="string", example=null),
                     @OA\Property(property="website", type="string", example=null),
                     @OA\Property(property="date_opened", type="string", format="date", example=null),
                     @OA\Property(property="year_opened", type="integer", example=null),
                     @OA\Property(property="date_closed", type="string", format="date", example=null),
                     @OA\Property(property="year_closed", type="integer", example=null),
                     @OA\Property(property="longitude", type="string", example=null),
                     @OA\Property(property="latitude", type="string", example=null),
                     @OA\Property(property="logo_name", type="string", example=null),
                     @OA\Property(property="logo_content", type="string", example=null),
                     @OA\Property(property="shift_type", type="integer", example=null),
                     @OA\Property(property="classification", type="integer", example=null),
                     @OA\Property(property="area_id", type="integer", example=null),
                     @OA\Property(property="area_administrative_id", type="integer", example=null),
                     @OA\Property(property="institution_locality_id", type="integer", example=null),
                     @OA\Property(property="institution_type_id", type="integer", example=null),
                     @OA\Property(property="institution_ownership_id", type="integer", example=null),
                     @OA\Property(property="institution_status_id", type="integer", example=null),
                     @OA\Property(property="institution_sector_id", type="integer", example=null),
                     @OA\Property(property="institution_provider_id", type="integer", example=null),
                     @OA\Property(property="institution_gender_id", type="integer", example=null),
                     @OA\Property(property="security_group_id", type="integer", example=null),
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
 *     path="/api/v5/institutions/{id}",
 *     summary="Delete Institutions",
 *     tags={"Institutions"},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="ID of the Institutions",
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
    public function areaAdministratives()
    {
        return $this->belongsTo(AreaAdministratives::class, 'area_administrative_id', 'id');
    }


    public function areaEducation()
    {
        return $this->belongsTo(Areas::class, 'area_id', 'id');
    }


    public function educationGrades()
    {
        return $this->belongsToMany(EducationGrades::class, 'institution_grades', 'institution_id', 'education_grade_id');
    }


    public function institutionLocalities()
    {
        return $this->belongsTo(InstitutionLocalities::class, 'institution_locality_id', 'id');
    }


    public function institutionOwnerships()
    {
        return $this->belongsTo(InstitutionOwnerships::class, 'institution_ownership_id', 'id');
    }


    public function institutionProviders()
    {
        return $this->belongsTo(InstitutionProviders::class, 'institution_provider_id', 'id');
    }


    public function institutionSectors()
    {
        return $this->belongsTo(InstitutionSectors::class, 'institution_sector_id', 'id');
    }


    public function institutionTypes()
    {
        return $this->belongsTo(InstitutionTypes::class, 'institution_type_id', 'id');
    }


    public function institutionStatus()
    {
        return $this->belongsTo(InstitutionStatus::class, 'institution_status_id', 'id');
    }


    public function institutionGender()
    {
        return $this->belongsTo(InstitutionGender::class, 'institution_gender_id', 'id');
    }


    public function getCodeNameAttribute()
    {
        if(isset($this->attributes['code']) && isset($this->attributes['name'])) {
            return $this->attributes['code']. ' - ' .$this->attributes['name'];
        }
        if(isset($this->attributes['code']) && !isset($this->attributes['name'])) {
            return $this->attributes['code'];
        }
        if(isset($this->attributes['name']) && !isset($this->attributes['code'])) {
            return $this->attributes['name'];
        }
        return 'not selected';
    }

    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope('userInstitutionAccess', function (Builder $query) {
            $user = JWTAuth::user();

            if (!$user) {
                return;
            }

            // Check if user is a super admin (no restrictions)
            if ($user->super_admin ?? 0) {
                return;
            }

            $permissionService = app(PermissionService::class);
            $allowedInstitutions = $permissionService->getInstitutionIds();

            // Apply institution filter only if the user does not have access to all institutions
            if (!$permissionService->getAllowAllInstitutions()) {
                $query->whereIn('institutions.id', $allowedInstitutions);
            }
        });
    }
}
