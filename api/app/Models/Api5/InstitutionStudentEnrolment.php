<?php

namespace App\Models\Api5;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\InstitutionScope;
use App\Services\AlertTriggerService; // POCOR-9509
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InstitutionStudentEnrolment extends Model
{
    use HasFactory;
    use InstitutionScope;

    protected $table = 'institution_student_enrolment';

    // ✅ Allow mass assignment
    protected $fillable = ['id', 'start_date', 'end_date', 'student_id', 'status_id', 'assignee_id', 'institution_id', 'academic_period_id', 'education_grade_id', 'institution_class_id', 'comment', 'modified_user_id', 'modified', 'created_user_id', 'created'];

    // ✅ Disable Laravel's default timestamps
    public $timestamps = false;

    // ✅ Treat 'modified' and 'created' as timestamps
    protected $dates = ['modified', 'created'];

    /**
     * POCOR-9509: Boot method to register model events
     */
    protected static function boot()
    {
        parent::boot();

        // POCOR-9509: Trigger alert processing after save (create or update)
        static::saved(function ($enrolment) {
            if (!$enrolment->student_id || !$enrolment->institution_id || !$enrolment->status_id) {
                // Log::warning('[POCOR-9509] Skipping enrolment alert - missing required fields', [
//                    'id' => $enrolment->id,
//                    'student_id' => $enrolment->student_id,
//                    'institution_id' => $enrolment->institution_id,
//                ]);
                return;
            }

            try {
                $enrolment->processEnrolmentAlert();
            } catch (\Throwable $e) {
                Log::error('[POCOR-9509] Enrolment alert processing failed in saved event', [
                    'enrolment_id' => $enrolment->id,
                    'exception' => $e->getMessage(),
                ]);
            }
        });
    }

    /**
     * POCOR-9509: Process enrolment alert after enrolment status changes
     *
     * Phase 3: Models just trigger alerts, commands do all the work
     */
    protected function processEnrolmentAlert(): bool
    {
        if (!$this->student_id || !$this->institution_id || !$this->id) {
            throw new \Exception('Missing required attributes for alert processing');
        }

        try {
            // POCOR-9509: Get active alert rule
            $alertRule = AlertTriggerService::getActiveAlertRule('StudentEnrolment', $this->institution_id);

            if (!$alertRule) {
                // Log::debug('[POCOR-9509] No active StudentEnrolment alert rule for institution', [
                //     'institution_id' => $this->institution_id,
                // ]);
                return false;
            }

            // POCOR-9509: Trigger alert (creates process + dispatches command)
            // Command will handle everything: threshold checks, placeholders, recipients, sending
            // entityId is passed as --entity_id to command, context is for params tracking only
            $triggerResult = AlertTriggerService::triggerAlert(
                processName: 'AlertStudentEnrolment',
                featureName: 'StudentEnrolment',
                userId: $this->created_user_id ?? 1,
                ruleId: $alertRule->id,
                entityId: $this->id, // Passed as --entity_id to command
                context: [
                    'student_id' => $this->student_id,
                    'status_id' => $this->status_id,
                    'academic_period_id' => $this->academic_period_id,
                    'institution_id' => $this->institution_id,
                    'change_date' => $this->modified ?? $this->created,
                ],
                entityType: 'StudentEnrolment',
                triggerType: 'status_changed'
            );

            // Log::info('[POCOR-9509] Enrolment alert triggered', [
            //     'enrolment_id' => $this->id,
            //     'success' => $triggerResult['success'],
            //     'process_id' => $triggerResult['process_id'] ?? null,
            //     'duplicate' => $triggerResult['duplicate'] ?? false,
            // ]);

            return true;
        } catch (\Throwable $e) {
            Log::error('[POCOR-9509] Failed to trigger enrolment alert', [
                'enrolment_id' => $this->id,
                'exception' => $e->getMessage(),
            ]);
            // Don't throw - graceful degradation
            return false;
        }
    }

     // Override getKeyForSaveQuery to handle composite keys


/**
 * @OA\PathItem(
 *     path="/api/v5/institution-student-enrolment"
 * )
 */
public function _swaggerPath() {}

/**
 * @OA\Get(
 *     path="/api/v5/institution-student-enrolment",
 *     summary="Get list of InstitutionStudentEnrolment",
 *     tags={"InstitutionStudentEnrolment"},
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
 *     path="/api/v5/institution-student-enrolment",
 *     summary="Create a new InstitutionStudentEnrolment",
 *     tags={"InstitutionStudentEnrolment"},
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
 *     path="/api/v5/institution-student-enrolment/{id}",
 *     summary="Get InstitutionStudentEnrolment by ID",
 *     tags={"InstitutionStudentEnrolment"},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="ID of the InstitutionStudentEnrolment",
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
 *     path="/api/v5/institution-student-enrolment/{id}",
 *     summary="Update InstitutionStudentEnrolment",
 *     tags={"InstitutionStudentEnrolment"},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="ID of the InstitutionStudentEnrolment",
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
 *     path="/api/v5/institution-student-enrolment/{id}",
 *     summary="Delete InstitutionStudentEnrolment",
 *     tags={"InstitutionStudentEnrolment"},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="ID of the InstitutionStudentEnrolment",
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
    protected function getKeyForSaveQuery()
    {
        $query = $this->newQueryWithoutScopes();
        $keyName = $this->getKeyName();
        if(!is_array($keyName)){
            $keyName = [$keyName];;
        }
        foreach ($keyName as $key) {
            $query->where($key, '=', $this->getAttribute($key));
        }

        return $query;
    }

    // Override setKeysForSaveQuery to handle composite keys
    protected function setKeysForSaveQuery($query)
    {
        $keyName = $this->getKeyName();
        if(!is_array($keyName)){
            $keyName = [$keyName];;
        }
        foreach ($keyName as $key) {
            $query->where($key, '=', $this->getAttribute($key));
        }

        return $query;
    }

    public static function getValidationRules(): array
    {
        return [
            // Add validation rules here
        ];
    }


}
