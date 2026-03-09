<?php

namespace App\Models\Api5;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\InstitutionScope;
use App\Traits\UuidId;
use App\Traits\ThresholdAlertTrait; // POCOR-9509
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

use App\Models\Concerns\WebhookQueueTrait;
class InstitutionStudents extends Model
{
    use HasFactory;
    use WebhookQueueTrait;

    // POCOR-9257: Configure webhook events
    protected $webhookEvents = ['created', 'updated', 'deleted'];    use InstitutionScope;
    use UuidId;
    use ThresholdAlertTrait; // POCOR-9509

    protected $table = 'institution_students';

    // ✅ Allow mass assignment
    protected $fillable = ['id', 'student_status_id', 'student_id', 'education_grade_id', 'academic_period_id', 'start_date', 'start_year', 'end_date', 'end_year', 'institution_id', 'previous_institution_student_id', 'modified_user_id', 'modified', 'created_user_id', 'created'];

    // ✅ Disable Laravel's default timestamps
    public $timestamps = false;

    // ✅ Treat 'modified' and 'created' as timestamps
    protected $dates = ['modified', 'created'];

    // ✅ Define the primary key

    public $incrementing = false;

    public $casts = [
        'id' => 'string',
    ];

    // POCOR-9509: Alert type configuration
    protected $alertType = 'StudentStatus';

    protected static function boot()
    {
        parent::boot();
        self::bootUuidId();

        // POCOR-9509: Trigger alert processing on status change
        static::saved(function ($student) {
            // Only trigger if status changed or is new
            if (!$student->wasRecentlyCreated && !$student->wasChanged('student_status_id')) {
                return;
            }

            if (!$student->student_id || !$student->institution_id || !$student->student_status_id) {
                // Log::warning('[POCOR-9509] Skipping student status alert - missing required fields', [
//                    'id' => $student->id,
//                    'student_id' => $student->student_id,
//                    'institution_id' => $student->institution_id,
//                ]);
                return;
            }

            try {
                $student->processStudentStatusAlert();
            } catch (\Throwable $e) {
                Log::error('[POCOR-9509] Student status alert processing failed in saved event', [
                    'student_id' => $student->id,
                    'exception' => $e->getMessage(),
                ]);
            }
        });
    }

