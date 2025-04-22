<?php

namespace App\Models\Api5;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\InstitutionScope;
use App\Traits\UuidId;

class InstitutionSubjectStudents extends Model
{
    use UuidId;
    use HasFactory;
    use InstitutionScope;

    // ✅ Allow mass assignment
    public $timestamps = false;
    // ✅ Treat 'modified' and 'created' as timestamps
    public $incrementing = false;
    protected $fillable = ['id',
        'total_mark',
        'outcome_result',
        'student_id',
        'institution_subject_id',
        'institution_class_id',
        'institution_id',
        'academic_period_id',
        'education_subject_id',
        'education_grade_id',
        'student_status_id',
        'modified_user_id',
        'modified',
        'created_user_id',
        'created'];
    protected $dates = ['modified', 'created'];
    protected $table = "institution_subject_students";
    protected $casts = [
        'id' => 'string',
    ];
    protected $primaryKey = [
        'student_id',
        'institution_class_id',
        'institution_id',
        'academic_period_id',
        'education_subject_id',
        'education_grade_id'];

    public static function getValidationRules(): array
    {
        return [
            // Add validation rules here
        ];
    }

    protected static function boot()
    {
        parent::boot();
        self::bootUuidId();
    }

    /**
     * @OA\PathItem(
     *     path="/api/v5/institution-subject-students"
     * )
     */
    public function _swaggerPath()
    {
    }

    /**
     * @OA\Get(
     *     path="/api/v5/institution-subject-students",
     *     summary="Get list of InstitutionSubjectStudents",
     *     tags={"InstitutionSubjectStudents"},
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
    @OA\Property(property="id", type="string", example=null),
    @OA\Property(property="total_mark", type="number", example=null),
    @OA\Property(property="outcome_result", type="string", example=null),
    @OA\Property(property="student_id", type="integer", example=null),
    @OA\Property(property="institution_subject_id", type="integer", example=null),
    @OA\Property(property="institution_class_id", type="integer", example=null),
    @OA\Property(property="institution_id", type="integer", example=null),
    @OA\Property(property="academic_period_id", type="integer", example=null),
    @OA\Property(property="education_subject_id", type="integer", example=null),
    @OA\Property(property="education_grade_id", type="integer", example=null),
    @OA\Property(property="student_status_id", type="integer", example=null),
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
    public function _swaggerList()
    {
    }

    /**
     * @OA\Post(
     *     path="/api/v5/institution-subject-students",
     *     summary="Create a new InstitutionSubjectStudents",
     *     tags={"InstitutionSubjectStudents"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
    @OA\Property(property="id", type="string", example=null),
    @OA\Property(property="total_mark", type="number", example=null),
    @OA\Property(property="outcome_result", type="string", example=null),
    @OA\Property(property="student_id", type="integer", example=null),
    @OA\Property(property="institution_subject_id", type="integer", example=null),
    @OA\Property(property="institution_class_id", type="integer", example=null),
    @OA\Property(property="institution_id", type="integer", example=null),
    @OA\Property(property="academic_period_id", type="integer", example=null),
    @OA\Property(property="education_subject_id", type="integer", example=null),
    @OA\Property(property="education_grade_id", type="integer", example=null),
    @OA\Property(property="student_status_id", type="integer", example=null),
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
    public function _swaggerCreate()
    {
    }

    /**
     * @OA\Get(
     *     path="/api/v5/institution-subject-students/student_id/{student_id}/institution_class_id/{institution_class_id}/institution_id/{institution_id}/academic_period_id/{academic_period_id}/education_subject_id/{education_subject_id}/education_grade_id/{education_grade_id}",
     *     summary="Get InstitutionSubjectStudents record by composite key",
     *     tags={"InstitutionSubjectStudents"},
     *     @OA\Parameter(
     *         name="student_id",
     *         in="path",
     *         required=true,
     *         description="student_id",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="institution_class_id",
     *         in="path",
     *         required=true,
     *         description="institution_class_id",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="institution_id",
     *         in="path",
     *         required=true,
     *         description="institution_id",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="academic_period_id",
     *         in="path",
     *         required=true,
     *         description="academic_period_id",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="education_subject_id",
     *         in="path",
     *         required=true,
     *         description="education_subject_id",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="education_grade_id",
     *         in="path",
     *         required=true,
     *         description="education_grade_id",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Record found"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Record not found"
     *     )
     * )
     */
    public function _swaggerView()
    {
    }

