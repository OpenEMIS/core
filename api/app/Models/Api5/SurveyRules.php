<?php

namespace App\Models\Api5;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\UuidId;

class SurveyRules extends Model
{
    use UuidId;

    use HasFactory;
    // ✅ Allow mass assignment
    protected $fillable = ['id', 'survey_form_id', 'survey_question_id', 'dependent_question_id', 'show_options', 'enabled', 'modified', 'modified_user_id', 'created', 'created_user_id', 'survey_form_id', 'survey_question_id', 'dependent_question_id', 'modified_user_id', 'created_user_id'];
    // ✅ Treat 'modified' and 'created' as timestamps
    protected $dates = ['modified', 'created'];

    public $timestamps = false;
    protected $table = "survey_rules";
    protected $primaryKey = "id";
    public $incrementing = false;
    protected $keyType = "string";

    protected static function boot()
    {
        parent::boot();
        self::bootUuidId();

    }
/**
 * @OA\PathItem(
 *     path="/api/v5/survey-rules"
 * )
 */
public function _swaggerPath() {}

/**
 * @OA\Get(
 *     path="/api/v5/survey-rules",
 *     summary="Get list of SurveyRules",
 *     tags={"SurveyRules"},
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
                          @OA\Property(property="survey_form_id", type="integer", example=null),
                          @OA\Property(property="survey_question_id", type="integer", example=null),
                          @OA\Property(property="dependent_question_id", type="integer", example=null),
                          @OA\Property(property="show_options", type="string", example=null),
                          @OA\Property(property="enabled", type="integer", example=null),
                          @OA\Property(property="modified", type="string", format="date-time", example=null),
                          @OA\Property(property="modified_user_id", type="integer", example=null),
                          @OA\Property(property="created", type="string", format="date-time", example=null),
                          @OA\Property(property="created_user_id", type="integer", example=null)
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
 *     path="/api/v5/survey-rules",
 *     summary="Create a new SurveyRules",
 *     tags={"SurveyRules"},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             type="object",
                     @OA\Property(property="id", type="string", example=null),
                     @OA\Property(property="survey_form_id", type="integer", example=null),
                     @OA\Property(property="survey_question_id", type="integer", example=null),
                     @OA\Property(property="dependent_question_id", type="integer", example=null),
                     @OA\Property(property="show_options", type="string", example=null),
                     @OA\Property(property="enabled", type="integer", example=null),
                     @OA\Property(property="modified", type="string", format="date-time", example=null),
                     @OA\Property(property="modified_user_id", type="integer", example=null),
                     @OA\Property(property="created", type="string", format="date-time", example=null),
                     @OA\Property(property="created_user_id", type="integer", example=null)
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
 *     path="/api/v5/survey-rules/{id}",
 *     summary="Get SurveyRules by ID",
 *     tags={"SurveyRules"},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="ID of the SurveyRules",
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
 *     path="/api/v5/survey-rules/{id}",
 *     summary="Update SurveyRules",
 *     tags={"SurveyRules"},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="ID of the SurveyRules",
 *         @OA\Schema(type="string")
 *     ),
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             type="object",
                     @OA\Property(property="id", type="string", example=null),
                     @OA\Property(property="survey_form_id", type="integer", example=null),
                     @OA\Property(property="survey_question_id", type="integer", example=null),
                     @OA\Property(property="dependent_question_id", type="integer", example=null),
                     @OA\Property(property="show_options", type="string", example=null),
                     @OA\Property(property="enabled", type="integer", example=null),
                     @OA\Property(property="modified", type="string", format="date-time", example=null),
                     @OA\Property(property="modified_user_id", type="integer", example=null),
                     @OA\Property(property="created", type="string", format="date-time", example=null),
                     @OA\Property(property="created_user_id", type="integer", example=null)
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
 *     path="/api/v5/survey-rules/{id}",
 *     summary="Delete SurveyRules",
 *     tags={"SurveyRules"},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="ID of the SurveyRules",
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
public function _swaggerHelper(){}

}
