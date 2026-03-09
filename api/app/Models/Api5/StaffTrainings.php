<?php

namespace App\Models\Api5;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\ThresholdAlertTrait;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class StaffTrainings extends Model
{
    use HasFactory;
    use ThresholdAlertTrait;

    protected $table = 'staff_trainings';

    // ✅ Allow mass assignment
    protected $fillable = ['id', 'code', 'name', 'description', 'credit_hours', 'file_name', 'file_content', 'completed_date', 'staff_id', 'staff_training_category_id', 'training_field_of_study_id', 'modified_user_id', 'modified', 'created_user_id', 'created'];

    // ✅ Disable Laravel's default timestamps
    public $timestamps = false;

    // ✅ Treat 'modified' and 'created' as timestamps
    protected $dates = ['modified', 'created'];

    // POCOR-9509: Alert configuration
    // Note: StaffTrainings can be used for both LicenseRenewal (before expiry) and LicenseValidity (after expiry)
    // Configure alert rule's feature field to one of: 'LicenseRenewal' or 'LicenseValidity'
    protected $alertType = 'LicenseRenewal'; // Can be overridden if needed

    /**
     * POCOR-9509: Boot method to register model events
     */
    protected static function boot()
    {
        parent::boot();

        // POCOR-9509: Trigger alert processing after save (only for new records)
        static::saved(function ($training) {
            if (!$training->wasRecentlyCreated) {
                return;
            }

            if (!$training->staff_id) {
                // Log::warning('[POCOR-9509] Skipping training alert - missing staff_id', [
                //     'training_id' => $training->id,
                // ]);
                return;
            }

            try {
                $training->processTrainingAlert();
            } catch (\Throwable $e) {
                Log::error('[POCOR-9509] Training alert processing failed in saved event', [
                    'staff_id' => $training->staff_id,
                    'exception' => $e->getMessage(),
                ]);
            }
        });
    }

