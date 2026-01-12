<?php

namespace App\Models\Api5;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\InstitutionScope;

class SummaryInstitutions extends Model
{
    use HasFactory;
use InstitutionScope;
    // ✅ Allow mass assignment
    protected $fillable = ['academic_period_id', 'academic_period_name', 'institution_id', 'institution_code', 'total_grades', 'total_classes', 'total_lands', 'total_land_size', 'total_buildings', 'total_building_sizes', 'total_floors', 'total_floor_sizes', 'total_rooms', 'total_room_sizes', 'total_room_classrooms', 'total_room_classroom_sizes', 'total_students', 'total_students_female', 'total_students_male', 'total_staff_teaching', 'total_staff_teaching_female', 'total_staff_teaching_male', 'total_staff_non_teaching', 'total_staff_non_teaching_female', 'total_staff_non_teaching_male', 'academic_period_id', 'institution_id'];

    public $timestamps = false;
    protected $table = "summary_institutions";


    // ✅ Treat 'modified' and 'created' as timestamps
    protected $dates = ['modified', 'created'];

    // ✅ Define the primary key

    // ✅ Define the primary key
    public $incrementing = false;
    protected $primaryKey = null;


/**
 * @OA\PathItem(
 *     path="/api/v5/summary-institutions"
 * )
 */
public function _swaggerPath() {}

/**
 * @OA\Get(
 *     path="/api/v5/summary-institutions",
 *     summary="Get list of SummaryInstitutions",
 *     tags={"SummaryInstitutions"},
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
                          @OA\Property(property="academic_period_id", type="integer", example=null),
                          @OA\Property(property="academic_period_name", type="string", example=null),
                          @OA\Property(property="institution_id", type="integer", example=null),
                          @OA\Property(property="institution_code", type="string", example=null),
                          @OA\Property(property="total_grades", type="integer", example=null),
                          @OA\Property(property="total_classes", type="integer", example=null),
                          @OA\Property(property="total_lands", type="integer", example=null),
                          @OA\Property(property="total_land_size", type="integer", example=null),
                          @OA\Property(property="total_buildings", type="integer", example=null),
                          @OA\Property(property="total_building_sizes", type="integer", example=null),
                          @OA\Property(property="total_floors", type="integer", example=null),
                          @OA\Property(property="total_floor_sizes", type="integer", example=null),
                          @OA\Property(property="total_rooms", type="integer", example=null),
                          @OA\Property(property="total_room_sizes", type="integer", example=null),
                          @OA\Property(property="total_room_classrooms", type="integer", example=null),
                          @OA\Property(property="total_room_classroom_sizes", type="integer", example=null),
                          @OA\Property(property="total_students", type="integer", example=null),
                          @OA\Property(property="total_students_female", type="integer", example=null),
                          @OA\Property(property="total_students_male", type="integer", example=null),
                          @OA\Property(property="total_staff_teaching", type="integer", example=null),
                          @OA\Property(property="total_staff_teaching_female", type="integer", example=null),
                          @OA\Property(property="total_staff_teaching_male", type="integer", example=null),
                          @OA\Property(property="total_staff_non_teaching", type="integer", example=null),
                          @OA\Property(property="total_staff_non_teaching_female", type="integer", example=null),
                          @OA\Property(property="total_staff_non_teaching_male", type="integer", example=null)
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
 *     path="/api/v5/summary-institutions",
 *     summary="Create a new SummaryInstitutions",
 *     tags={"SummaryInstitutions"},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             type="object",
                     @OA\Property(property="academic_period_id", type="integer", example=null),
                     @OA\Property(property="academic_period_name", type="string", example=null),
                     @OA\Property(property="institution_id", type="integer", example=null),
                     @OA\Property(property="institution_code", type="string", example=null),
                     @OA\Property(property="total_grades", type="integer", example=null),
                     @OA\Property(property="total_classes", type="integer", example=null),
                     @OA\Property(property="total_lands", type="integer", example=null),
                     @OA\Property(property="total_land_size", type="integer", example=null),
                     @OA\Property(property="total_buildings", type="integer", example=null),
                     @OA\Property(property="total_building_sizes", type="integer", example=null),
                     @OA\Property(property="total_floors", type="integer", example=null),
                     @OA\Property(property="total_floor_sizes", type="integer", example=null),
                     @OA\Property(property="total_rooms", type="integer", example=null),
                     @OA\Property(property="total_room_sizes", type="integer", example=null),
                     @OA\Property(property="total_room_classrooms", type="integer", example=null),
                     @OA\Property(property="total_room_classroom_sizes", type="integer", example=null),
                     @OA\Property(property="total_students", type="integer", example=null),
                     @OA\Property(property="total_students_female", type="integer", example=null),
                     @OA\Property(property="total_students_male", type="integer", example=null),
                     @OA\Property(property="total_staff_teaching", type="integer", example=null),
                     @OA\Property(property="total_staff_teaching_female", type="integer", example=null),
                     @OA\Property(property="total_staff_teaching_male", type="integer", example=null),
                     @OA\Property(property="total_staff_non_teaching", type="integer", example=null),
                     @OA\Property(property="total_staff_non_teaching_female", type="integer", example=null),
                     @OA\Property(property="total_staff_non_teaching_male", type="integer", example=null)
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
 *     path="/api/v5/summary-institutions/{id}",
 *     summary="Get SummaryInstitutions by ID",
 *     tags={"SummaryInstitutions"},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="ID of the SummaryInstitutions",
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
 *     path="/api/v5/summary-institutions/{id}",
 *     summary="Update SummaryInstitutions",
 *     tags={"SummaryInstitutions"},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="ID of the SummaryInstitutions",
 *         @OA\Schema(type="string")
 *     ),
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             type="object",
                     @OA\Property(property="academic_period_id", type="integer", example=null),
                     @OA\Property(property="academic_period_name", type="string", example=null),
                     @OA\Property(property="institution_id", type="integer", example=null),
                     @OA\Property(property="institution_code", type="string", example=null),
                     @OA\Property(property="total_grades", type="integer", example=null),
                     @OA\Property(property="total_classes", type="integer", example=null),
                     @OA\Property(property="total_lands", type="integer", example=null),
                     @OA\Property(property="total_land_size", type="integer", example=null),
                     @OA\Property(property="total_buildings", type="integer", example=null),
                     @OA\Property(property="total_building_sizes", type="integer", example=null),
                     @OA\Property(property="total_floors", type="integer", example=null),
                     @OA\Property(property="total_floor_sizes", type="integer", example=null),
                     @OA\Property(property="total_rooms", type="integer", example=null),
                     @OA\Property(property="total_room_sizes", type="integer", example=null),
                     @OA\Property(property="total_room_classrooms", type="integer", example=null),
                     @OA\Property(property="total_room_classroom_sizes", type="integer", example=null),
                     @OA\Property(property="total_students", type="integer", example=null),
                     @OA\Property(property="total_students_female", type="integer", example=null),
                     @OA\Property(property="total_students_male", type="integer", example=null),
                     @OA\Property(property="total_staff_teaching", type="integer", example=null),
                     @OA\Property(property="total_staff_teaching_female", type="integer", example=null),
                     @OA\Property(property="total_staff_teaching_male", type="integer", example=null),
                     @OA\Property(property="total_staff_non_teaching", type="integer", example=null),
                     @OA\Property(property="total_staff_non_teaching_female", type="integer", example=null),
                     @OA\Property(property="total_staff_non_teaching_male", type="integer", example=null)
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
 *     path="/api/v5/summary-institutions/{id}",
 *     summary="Delete SummaryInstitutions",
 *     tags={"SummaryInstitutions"},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="ID of the SummaryInstitutions",
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
private function emptyFunction() { return; }
}
