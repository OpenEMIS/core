<?php

namespace App\Models\Api5;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\ThresholdAlertTrait;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class StaffEmploymentStatuses extends Model
{
    use HasFactory;
    use ThresholdAlertTrait;

    protected $table = 'staff_employment_statuses';

    // ✅ Allow mass assignment
    protected $fillable = ['id', 'status_date', 'comment', 'file_name', 'file_content', 'staff_id', 'status_type_id', 'modified_user_id', 'modified', 'created_user_id', 'created'];

    // ✅ Disable Laravel's default timestamps
    public $timestamps = false;

    // ✅ Treat 'modified' and 'created' as timestamps
    protected $dates = ['modified', 'created'];

    // POCOR-9509: Alert configuration
    protected $alertType = 'StaffEmployment';

    /**
     * POCOR-9509: Boot method to register model events
     */
    protected static function boot()
    {
        parent::boot();

        // POCOR-9509: Trigger alert processing after save (only for new records)
        static::saved(function ($employment) {
            if (!$employment->wasRecentlyCreated) {
                return;
            }

            if (!$employment->staff_id || !$employment->status_date) {
                // Log::warning('[POCOR-9509] Skipping employment alert - missing required fields', [
                //     'staff_id' => $employment->staff_id,
                //     'status_date' => $employment->status_date,
                // ]);
                return;
            }

            try {
                $employment->processEmploymentAlert();
            } catch (\Throwable $e) {
                Log::error('[POCOR-9509] Employment alert processing failed in saved event', [
                    'staff_id' => $employment->staff_id,
                    'exception' => $e->getMessage(),
                ]);
            }
        });
    }

    /**
     * POCOR-9509: Process employment alert after record is saved
     * Must get institution context from institution_staff table
     */
    protected function processEmploymentAlert(): bool
    {
        // Get institution(s) where staff is assigned
        $staffAssignments = DB::table('institution_staff')
            ->where('staff_id', $this->staff_id)
            ->where('staff_status_id', function ($q) {
                $q->select('id')
                  ->from('staff_statuses')
                  ->where('code', 'ASSIGNED')
                  ->limit(1);
            })
            ->select('institution_id')
            ->distinct()
            ->pluck('institution_id')
            ->toArray();

        if (empty($staffAssignments)) {
            // Log::warning('[POCOR-9509] No institution assignment found for employment alert', [
            //     'staff_id' => $this->staff_id,
            // ]);
            return false;
        }

        $sentCount = 0;
        foreach ($staffAssignments as $institutionId) {
            $result = $this->processThresholdAlert((int) $institutionId, [
                'staff_id' => (int) $this->staff_id,
                'status_date' => $this->status_date,
                'status_type_id' => $this->status_type_id,
            ]);

            if ($result['sent']) {
                $sentCount++;
            }
        }

        // Log::info('[POCOR-9509] Employment alert processed', [
        //     'staff_id' => $this->staff_id,
        //     'institutions' => count($staffAssignments),
        //     'alerts_sent' => $sentCount,
        // ]);

        return $sentCount > 0;
    }

    /**
     * POCOR-9509: Implement trait requirement - audit label
     */
    protected function getAuditLabel(): string
    {
        return 'StaffEmployment';
    }

    /**
     * POCOR-9509: Implement trait requirement - calculate days before/after employment date
     * Supports both "days before" (future) and "days after" (past) scenarios
     */
    protected function getThresholdData(array $context): array
    {
        $statusDateStr = $context['status_date'] ?? null;

        if (!$statusDateStr) {
            return ['current' => 0];
        }

        try {
            $statusDate = new \DateTime($statusDateStr);
            $today = new \DateTime();
            $diff = $today->diff($statusDate);

            // Days difference: positive if future, negative if past
            $daysDiff = $diff->days;
            if ($diff->invert) {
                $daysDiff = -$daysDiff;
            }

            return [
                'current' => abs($daysDiff),
                'days_from_employment' => $daysDiff,
            ];
        } catch (\Throwable $e) {
            Log::error('[POCOR-9509] Failed to calculate employment threshold data', [
                'status_date' => $statusDateStr,
                'exception' => $e->getMessage(),
            ]);
            return ['current' => 0];
        }
    }

