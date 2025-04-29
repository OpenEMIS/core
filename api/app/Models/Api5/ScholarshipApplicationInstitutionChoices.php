<?php

namespace App\Models\Api5;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ScholarshipApplicationInstitutionChoices extends Model
{
    use HasFactory;

    protected $table = 'scholarship_application_institution_choices';

    // ✅ Allow mass assignment
    protected $fillable = ['id', 'location_type', 'scholarship_institution_choice_type_id', 'estimated_cost', 'course_name', 'start_date', 'end_date', 'is_selected', 'order', 'country_id', 'scholarship_institution_choice_status_id', 'education_field_of_study_id', 'qualification_level_id', 'applicant_id', 'scholarship_id', 'modified_user_id', 'modified', 'created_user_id', 'created'];

    // ✅ Disable Laravel's default timestamps
    public $timestamps = false;

    // ✅ Treat 'modified' and 'created' as timestamps
    protected $dates = ['modified', 'created'];

    // ✅ Define the primary key


     // Override getKeyForSaveQuery to handle composite keys


/**
 * @OA\PathItem(
 *     path="/api/v5/scholarship-application-institution-choices"
 * )
 */
public function _swaggerPath() {}

/**
 * @OA\Get(
 *     path="/api/v5/scholarship-application-institution-choices",
 *     summary="Get list of ScholarshipApplicationInstitutionChoices",
 *     tags={"ScholarshipApplicationInstitutionChoices"},
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
                          @OA\Property(property="location_type", type="string", example=null),
                          @OA\Property(property="scholarship_institution_choice_type_id", type="integer", example=null),
                          @OA\Property(property="estimated_cost", type="number", example=null),
                          @OA\Property(property="course_name", type="string", example=null),
                          @OA\Property(property="start_date", type="string", format="date", example=null),
                          @OA\Property(property="end_date", type="string", format="date", example=null),
                          @OA\Property(property="is_selected", type="integer", example=null),
                          @OA\Property(property="order", type="integer", example=null),
                          @OA\Property(property="country_id", type="integer", example=null),
                          @OA\Property(property="scholarship_institution_choice_status_id", type="integer", example=null),
                          @OA\Property(property="education_field_of_study_id", type="integer", example=null),
                          @OA\Property(property="qualification_level_id", type="integer", example=null),
                          @OA\Property(property="applicant_id", type="integer", example=null),
                          @OA\Property(property="scholarship_id", type="integer", example=null),
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
 *     path="/api/v5/scholarship-application-institution-choices",
 *     summary="Create a new ScholarshipApplicationInstitutionChoices",
 *     tags={"ScholarshipApplicationInstitutionChoices"},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             type="object",
                     @OA\Property(property="id", type="integer", example=null),
                     @OA\Property(property="location_type", type="string", example=null),
                     @OA\Property(property="scholarship_institution_choice_type_id", type="integer", example=null),
                     @OA\Property(property="estimated_cost", type="number", example=null),
                     @OA\Property(property="course_name", type="string", example=null),
                     @OA\Property(property="start_date", type="string", format="date", example=null),
                     @OA\Property(property="end_date", type="string", format="date", example=null),
                     @OA\Property(property="is_selected", type="integer", example=null),
                     @OA\Property(property="order", type="integer", example=null),
                     @OA\Property(property="country_id", type="integer", example=null),
                     @OA\Property(property="scholarship_institution_choice_status_id", type="integer", example=null),
                     @OA\Property(property="education_field_of_study_id", type="integer", example=null),
                     @OA\Property(property="qualification_level_id", type="integer", example=null),
                     @OA\Property(property="applicant_id", type="integer", example=null),
                     @OA\Property(property="scholarship_id", type="integer", example=null),
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
 *     path="/api/v5/scholarship-application-institution-choices/{id}",
 *     summary="Get ScholarshipApplicationInstitutionChoices by ID",
 *     tags={"ScholarshipApplicationInstitutionChoices"},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="ID of the ScholarshipApplicationInstitutionChoices",
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
 *     path="/api/v5/scholarship-application-institution-choices/{id}",
 *     summary="Update ScholarshipApplicationInstitutionChoices",
 *     tags={"ScholarshipApplicationInstitutionChoices"},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="ID of the ScholarshipApplicationInstitutionChoices",
 *         @OA\Schema(type="string")
 *     ),
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             type="object",
                     @OA\Property(property="id", type="integer", example=null),
                     @OA\Property(property="location_type", type="string", example=null),
                     @OA\Property(property="scholarship_institution_choice_type_id", type="integer", example=null),
                     @OA\Property(property="estimated_cost", type="number", example=null),
                     @OA\Property(property="course_name", type="string", example=null),
                     @OA\Property(property="start_date", type="string", format="date", example=null),
                     @OA\Property(property="end_date", type="string", format="date", example=null),
                     @OA\Property(property="is_selected", type="integer", example=null),
                     @OA\Property(property="order", type="integer", example=null),
                     @OA\Property(property="country_id", type="integer", example=null),
                     @OA\Property(property="scholarship_institution_choice_status_id", type="integer", example=null),
                     @OA\Property(property="education_field_of_study_id", type="integer", example=null),
                     @OA\Property(property="qualification_level_id", type="integer", example=null),
                     @OA\Property(property="applicant_id", type="integer", example=null),
                     @OA\Property(property="scholarship_id", type="integer", example=null),
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
 *     path="/api/v5/scholarship-application-institution-choices/{id}",
 *     summary="Delete ScholarshipApplicationInstitutionChoices",
 *     tags={"ScholarshipApplicationInstitutionChoices"},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="ID of the ScholarshipApplicationInstitutionChoices",
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
