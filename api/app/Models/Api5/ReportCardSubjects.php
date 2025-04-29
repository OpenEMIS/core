<?php

namespace App\Models\Api5;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReportCardSubjects extends Model
{
    use HasFactory;

    protected $table = 'report_card_subjects';

    // ✅ Allow mass assignment
    protected $fillable = ['id', 'report_card_id', 'education_subject_id', 'education_grade_id', 'created_user_id', 'created'];

    // ✅ Disable Laravel's default timestamps
    public $timestamps = false;

    // ✅ Treat 'modified' and 'created' as timestamps
    protected $dates = ['modified', 'created'];

    // ✅ Define the primary key
    protected $primaryKey = ['report_card_id', 'education_subject_id'];
    public $incrementing = false;

     // Override getKeyForSaveQuery to handle composite keys


/**
 * @OA\PathItem(
 *     path="/api/v5/report-card-subjects"
 * )
 */
public function _swaggerPath() {}

/**
 * @OA\Get(
 *     path="/api/v5/report-card-subjects",
 *     summary="Get list of ReportCardSubjects",
 *     tags={"ReportCardSubjects"},
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
                          @OA\Property(property="report_card_id", type="integer", example=null),
                          @OA\Property(property="education_subject_id", type="integer", example=null),
                          @OA\Property(property="education_grade_id", type="integer", example=null),
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
 *     path="/api/v5/report-card-subjects",
 *     summary="Create a new ReportCardSubjects",
 *     tags={"ReportCardSubjects"},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             type="object",
                     @OA\Property(property="id", type="string", example=null),
                     @OA\Property(property="report_card_id", type="integer", example=null),
                     @OA\Property(property="education_subject_id", type="integer", example=null),
                     @OA\Property(property="education_grade_id", type="integer", example=null),
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
 *     path="/api/v5/report-card-subjects/report_card_id/{report_card_id}/education_subject_id/{education_subject_id}",
 *     summary="Get ReportCardSubjects record by composite key",
 *     tags={"ReportCardSubjects"},
 *     @OA\Parameter(
 *         name="report_card_id",
 *         in="path",
 *         required=true,
 *         description="report_card_id",
 *         @OA\Schema(type="string")
 *     ),
 *     @OA\Parameter(
 *         name="education_subject_id",
 *         in="path",
 *         required=true,
 *         description="education_subject_id",
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
 *     path="/api/v5/report-card-subjects/report_card_id/{report_card_id}/education_subject_id/{education_subject_id}",
 *     summary="Update ReportCardSubjects record by composite key",
 *     tags={"ReportCardSubjects"},
 *     @OA\Parameter(
 *         name="report_card_id",
 *         in="path",
 *         required=true,
 *         description="report_card_id",
 *         @OA\Schema(type="string")
 *     ),
 *     @OA\Parameter(
 *         name="education_subject_id",
 *         in="path",
 *         required=true,
 *         description="education_subject_id",
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
 *     path="/api/v5/report-card-subjects/report_card_id/{report_card_id}/education_subject_id/{education_subject_id}",
 *     summary="Delete ReportCardSubjects record by composite key",
 *     tags={"ReportCardSubjects"},
 *     @OA\Parameter(
 *         name="report_card_id",
 *         in="path",
 *         required=true,
 *         description="report_card_id",
 *         @OA\Schema(type="string")
 *     ),
 *     @OA\Parameter(
 *         name="education_subject_id",
 *         in="path",
 *         required=true,
 *         description="education_subject_id",
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
