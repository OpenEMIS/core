<?php

namespace App\Models\Api5;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\InstitutionScope;
use App\Services\AlertTriggerService;
use Illuminate\Support\Facades\Log;

class InstitutionStudentAdmission extends Model
{
    use HasFactory;
    use InstitutionScope;
    // ✅ Allow mass assignment
    protected $fillable = ['id', 'start_date', 'end_date', 'student_id', 'status_id', 'assignee_id', 'institution_id', 'academic_period_id', 'education_grade_id', 'institution_class_id', 'test_score', 'interview_score', 'comment', 'modified_user_id', 'modified', 'created_user_id', 'created', 'student_id', 'status_id', 'assignee_id', 'institution_id', 'academic_period_id', 'education_grade_id', 'institution_class_id', 'modified_user_id', 'created_user_id'];
    // ✅ Treat 'modified' and 'created' as timestamps
    protected $dates = ['modified', 'created'];

    public $timestamps = false;
    protected $table = "institution_student_admission";

    /**
     * POCOR-9509: Trigger admission alerts when the record is created or the workflow status changes.
     */
    protected static function boot()
    {
        parent::boot();

        static::saved(function (self $admission) {
            if (!$admission->wasRecentlyCreated && !$admission->wasChanged('status_id')) {
                return;
            }

            if (!$admission->student_id || !$admission->institution_id || !$admission->status_id) {
                // POCOR-9509: Keep production resilient when required event data is missing.
                return;
            }

            try {
                $admission->processAdmissionAlert();
            } catch (\Throwable $e) {
                Log::error('[POCOR-9509] Admission alert processing failed in saved event', [
                    'admission_id' => $admission->id,
                    'exception' => $e->getMessage(),
                ]);
            }
        });
    }

    /**
     * POCOR-9509: Dispatch the Laravel alert command for admission events.
     */
    protected function processAdmissionAlert(): bool
    {
        $alertRule = AlertTriggerService::getActiveAlertRule('StudentAdmission', (int) $this->institution_id);

        if (!$alertRule) {
            return false;
        }

        $result = AlertTriggerService::triggerAlert(
            processName: 'AlertStudentAdmission',
            featureName: 'StudentAdmission',
            userId: (int) ($this->created_user_id ?: 1),
            ruleId: (int) $alertRule->id,
            entityId: (int) $this->id,
            context: [
                'student_id' => (int) $this->student_id,
                'status_id' => (int) $this->status_id,
                'academic_period_id' => (int) $this->academic_period_id,
                'institution_id' => (int) $this->institution_id,
                'change_date' => $this->modified ?? $this->created,
            ],
            entityType: 'StudentAdmission',
            triggerType: 'status_changed'
        );

        return (bool) ($result['success'] ?? false) || (bool) ($result['duplicate'] ?? false);
    }


/**
 * @OA\PathItem(
 *     path="/api/v5/institution-student-admission"
 * )
 */
public function _swaggerPath() {}

/**
 * @OA\Get(
 *     path="/api/v5/institution-student-admission",
 *     summary="Get list of InstitutionStudentAdmission",
 *     tags={"InstitutionStudentAdmission"},
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
                          @OA\Property(property="start_date", type="string", format="date", example=null),
                          @OA\Property(property="end_date", type="string", format="date", example=null),
                          @OA\Property(property="student_id", type="integer", example=null),
                          @OA\Property(property="status_id", type="integer", example=null),
                          @OA\Property(property="assignee_id", type="integer", example=null),
                          @OA\Property(property="institution_id", type="integer", example=null),
                          @OA\Property(property="academic_period_id", type="integer", example=null),
                          @OA\Property(property="education_grade_id", type="integer", example=null),
                          @OA\Property(property="institution_class_id", type="integer", example=null),
                          @OA\Property(property="test_score", type="integer", example=null),
                          @OA\Property(property="interview_score", type="integer", example=null),
                          @OA\Property(property="comment", type="string", example=null),
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
 *     path="/api/v5/institution-student-admission",
 *     summary="Create a new InstitutionStudentAdmission",
 *     tags={"InstitutionStudentAdmission"},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             type="object",
                     @OA\Property(property="id", type="integer", example=null),
                     @OA\Property(property="start_date", type="string", format="date", example=null),
                     @OA\Property(property="end_date", type="string", format="date", example=null),
                     @OA\Property(property="student_id", type="integer", example=null),
                     @OA\Property(property="status_id", type="integer", example=null),
                     @OA\Property(property="assignee_id", type="integer", example=null),
                     @OA\Property(property="institution_id", type="integer", example=null),
                     @OA\Property(property="academic_period_id", type="integer", example=null),
                     @OA\Property(property="education_grade_id", type="integer", example=null),
                     @OA\Property(property="institution_class_id", type="integer", example=null),
                     @OA\Property(property="test_score", type="integer", example=null),
                     @OA\Property(property="interview_score", type="integer", example=null),
                     @OA\Property(property="comment", type="string", example=null),
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
 *     path="/api/v5/institution-student-admission/{id}",
 *     summary="Get InstitutionStudentAdmission by ID",
 *     tags={"InstitutionStudentAdmission"},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="ID of the InstitutionStudentAdmission",
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
 *     path="/api/v5/institution-student-admission/{id}",
 *     summary="Update InstitutionStudentAdmission",
 *     tags={"InstitutionStudentAdmission"},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="ID of the InstitutionStudentAdmission",
 *         @OA\Schema(type="string")
 *     ),
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             type="object",
                     @OA\Property(property="id", type="integer", example=null),
                     @OA\Property(property="start_date", type="string", format="date", example=null),
                     @OA\Property(property="end_date", type="string", format="date", example=null),
                     @OA\Property(property="student_id", type="integer", example=null),
                     @OA\Property(property="status_id", type="integer", example=null),
                     @OA\Property(property="assignee_id", type="integer", example=null),
                     @OA\Property(property="institution_id", type="integer", example=null),
                     @OA\Property(property="academic_period_id", type="integer", example=null),
                     @OA\Property(property="education_grade_id", type="integer", example=null),
                     @OA\Property(property="institution_class_id", type="integer", example=null),
                     @OA\Property(property="test_score", type="integer", example=null),
                     @OA\Property(property="interview_score", type="integer", example=null),
                     @OA\Property(property="comment", type="string", example=null),
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
 *     path="/api/v5/institution-student-admission/{id}",
 *     summary="Delete InstitutionStudentAdmission",
 *     tags={"InstitutionStudentAdmission"},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="ID of the InstitutionStudentAdmission",
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
    public function institution()
    {
        return $this->belongsTo(Institutions::class, 'institution_id', 'id');
    }


    public function assignee()
    {
        return $this->belongsTo(SecurityUsers::class, 'assignee_id', 'id');
    }

    public function securityUser()
    {
        return $this->belongsTo(SecurityUsers::class, 'created_user_id', 'id');
    }


    public function user()
    {
        return $this->belongsTo(SecurityUsers::class, 'student_id', 'id');
    }


    public function status()
    {
        return $this->belongsTo(WorkflowSteps::class, 'status_id', 'id');
    }
}
