<?php

namespace App\Models\Api5;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InstitutionRepeaterSurveyTableCells extends Model
{
    use HasFactory;

    protected $table = 'institution_repeater_survey_table_cells';

    // ✅ Allow mass assignment
    protected $fillable = ['text_value', 'number_value', 'decimal_value', 'survey_question_id', 'survey_table_column_id', 'survey_table_row_id', 'institution_repeater_survey_id', 'modified_user_id', 'modified', 'created_user_id', 'created'];

    // ✅ Disable Laravel's default timestamps
    public $timestamps = false;

    // ✅ Treat 'modified' and 'created' as timestamps
    protected $dates = ['modified', 'created'];

    // ✅ Define the primary key
    protected $primaryKey = ['survey_question_id', 'survey_table_column_id', 'survey_table_row_id', 'institution_repeater_survey_id'];
    public $incrementing = false;

     // Override getKeyForSaveQuery to handle composite keys


/**
 * @OA\PathItem(
 *     path="/api/v5/institution-repeater-survey-table-cells"
 * )
 */
public function _swaggerPath() {}

/**
 * @OA\Get(
 *     path="/api/v5/institution-repeater-survey-table-cells",
 *     summary="Get list of InstitutionRepeaterSurveyTableCells",
 *     tags={"InstitutionRepeaterSurveyTableCells"},
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
                          @OA\Property(property="text_value", type="string", example=null),
                          @OA\Property(property="number_value", type="integer", example=null),
                          @OA\Property(property="decimal_value", type="string", example=null),
                          @OA\Property(property="survey_question_id", type="integer", example=null),
                          @OA\Property(property="survey_table_column_id", type="integer", example=null),
                          @OA\Property(property="survey_table_row_id", type="integer", example=null),
                          @OA\Property(property="institution_repeater_survey_id", type="integer", example=null),
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
 *     path="/api/v5/institution-repeater-survey-table-cells",
 *     summary="Create a new InstitutionRepeaterSurveyTableCells",
 *     tags={"InstitutionRepeaterSurveyTableCells"},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             type="object",
                     @OA\Property(property="text_value", type="string", example=null),
                     @OA\Property(property="number_value", type="integer", example=null),
                     @OA\Property(property="decimal_value", type="string", example=null),
                     @OA\Property(property="survey_question_id", type="integer", example=null),
                     @OA\Property(property="survey_table_column_id", type="integer", example=null),
                     @OA\Property(property="survey_table_row_id", type="integer", example=null),
                     @OA\Property(property="institution_repeater_survey_id", type="integer", example=null),
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
 *     path="/api/v5/institution-repeater-survey-table-cells/survey_question_id/{survey_question_id}/survey_table_column_id/{survey_table_column_id}/survey_table_row_id/{survey_table_row_id}/institution_repeater_survey_id/{institution_repeater_survey_id}",
 *     summary="Get InstitutionRepeaterSurveyTableCells record by composite key",
 *     tags={"InstitutionRepeaterSurveyTableCells"},
 *     @OA\Parameter(
 *         name="survey_question_id",
 *         in="path",
 *         required=true,
 *         description="survey_question_id",
 *         @OA\Schema(type="string")
 *     ),
 *     @OA\Parameter(
 *         name="survey_table_column_id",
 *         in="path",
 *         required=true,
 *         description="survey_table_column_id",
 *         @OA\Schema(type="string")
 *     ),
 *     @OA\Parameter(
 *         name="survey_table_row_id",
 *         in="path",
 *         required=true,
 *         description="survey_table_row_id",
 *         @OA\Schema(type="string")
 *     ),
 *     @OA\Parameter(
 *         name="institution_repeater_survey_id",
 *         in="path",
 *         required=true,
 *         description="institution_repeater_survey_id",
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
 *     path="/api/v5/institution-repeater-survey-table-cells/survey_question_id/{survey_question_id}/survey_table_column_id/{survey_table_column_id}/survey_table_row_id/{survey_table_row_id}/institution_repeater_survey_id/{institution_repeater_survey_id}",
 *     summary="Update InstitutionRepeaterSurveyTableCells record by composite key",
 *     tags={"InstitutionRepeaterSurveyTableCells"},
 *     @OA\Parameter(
 *         name="survey_question_id",
 *         in="path",
 *         required=true,
 *         description="survey_question_id",
 *         @OA\Schema(type="string")
 *     ),
 *     @OA\Parameter(
 *         name="survey_table_column_id",
 *         in="path",
 *         required=true,
 *         description="survey_table_column_id",
 *         @OA\Schema(type="string")
 *     ),
 *     @OA\Parameter(
 *         name="survey_table_row_id",
 *         in="path",
 *         required=true,
 *         description="survey_table_row_id",
 *         @OA\Schema(type="string")
 *     ),
 *     @OA\Parameter(
 *         name="institution_repeater_survey_id",
 *         in="path",
 *         required=true,
 *         description="institution_repeater_survey_id",
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
 *     path="/api/v5/institution-repeater-survey-table-cells/survey_question_id/{survey_question_id}/survey_table_column_id/{survey_table_column_id}/survey_table_row_id/{survey_table_row_id}/institution_repeater_survey_id/{institution_repeater_survey_id}",
 *     summary="Delete InstitutionRepeaterSurveyTableCells record by composite key",
 *     tags={"InstitutionRepeaterSurveyTableCells"},
 *     @OA\Parameter(
 *         name="survey_question_id",
 *         in="path",
 *         required=true,
 *         description="survey_question_id",
 *         @OA\Schema(type="string")
 *     ),
 *     @OA\Parameter(
 *         name="survey_table_column_id",
 *         in="path",
 *         required=true,
 *         description="survey_table_column_id",
 *         @OA\Schema(type="string")
 *     ),
 *     @OA\Parameter(
 *         name="survey_table_row_id",
 *         in="path",
 *         required=true,
 *         description="survey_table_row_id",
 *         @OA\Schema(type="string")
 *     ),
 *     @OA\Parameter(
 *         name="institution_repeater_survey_id",
 *         in="path",
 *         required=true,
 *         description="institution_repeater_survey_id",
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
