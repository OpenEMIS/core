<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TrainingCourses extends Model
{
    use HasFactory;

    protected $table = 'training_courses';

    // ✅ Allow mass assignment
    protected $fillable = ['id', 'code', 'name', 'description', 'objective', 'credit_hours', 'duration', 'number_of_months', 'special_education_needs', 'file_name', 'file_content', 'training_field_of_study_id', 'training_course_type_id', 'training_course_category_id', 'training_mode_of_delivery_id', 'training_requirement_id', 'training_level_id', 'assignee_id', 'status_id', 'modified_user_id', 'modified', 'created_user_id', 'created'];

    // ✅ Disable Laravel's default timestamps
    public $timestamps = false;

    // ✅ Treat 'modified' and 'created' as timestamps
    protected $dates = ['modified', 'created'];

    // ✅ Define the primary key
    
    

     // Override getKeyForSaveQuery to handle composite keys
/**
 * @OA\PathItem(
 *     path="/api/v5/training-courses"
 * )
 */
public function _swaggerPath() {}

/**
 * @OA\Get(
 *     path="/api/v5/training-courses",
 *     summary="Get list of TrainingCourses",
 *     tags={"TrainingCourses"},
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
                          @OA\Property(property="objective", type="string", example=null),
                          @OA\Property(property="credit_hours", type="integer", example=null),
                          @OA\Property(property="duration", type="integer", example=null),
                          @OA\Property(property="number_of_months", type="integer", example=null),
                          @OA\Property(property="special_education_needs", type="integer", example=null),
                          @OA\Property(property="file_name", type="string", example=null),
                          @OA\Property(property="file_content", type="string", example=null),
                          @OA\Property(property="training_field_of_study_id", type="integer", example=null),
                          @OA\Property(property="training_course_type_id", type="integer", example=null),
                          @OA\Property(property="training_course_category_id", type="integer", example=null),
                          @OA\Property(property="training_mode_of_delivery_id", type="integer", example=null),
                          @OA\Property(property="training_requirement_id", type="integer", example=null),
                          @OA\Property(property="training_level_id", type="integer", example=null),
                          @OA\Property(property="assignee_id", type="integer", example=null),
                          @OA\Property(property="status_id", type="integer", example=null),
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
 * @OA\Get(
 *     path="/api/v5/training-courses/{id}",
 *     summary="Get TrainingCourses by ID",
 *     tags={"TrainingCourses"},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="ID of the TrainingCourses",
 *         @OA\Schema(type="integer")
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
 * @OA\Post(
 *     path="/api/v5/training-courses",
 *     summary="Create a new TrainingCourses",
 *     tags={"TrainingCourses"},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             type="object",
                     @OA\Property(property="id", type="integer", example=null),
                     @OA\Property(property="code", type="string", example=null),
                     @OA\Property(property="name", type="string", example=null),
                     @OA\Property(property="description", type="string", example=null),
                     @OA\Property(property="objective", type="string", example=null),
                     @OA\Property(property="credit_hours", type="integer", example=null),
                     @OA\Property(property="duration", type="integer", example=null),
                     @OA\Property(property="number_of_months", type="integer", example=null),
                     @OA\Property(property="special_education_needs", type="integer", example=null),
                     @OA\Property(property="file_name", type="string", example=null),
                     @OA\Property(property="file_content", type="string", example=null),
                     @OA\Property(property="training_field_of_study_id", type="integer", example=null),
                     @OA\Property(property="training_course_type_id", type="integer", example=null),
                     @OA\Property(property="training_course_category_id", type="integer", example=null),
                     @OA\Property(property="training_mode_of_delivery_id", type="integer", example=null),
                     @OA\Property(property="training_requirement_id", type="integer", example=null),
                     @OA\Property(property="training_level_id", type="integer", example=null),
                     @OA\Property(property="assignee_id", type="integer", example=null),
                     @OA\Property(property="status_id", type="integer", example=null),
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
 * @OA\Put(
 *     path="/api/v5/training-courses/{id}",
 *     summary="Update TrainingCourses",
 *     tags={"TrainingCourses"},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="ID of the TrainingCourses",
 *         @OA\Schema(type="integer")
 *     ),
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             type="object",
                     @OA\Property(property="id", type="integer", example=null),
                     @OA\Property(property="code", type="string", example=null),
                     @OA\Property(property="name", type="string", example=null),
                     @OA\Property(property="description", type="string", example=null),
                     @OA\Property(property="objective", type="string", example=null),
                     @OA\Property(property="credit_hours", type="integer", example=null),
                     @OA\Property(property="duration", type="integer", example=null),
                     @OA\Property(property="number_of_months", type="integer", example=null),
                     @OA\Property(property="special_education_needs", type="integer", example=null),
                     @OA\Property(property="file_name", type="string", example=null),
                     @OA\Property(property="file_content", type="string", example=null),
                     @OA\Property(property="training_field_of_study_id", type="integer", example=null),
                     @OA\Property(property="training_course_type_id", type="integer", example=null),
                     @OA\Property(property="training_course_category_id", type="integer", example=null),
                     @OA\Property(property="training_mode_of_delivery_id", type="integer", example=null),
                     @OA\Property(property="training_requirement_id", type="integer", example=null),
                     @OA\Property(property="training_level_id", type="integer", example=null),
                     @OA\Property(property="assignee_id", type="integer", example=null),
                     @OA\Property(property="status_id", type="integer", example=null),
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
 *     path="/api/v5/training-courses/{id}",
 *     summary="Delete TrainingCourses",
 *     tags={"TrainingCourses"},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="ID of the TrainingCourses",
 *         @OA\Schema(type="integer")
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




    public function _swaggerHelper() {
        return;
    }
}