    /**
     * @OA\Put(
     *     path="/api/v5/institution-subject-students/student_id/{student_id}/institution_class_id/{institution_class_id}/institution_id/{institution_id}/academic_period_id/{academic_period_id}/education_subject_id/{education_subject_id}/education_grade_id/{education_grade_id}",
     *     summary="Update InstitutionSubjectStudents record by composite key",
     *     tags={"InstitutionSubjectStudents"},
     *     @OA\Parameter(
     *         name="student_id",
     *         in="path",
     *         required=true,
     *         description="student_id",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="institution_class_id",
     *         in="path",
     *         required=true,
     *         description="institution_class_id",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="institution_id",
     *         in="path",
     *         required=true,
     *         description="institution_id",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="academic_period_id",
     *         in="path",
     *         required=true,
     *         description="academic_period_id",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="education_subject_id",
     *         in="path",
     *         required=true,
     *         description="education_subject_id",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="education_grade_id",
     *         in="path",
     *         required=true,
     *         description="education_grade_id",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *              *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Record updated successfully"
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Invalid data provided"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Record not found"
     *     )
     * )
     */
    public function _swaggerUpdate()
    {
    }

    /**
     * @OA\Delete(
     *     path="/api/v5/institution-subject-students/student_id/{student_id}/institution_class_id/{institution_class_id}/institution_id/{institution_id}/academic_period_id/{academic_period_id}/education_subject_id/{education_subject_id}/education_grade_id/{education_grade_id}",
     *     summary="Delete InstitutionSubjectStudents record by composite key",
     *     tags={"InstitutionSubjectStudents"},
     *     @OA\Parameter(
     *         name="student_id",
     *         in="path",
     *         required=true,
     *         description="student_id",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="institution_class_id",
     *         in="path",
     *         required=true,
     *         description="institution_class_id",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="institution_id",
     *         in="path",
     *         required=true,
     *         description="institution_id",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="academic_period_id",
     *         in="path",
     *         required=true,
     *         description="academic_period_id",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="education_subject_id",
     *         in="path",
     *         required=true,
     *         description="education_subject_id",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="education_grade_id",
     *         in="path",
     *         required=true,
     *         description="education_grade_id",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=204,
     *         description="Record deleted successfully"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Record not found"
     *     )
     * )
     */
    public function _swaggerDelete()
    {
    }

    public function securityUser()
    {
        return $this->belongsTo(SecurityUsers::class, 'student_id', 'id');
    }

    public function studentStatuses()
    {
        return $this->belongsTo(StudentStatuses::class, 'student_status_id', 'id');
    }

    public function educationGrades()
    {
        return $this->belongsTo(EducationGrades::class, 'education_grade_id', 'id');
    }

    public function class()
    {
        return $this->belongsTo(InstitutionClasses::class, 'institution_class_id', 'id');
    }

    // Override setKeysForSaveQuery to handle composite keys

    protected function getKeyForSaveQuery()
    {
        $query = $this->newQueryWithoutScopes();
        $keyName = $this->getKeyName();
        if (!is_array($keyName)) {
            $keyName = [$keyName];;
        }
        foreach ($keyName as $key) {
            $query->where($key, '=', $this->getAttribute($key));
        }

        return $query;
    }

    protected function setKeysForSaveQuery($query)
    {
        $keyName = $this->getKeyName();
        if (!is_array($keyName)) {
            $keyName = [$keyName];;
        }
        foreach ($keyName as $key) {
            $query->where($key, '=', $this->getAttribute($key));
        }

        return $query;
    }
}
