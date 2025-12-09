<?php

namespace App\Models\Api5;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InstitutionClassAttendanceRecords extends Model
{
    use HasFactory;

    protected $table = 'institution_class_attendance_records';

    // ✅ Allow mass assignment
    protected $fillable = ['institution_class_id', 'academic_period_id', 'year', 'month', 'day_1', 'day_2', 'day_3', 'day_4', 'day_5', 'day_6', 'day_7', 'day_8', 'day_9', 'day_10', 'day_11', 'day_12', 'day_13', 'day_14', 'day_15', 'day_16', 'day_17', 'day_18', 'day_19', 'day_20', 'day_21', 'day_22', 'day_23', 'day_24', 'day_25', 'day_26', 'day_27', 'day_28', 'day_29', 'day_30', 'day_31'];

    // ✅ Disable Laravel's default timestamps
    public $timestamps = false;

    // ✅ Treat 'modified' and 'created' as timestamps
    protected $dates = ['modified', 'created'];

    // ✅ Define the primary key
    protected $primaryKey = ['institution_class_id', 'academic_period_id', 'year', 'month'];
    public $incrementing = false;

     // Override getKeyForSaveQuery to handle composite keys


/**
 * @OA\PathItem(
 *     path="/api/v5/institution-class-attendance-records"
 * )
 */
public function _swaggerPath() {}

/**
 * @OA\Get(
 *     path="/api/v5/institution-class-attendance-records",
 *     summary="Get list of InstitutionClassAttendanceRecords",
 *     tags={"InstitutionClassAttendanceRecords"},
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
                          @OA\Property(property="institution_class_id", type="integer", example=null),
                          @OA\Property(property="academic_period_id", type="integer", example=null),
                          @OA\Property(property="year", type="integer", example=null),
                          @OA\Property(property="month", type="integer", example=null),
                          @OA\Property(property="day_1", type="integer", example=null),
                          @OA\Property(property="day_2", type="integer", example=null),
                          @OA\Property(property="day_3", type="integer", example=null),
                          @OA\Property(property="day_4", type="integer", example=null),
                          @OA\Property(property="day_5", type="integer", example=null),
                          @OA\Property(property="day_6", type="integer", example=null),
                          @OA\Property(property="day_7", type="integer", example=null),
                          @OA\Property(property="day_8", type="integer", example=null),
                          @OA\Property(property="day_9", type="integer", example=null),
                          @OA\Property(property="day_10", type="integer", example=null),
                          @OA\Property(property="day_11", type="integer", example=null),
                          @OA\Property(property="day_12", type="integer", example=null),
                          @OA\Property(property="day_13", type="integer", example=null),
                          @OA\Property(property="day_14", type="integer", example=null),
                          @OA\Property(property="day_15", type="integer", example=null),
                          @OA\Property(property="day_16", type="integer", example=null),
                          @OA\Property(property="day_17", type="integer", example=null),
                          @OA\Property(property="day_18", type="integer", example=null),
                          @OA\Property(property="day_19", type="integer", example=null),
                          @OA\Property(property="day_20", type="integer", example=null),
                          @OA\Property(property="day_21", type="integer", example=null),
                          @OA\Property(property="day_22", type="integer", example=null),
                          @OA\Property(property="day_23", type="integer", example=null),
                          @OA\Property(property="day_24", type="integer", example=null),
                          @OA\Property(property="day_25", type="integer", example=null),
                          @OA\Property(property="day_26", type="integer", example=null),
                          @OA\Property(property="day_27", type="integer", example=null),
                          @OA\Property(property="day_28", type="integer", example=null),
                          @OA\Property(property="day_29", type="integer", example=null),
                          @OA\Property(property="day_30", type="integer", example=null),
                          @OA\Property(property="day_31", type="integer", example=null)
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
 *     path="/api/v5/institution-class-attendance-records",
 *     summary="Create a new InstitutionClassAttendanceRecords",
 *     tags={"InstitutionClassAttendanceRecords"},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             type="object",
                     @OA\Property(property="institution_class_id", type="integer", example=null),
                     @OA\Property(property="academic_period_id", type="integer", example=null),
                     @OA\Property(property="year", type="integer", example=null),
                     @OA\Property(property="month", type="integer", example=null),
                     @OA\Property(property="day_1", type="integer", example=null),
                     @OA\Property(property="day_2", type="integer", example=null),
                     @OA\Property(property="day_3", type="integer", example=null),
                     @OA\Property(property="day_4", type="integer", example=null),
                     @OA\Property(property="day_5", type="integer", example=null),
                     @OA\Property(property="day_6", type="integer", example=null),
                     @OA\Property(property="day_7", type="integer", example=null),
                     @OA\Property(property="day_8", type="integer", example=null),
                     @OA\Property(property="day_9", type="integer", example=null),
                     @OA\Property(property="day_10", type="integer", example=null),
                     @OA\Property(property="day_11", type="integer", example=null),
                     @OA\Property(property="day_12", type="integer", example=null),
                     @OA\Property(property="day_13", type="integer", example=null),
                     @OA\Property(property="day_14", type="integer", example=null),
                     @OA\Property(property="day_15", type="integer", example=null),
                     @OA\Property(property="day_16", type="integer", example=null),
                     @OA\Property(property="day_17", type="integer", example=null),
                     @OA\Property(property="day_18", type="integer", example=null),
                     @OA\Property(property="day_19", type="integer", example=null),
                     @OA\Property(property="day_20", type="integer", example=null),
                     @OA\Property(property="day_21", type="integer", example=null),
                     @OA\Property(property="day_22", type="integer", example=null),
                     @OA\Property(property="day_23", type="integer", example=null),
                     @OA\Property(property="day_24", type="integer", example=null),
                     @OA\Property(property="day_25", type="integer", example=null),
                     @OA\Property(property="day_26", type="integer", example=null),
                     @OA\Property(property="day_27", type="integer", example=null),
                     @OA\Property(property="day_28", type="integer", example=null),
                     @OA\Property(property="day_29", type="integer", example=null),
                     @OA\Property(property="day_30", type="integer", example=null),
                     @OA\Property(property="day_31", type="integer", example=null)
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
 *     path="/api/v5/institution-class-attendance-records/institution_class_id/{institution_class_id}/academic_period_id/{academic_period_id}/year/{year}/month/{month}",
 *     summary="Get InstitutionClassAttendanceRecords record by composite key",
 *     tags={"InstitutionClassAttendanceRecords"},
 *     @OA\Parameter(
 *         name="institution_class_id",
 *         in="path",
 *         required=true,
 *         description="institution_class_id",
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
 *         name="year",
 *         in="path",
 *         required=true,
 *         description="year",
 *         @OA\Schema(type="string")
 *     ),
 *     @OA\Parameter(
 *         name="month",
 *         in="path",
 *         required=true,
 *         description="month",
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
public function _swaggerView() {}

/**
 * @OA\Put(
 *     path="/api/v5/institution-class-attendance-records/institution_class_id/{institution_class_id}/academic_period_id/{academic_period_id}/year/{year}/month/{month}",
 *     summary="Update InstitutionClassAttendanceRecords record by composite key",
 *     tags={"InstitutionClassAttendanceRecords"},
 *     @OA\Parameter(
 *         name="institution_class_id",
 *         in="path",
 *         required=true,
 *         description="institution_class_id",
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
 *         name="year",
 *         in="path",
 *         required=true,
 *         description="year",
 *         @OA\Schema(type="string")
 *     ),
 *     @OA\Parameter(
 *         name="month",
 *         in="path",
 *         required=true,
 *         description="month",
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
public function _swaggerUpdate() {}

/**
 * @OA\Delete(
 *     path="/api/v5/institution-class-attendance-records/institution_class_id/{institution_class_id}/academic_period_id/{academic_period_id}/year/{year}/month/{month}",
 *     summary="Delete InstitutionClassAttendanceRecords record by composite key",
 *     tags={"InstitutionClassAttendanceRecords"},
 *     @OA\Parameter(
 *         name="institution_class_id",
 *         in="path",
 *         required=true,
 *         description="institution_class_id",
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
 *         name="year",
 *         in="path",
 *         required=true,
 *         description="year",
 *         @OA\Schema(type="string")
 *     ),
 *     @OA\Parameter(
 *         name="month",
 *         in="path",
 *         required=true,
 *         description="month",
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