    /**
     * POCOR-9509: Process student status alert after status changes
     */
    protected function processStudentStatusAlert(): bool
    {
        if (!$this->student_id || !$this->institution_id || !$this->id) {
            throw new \Exception('Missing required attributes for alert processing');
        }

        try {
            // Process threshold alert (always trigger for status changes)
            $result = $this->processThresholdAlert(
                (int) $this->institution_id,
                [
                    'institution_student_id' => $this->id,
                    'student_id' => (int) $this->student_id,
                    'student_status_id' => (int) $this->student_status_id,
                    'academic_period_id' => (int) $this->academic_period_id,
                ],
                (int) $this->student_id // Pass student_id as specificUserId
            );

            // Log::info('[POCOR-9509] Student status alert processed', [
            //     'institution_student_id' => $this->id,
            //     'alert_sent' => $result['sent'],
            // ]);

            return true;
        } catch (\Throwable $e) {
            Log::error('[POCOR-9509] Failed to process student status alert', [
                'institution_student_id' => $this->id,
                'exception' => $e->getMessage(),
            ]);
            throw new \Exception('Failed to process student status alert: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * POCOR-9509: Implement trait requirement - audit label
     */
    protected function getAuditLabel(): string
    {
        return 'StudentStatus';
    }

    /**
     * POCOR-9509: Implement trait requirement - get threshold data
     * For status changes, always trigger alert (current=9999, threshold should be set to 1 in alert rule)
     */
    protected function getThresholdData(array $context): array
    {
        // POCOR-9509: Always trigger for status changes
        // Set current=9999 so it's always >= threshold (which should be configured as 1 in alert rule)
        return [
            'current' => 9999,
            'institution_student_id' => $context['institution_student_id'] ?? '',
            'student_status_id' => $context['student_status_id'] ?? 0,
        ];
    }

    /**
     * POCOR-9509: Implement trait requirement - get placeholder data
     */

    protected function getSubjectPlaceholders(array $context): array
    {
        $institutionStudentId = $context['institution_student_id'] ?? '';

        // Get student with all related data and all guardians
        $data = DB::table('institution_students as InstitutionStudents')
            ->leftJoin('security_users as Users', 'Users.id', '=', 'InstitutionStudents.student_id')
            ->leftJoin('institutions as Institutions', 'Institutions.id', '=', 'InstitutionStudents.institution_id')
            ->leftJoin('academic_periods as AcademicPeriods', 'AcademicPeriods.id', '=', 'InstitutionStudents.academic_period_id')
            ->leftJoin('education_grades as EducationGrades', 'EducationGrades.id', '=', 'InstitutionStudents.education_grade_id')
            ->leftJoin('student_statuses as StudentStatuses', 'StudentStatuses.id', '=', 'InstitutionStudents.student_status_id')
            ->where('InstitutionStudents.id', $institutionStudentId)
            ->select([
                'InstitutionStudents.id',
                'InstitutionStudents.student_id as student_id',
                'InstitutionStudents.start_date',
                'InstitutionStudents.end_date',
                'AcademicPeriods.name as academic_period_name',
                'StudentStatuses.name as student_status',
                DB::raw("CONCAT(Users.first_name, ' ', Users.last_name) as student_name"),
                'Users.openemis_no as student_openemis_no',
                'Users.first_name as student_first_name',
                'Users.middle_name as student_middle_name',
                'Users.third_name as student_third_name',
                'Users.last_name as student_last_name',
                'Users.preferred_name as student_preferred_name',
                'Users.email as student_email',
                'Users.address as student_address',
                'Users.postal_code as student_postal_code',
                'Users.date_of_birth as student_date_of_birth',
                'Institutions.name as institution_name',
                'Institutions.code as institution_code',
                'Institutions.address as institution_address',
                'Institutions.postal_code as institution_postal_code',
                'Institutions.contact_person as institution_contact_person',
                'Institutions.telephone as institution_telephone',
                'Institutions.email as institution_email',
                'Institutions.website as institution_website',
                'EducationGrades.name as grade_name',
            ])
            ->first(); // We will get the student details once

        if (!$data) {
            // Log::debug('[POCOR-9509] No student data found for placeholders', [
            //     'institution_student_id' => $institutionStudentId,
            // ]);
            return [];
        }

        // Get all guardians for this student
        $guardians = DB::table('student_guardians')
            ->join('security_users as guardians', 'guardians.id', '=', 'student_guardians.guardian_id')
            ->leftJoin('guardian_relations', 'guardian_relations.id', '=', 'student_guardians.guardian_relation_id')
            ->where('student_guardians.student_id', $data->student_id)
            ->select([
                DB::raw("CONCAT(guardians.first_name, ' ', guardians.last_name) as guardian_name"),
                'guardian_relations.name as guardian_relation',
                'guardians.email as guardian_email',
                'guardians.mobile_number as guardian_mobile_number',
            ])
            ->get();

        $guardianNames = [];
        $guardianRelations = [];
        $guardianContacts = [];

        foreach ($guardians as $guardian) {
            if ($guardian->guardian_name) {
                $guardianNames[] = $guardian->guardian_name;
            }
            if ($guardian->guardian_relation) {
                $guardianRelations[] = $guardian->guardian_relation;
            }
            if ($guardian->guardian_email) {
                $guardianContacts[] = $guardian->guardian_email . ' (email)';
            }
            if ($guardian->guardian_mobile_number) {
                $guardianContacts[] = $guardian->guardian_mobile_number . ' (mobile)';
            }
        }

        return [
            '${academic_period.name}' => $data->academic_period_name ?? '',
            '${start_date}' => $data->start_date ?? '',
            '${end_date}' => $data->end_date ?? '',
            '${student_status}' => $data->student_status ?? '',
            '${student.name}' => $data->student_name ?? '',
            '${student.openemis_no}' => $data->student_openemis_no ?? '',
            '${student.first_name}' => $data->student_first_name ?? '',
            '${student.middle_name}' => $data->student_middle_name ?? '',
            '${student.third_name}' => $data->student_third_name ?? '',
            '${student.last_name}' => $data->student_last_name ?? '',
            '${student.preferred_name}' => $data->student_preferred_name ?? '',
            '${student.email}' => $data->student_email ?? '',
            '${student.address}' => $data->student_address ?? '',
            '${student.postal_code}' => $data->postal_code ?? '',
            '${student.date_of_birth}' => $data->date_of_birth ?? '',
            '${institution.name}' => $data->institution_name ?? '',
            '${institution.code}' => $data->institution_code ?? '',
            '${institution.address}' => $data->institution_address ?? '',
            '${institution.postal_code}' => $data->institution_postal_code ?? '',
            '${institution.contact_person}' => $data->institution_contact_person ?? '',
            '${institution.telephone}' => $data->institution_telephone ?? '',
            '${institution.email}' => $data->institution_email ?? '',
            '${institution.website}' => $data->institution_website ?? '',
            '${grade.name}' => $data->grade_name ?? '',
            '${guardian.name}' => implode(', ', $guardianNames),
            '${guardian.relation}' => implode(', ', $guardianRelations),
            '${guardian.contact}' => implode(', ', $guardianContacts),
        ];
    }

     // Override getKeyForSaveQuery to handle composite keys


/**
 * @OA\PathItem(
 *     path="/api/v5/institution-students"
 * )
 */
public function _swaggerPath() {}

/**
 * @OA\Get(
 *     path="/api/v5/institution-students",
 *     summary="Get list of InstitutionStudents",
 *     tags={"InstitutionStudents"},
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
                          @OA\Property(property="student_status_id", type="integer", example=null),
                          @OA\Property(property="student_id", type="integer", example=null),
                          @OA\Property(property="education_grade_id", type="integer", example=null),
                          @OA\Property(property="academic_period_id", type="integer", example=null),
                          @OA\Property(property="start_date", type="string", format="date", example=null),
                          @OA\Property(property="start_year", type="integer", example=null),
                          @OA\Property(property="end_date", type="string", format="date", example=null),
                          @OA\Property(property="end_year", type="integer", example=null),
                          @OA\Property(property="institution_id", type="integer", example=null),
                          @OA\Property(property="previous_institution_student_id", type="string", example=null),
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
 *     path="/api/v5/institution-students",
 *     summary="Create a new InstitutionStudents",
 *     tags={"InstitutionStudents"},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             type="object",
                     @OA\Property(property="id", type="string", example=null),
                     @OA\Property(property="student_status_id", type="integer", example=null),
                     @OA\Property(property="student_id", type="integer", example=null),
                     @OA\Property(property="education_grade_id", type="integer", example=null),
                     @OA\Property(property="academic_period_id", type="integer", example=null),
                     @OA\Property(property="start_date", type="string", format="date", example=null),
                     @OA\Property(property="start_year", type="integer", example=null),
                     @OA\Property(property="end_date", type="string", format="date", example=null),
                     @OA\Property(property="end_year", type="integer", example=null),
                     @OA\Property(property="institution_id", type="integer", example=null),
                     @OA\Property(property="previous_institution_student_id", type="string", example=null),
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
 *     path="/api/v5/institution-students/{id}",
 *     summary="Get InstitutionStudents by ID",
 *     tags={"InstitutionStudents"},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="ID of the InstitutionStudents",
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
 *     path="/api/v5/institution-students/{id}",
 *     summary="Update InstitutionStudents",
 *     tags={"InstitutionStudents"},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="ID of the InstitutionStudents",
 *         @OA\Schema(type="string")
 *     ),
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             type="object",
                     @OA\Property(property="id", type="string", example=null),
                     @OA\Property(property="student_status_id", type="integer", example=null),
                     @OA\Property(property="student_id", type="integer", example=null),
                     @OA\Property(property="education_grade_id", type="integer", example=null),
                     @OA\Property(property="academic_period_id", type="integer", example=null),
                     @OA\Property(property="start_date", type="string", format="date", example=null),
                     @OA\Property(property="start_year", type="integer", example=null),
                     @OA\Property(property="end_date", type="string", format="date", example=null),
                     @OA\Property(property="end_year", type="integer", example=null),
                     @OA\Property(property="institution_id", type="integer", example=null),
                     @OA\Property(property="previous_institution_student_id", type="string", example=null),
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
 *     path="/api/v5/institution-students/{id}",
 *     summary="Delete InstitutionStudents",
 *     tags={"InstitutionStudents"},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="ID of the InstitutionStudents",
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