    /**
     * POCOR-9509: Process training/license alert after record is saved
     * Must get institution context from institution_staff table (staff may be assigned to multiple institutions)
     */
    protected function processTrainingAlert(): bool
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
            // Log::warning('[POCOR-9509] No institution assignment found for training alert', [
            //     'staff_id' => $this->staff_id,
            // ]);
            return false;
        }

        $sentCount = 0;
        foreach ($staffAssignments as $institutionId) {
            $result = $this->processThresholdAlert((int) $institutionId, [
                'staff_id' => (int) $this->staff_id,
                'training_id' => $this->id,
            ]);

            if ($result['sent']) {
                $sentCount++;
            }
        }

        // Log::info('[POCOR-9509] Training alert processed', [
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
        return 'StaffTraining';
    }

    /**
     * POCOR-9509: Implement trait requirement - get days until license expiry
     * Note: Alert rules determine if this triggers for "renewal" (before expiry) or "validity" (after expiry)
     */
    protected function getThresholdData(array $context): array
    {
        $training = DB::table('staff_trainings')
            ->where('id', $context['training_id'] ?? $this->id)
            ->select(['id', 'name', 'completed_date'])
            ->first();

        // Staff trainings table doesn't have expiry_date column by default
        // For now, use completed_date + 1 year as expiry approximation
        // In production, this would need to be customized based on your license schema
        if (!$training || !$training->completed_date) {
            return ['current' => 0];
        }

        try {
            $completedDate = new \DateTime($training->completed_date);
            // Assume licenses expire 1 year from completion
            $expiryDate = $completedDate->modify('+1 year');
            $today = new \DateTime();
            $diff = $today->diff($expiryDate);

            $daysDiff = $diff->days;
            if ($diff->invert) {
                $daysDiff = -$daysDiff; // negative if expired
            }

            return [
                'current' => abs($daysDiff),
                'days_until_expiry' => $daysDiff,
                'is_expired' => $daysDiff < 0,
            ];
        } catch (\Throwable $e) {
            Log::error('[POCOR-9509] Failed to calculate training threshold data', [
                'training_id' => $context['training_id'] ?? $this->id,
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
        $trainingId = $context['training_id'] ?? $this->id;

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

        $training = DB::table('staff_trainings')
            ->where('id', $trainingId)
            ->select(['id', 'name', 'completed_date'])
            ->first();

        if (!$staff || !$institution || !$training) {
            return [];
        }

        // Calculate expiry date (completed_date + 1 year)
        $expiryDate = '';
        $daysRemaining = 0;
        if ($training->completed_date) {
            try {
                $completedDt = new \DateTime($training->completed_date);
                $expiryDt = $completedDt->modify('+1 year');
                $expiryDate = $expiryDt->format('Y-m-d');

                $today = new \DateTime();
                $diff = $today->diff($expiryDt);
                $daysRemaining = $diff->invert ? -$diff->days : $diff->days;
            } catch (\Throwable $e) {
                Log::error('[POCOR-9509] Failed to calculate expiry date', [
                    'completed_date' => $training->completed_date,
                ]);
            }
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
            '${certificate.name}' => $training->name,
            '${completed_date}' => $training->completed_date,
            '${expiry_date}' => $expiryDate,
            '${days_remaining}' => (string)$daysRemaining,
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
 *     path="/api/v5/staff-trainings"
 * )
 */
public function _swaggerPath() {}

/**
 * @OA\Get(
 *     path="/api/v5/staff-trainings",
 *     summary="Get list of StaffTrainings",
 *     tags={"StaffTrainings"},
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
                          @OA\Property(property="code", type="string", example=null),
                          @OA\Property(property="name", type="string", example=null),
                          @OA\Property(property="description", type="string", example=null),
                          @OA\Property(property="credit_hours", type="integer", example=null),
                          @OA\Property(property="file_name", type="string", example=null),
                          @OA\Property(property="file_content", type="string", example=null),
                          @OA\Property(property="completed_date", type="string", format="date", example=null),
                          @OA\Property(property="staff_id", type="integer", example=null),
                          @OA\Property(property="staff_training_category_id", type="integer", example=null),
                          @OA\Property(property="training_field_of_study_id", type="integer", example=null),
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
 *     path="/api/v5/staff-trainings",
 *     summary="Create a new StaffTrainings",
 *     tags={"StaffTrainings"},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             type="object",
                     @OA\Property(property="id", type="integer", example=null),
                     @OA\Property(property="code", type="string", example=null),
                     @OA\Property(property="name", type="string", example=null),
                     @OA\Property(property="description", type="string", example=null),
                     @OA\Property(property="credit_hours", type="integer", example=null),
                     @OA\Property(property="file_name", type="string", example=null),
                     @OA\Property(property="file_content", type="string", example=null),
                     @OA\Property(property="completed_date", type="string", format="date", example=null),
                     @OA\Property(property="staff_id", type="integer", example=null),
                     @OA\Property(property="staff_training_category_id", type="integer", example=null),
                     @OA\Property(property="training_field_of_study_id", type="integer", example=null),
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
 *     path="/api/v5/staff-trainings/{id}",
 *     summary="Get StaffTrainings by ID",
 *     tags={"StaffTrainings"},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="ID of the StaffTrainings",
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
 *     path="/api/v5/staff-trainings/{id}",
 *     summary="Update StaffTrainings",
 *     tags={"StaffTrainings"},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="ID of the StaffTrainings",
 *         @OA\Schema(type="string")
 *     ),
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             type="object",
                     @OA\Property(property="id", type="integer", example=null),
                     @OA\Property(property="code", type="string", example=null),
                     @OA\Property(property="name", type="string", example=null),
                     @OA\Property(property="description", type="string", example=null),
                     @OA\Property(property="credit_hours", type="integer", example=null),
                     @OA\Property(property="file_name", type="string", example=null),
                     @OA\Property(property="file_content", type="string", example=null),
                     @OA\Property(property="completed_date", type="string", format="date", example=null),
                     @OA\Property(property="staff_id", type="integer", example=null),
                     @OA\Property(property="staff_training_category_id", type="integer", example=null),
                     @OA\Property(property="training_field_of_study_id", type="integer", example=null),
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
 *     path="/api/v5/staff-trainings/{id}",
 *     summary="Delete StaffTrainings",
 *     tags={"StaffTrainings"},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="ID of the StaffTrainings",
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
