<?php

namespace App\Models\Api5;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\InstitutionScope;

use App\Models\Concerns\WebhookQueueTrait;
class InstitutionSubjects extends Model
{
    use HasFactory;
    use InstitutionScope;


    // POCOR-9257: Configure webhook events
    use WebhookQueueTrait;
    protected $webhookEvents = ['created', 'updated', 'deleted'];

    // ✅ Allow mass assignment
    protected $fillable = ['id', 'name', 'no_of_seats', 'total_male_students', 'total_female_students', 'institution_id', 'education_grade_id', 'education_subject_id', 'academic_period_id', 'modified_user_id', 'modified', 'created_user_id', 'created', 'institution_id', 'education_grade_id', 'education_subject_id', 'academic_period_id', 'modified_user_id', 'created_user_id'];
    // ✅ Treat 'modified' and 'created' as timestamps
    protected $dates = ['modified', 'created'];

    public $timestamps = false;
    protected $table = "institution_subjects";


/**
 * @OA\PathItem(
 *     path="/api/v5/institution-subjects"
 * )
 */
public function _swaggerPath() {}

/**
 * @OA\Get(
 *     path="/api/v5/institution-subjects",
 *     summary="Get list of InstitutionSubjects",
 *     tags={"InstitutionSubjects"},
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
                          @OA\Property(property="no_of_seats", type="integer", example=null),
                          @OA\Property(property="total_male_students", type="integer", example=null),
                          @OA\Property(property="total_female_students", type="integer", example=null),
                          @OA\Property(property="institution_id", type="integer", example=null),
                          @OA\Property(property="education_grade_id", type="integer", example=null),
                          @OA\Property(property="education_subject_id", type="integer", example=null),
                          @OA\Property(property="academic_period_id", type="integer", example=null),
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
 *     path="/api/v5/institution-subjects",
 *     summary="Create a new InstitutionSubjects",
 *     tags={"InstitutionSubjects"},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             type="object",
                     @OA\Property(property="id", type="integer", example=null),
                     @OA\Property(property="name", type="string", example=null),
                     @OA\Property(property="no_of_seats", type="integer", example=null),
                     @OA\Property(property="total_male_students", type="integer", example=null),
                     @OA\Property(property="total_female_students", type="integer", example=null),
                     @OA\Property(property="institution_id", type="integer", example=null),
                     @OA\Property(property="education_grade_id", type="integer", example=null),
                     @OA\Property(property="education_subject_id", type="integer", example=null),
                     @OA\Property(property="academic_period_id", type="integer", example=null),
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
 *     path="/api/v5/institution-subjects/{id}",
 *     summary="Get InstitutionSubjects by ID",
 *     tags={"InstitutionSubjects"},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="ID of the InstitutionSubjects",
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
 *     path="/api/v5/institution-subjects/{id}",
 *     summary="Update InstitutionSubjects",
 *     tags={"InstitutionSubjects"},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="ID of the InstitutionSubjects",
 *         @OA\Schema(type="string")
 *     ),
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             type="object",
                     @OA\Property(property="id", type="integer", example=null),
                     @OA\Property(property="name", type="string", example=null),
                     @OA\Property(property="no_of_seats", type="integer", example=null),
                     @OA\Property(property="total_male_students", type="integer", example=null),
                     @OA\Property(property="total_female_students", type="integer", example=null),
                     @OA\Property(property="institution_id", type="integer", example=null),
                     @OA\Property(property="education_grade_id", type="integer", example=null),
                     @OA\Property(property="education_subject_id", type="integer", example=null),
                     @OA\Property(property="academic_period_id", type="integer", example=null),
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
 *     path="/api/v5/institution-subjects/{id}",
 *     summary="Delete InstitutionSubjects",
 *     tags={"InstitutionSubjects"},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="ID of the InstitutionSubjects",
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
    public function educationGrades()
    {
        return $this->belongsTo(EducationGrades::class, 'education_grade_id', 'id');
    }

    public function educationSubjects()
    {
        return $this->belongsTo(EducationSubjects::class, 'education_subject_id', 'id');
    }

    public function classes()
    {
        return $this->hasMany(InstitutionClassSubjects::class, 'institution_subject_id', 'id');
    }

    public function rooms()
    {
        return $this->hasMany(InstitutionSubjectRooms::class, 'institution_subject_id', 'id');
    }


    public function staff()
    {
        return $this->hasMany(InstitutionSubjectStaff::class, 'institution_subject_id', 'id');
    }


    public function students()
    {
        return $this->hasMany(InstitutionSubjectStudents::class, 'institution_subject_id', 'id');
    }


    public function academicPeriod()
    {
        return $this->belongsTo(AcademicPeriod::class, 'academic_period_id', 'id');
    }
}