    /**
     * POCOR-9509: Implement trait requirement - get placeholder data for alert message
     */
    protected function getSubjectPlaceholders(array $context): array
    {
        $staffId = $context['staff_id'] ?? 0;
        $institutionId = $context['institution_id'] ?? 0;

        $staff = DB::table('security_users')
            ->where('id', $staffId)
            ->select([
                'id', 'openemis_no', 'first_name', 'middle_name', 'last_name',
                'preferred_name', 'email', 'address', 'postal_code', 'date_of_birth'
            ])
            ->first();

        $institution = DB::table('institutions')
            ->where('id', $institutionId)
            ->select(['id', 'name', 'code', 'address', 'postal_code', 'contact_person', 'telephone', 'email', 'website'])
            ->first();

        if (!$staff || !$institution) {
            return [];
        }

        // Get employment status type name
        $employmentStatus = DB::table('staff_employment_statuses')
            ->where('staff_id', $staffId)
            ->orderBy('status_date', 'desc')
            ->select(['status_type_id', 'status_date'])
            ->first();

        $statusTypeName = '';
        if ($employmentStatus) {
            $statusTypeRecord = DB::table('staff_employment_status_types')
                ->where('id', $employmentStatus->status_type_id)
                ->value('name');
            $statusTypeName = $statusTypeRecord ?? '';
        }

        return [
            '${user.openemis_no}' => $staff->openemis_no,
            '${user.first_name}' => $staff->first_name,
            '${user.middle_name}' => $staff->middle_name ?? '',
            '${user.last_name}' => $staff->last_name,
            '${user.preferred_name}' => $staff->preferred_name ?? '',
            '${user.email}' => $staff->email ?? '',
            '${user.address}' => $staff->address ?? '',
            '${user.postal_code}' => $staff->postal_code ?? '',
            '${user.date_of_birth}' => $staff->date_of_birth ?? '',
            '${employment_type.name}' => $statusTypeName,
            '${employment_date}' => ($employmentStatus ? $employmentStatus->status_date : ''),
            '${institution.name}' => $institution->name,
            '${institution.code}' => $institution->code,
            '${institution.address}' => $institution->address ?? '',
            '${institution.postal_code}' => $institution->postal_code ?? '',
            '${institution.contact_person}' => $institution->contact_person ?? '',
            '${institution.telephone}' => $institution->telephone ?? '',
            '${institution.email}' => $institution->email ?? '',
            '${institution.website}' => $institution->website ?? '',
        ];
    }

    // ✅ Define the primary key


     // Override getKeyForSaveQuery to handle composite keys


/**
 * @OA\PathItem(
 *     path="/api/v5/staff-employment-statuses"
 * )
 */
public function _swaggerPath() {}

/**
 * @OA\Get(
 *     path="/api/v5/staff-employment-statuses",
 *     summary="Get list of StaffEmploymentStatuses",
 *     tags={"StaffEmploymentStatuses"},
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
                          @OA\Property(property="status_date", type="string", format="date", example=null),
                          @OA\Property(property="comment", type="string", example=null),
                          @OA\Property(property="file_name", type="string", example=null),
                          @OA\Property(property="file_content", type="string", example=null),
                          @OA\Property(property="staff_id", type="integer", example=null),
                          @OA\Property(property="status_type_id", type="integer", example=null),
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
 *     path="/api/v5/staff-employment-statuses",
 *     summary="Create a new StaffEmploymentStatuses",
 *     tags={"StaffEmploymentStatuses"},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             type="object",
                     @OA\Property(property="id", type="integer", example=null),
                     @OA\Property(property="status_date", type="string", format="date", example=null),
                     @OA\Property(property="comment", type="string", example=null),
                     @OA\Property(property="file_name", type="string", example=null),
                     @OA\Property(property="file_content", type="string", example=null),
                     @OA\Property(property="staff_id", type="integer", example=null),
                     @OA\Property(property="status_type_id", type="integer", example=null),
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
 *     path="/api/v5/staff-employment-statuses/{id}",
 *     summary="Get StaffEmploymentStatuses by ID",
 *     tags={"StaffEmploymentStatuses"},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="ID of the StaffEmploymentStatuses",
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
 *     path="/api/v5/staff-employment-statuses/{id}",
 *     summary="Update StaffEmploymentStatuses",
 *     tags={"StaffEmploymentStatuses"},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="ID of the StaffEmploymentStatuses",
 *         @OA\Schema(type="string")
 *     ),
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             type="object",
                     @OA\Property(property="id", type="integer", example=null),
                     @OA\Property(property="status_date", type="string", format="date", example=null),
                     @OA\Property(property="comment", type="string", example=null),
                     @OA\Property(property="file_name", type="string", example=null),
                     @OA\Property(property="file_content", type="string", example=null),
                     @OA\Property(property="staff_id", type="integer", example=null),
                     @OA\Property(property="status_type_id", type="integer", example=null),
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
 *     path="/api/v5/staff-employment-statuses/{id}",
 *     summary="Delete StaffEmploymentStatuses",
 *     tags={"StaffEmploymentStatuses"},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="ID of the StaffEmploymentStatuses",
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
