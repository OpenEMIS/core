<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StaffTrainingNeeds extends Model
{
    use HasFactory;

    protected $table = 'staff_training_needs';

    // ✅ Allow mass assignment
    protected $fillable = ['id', 'reason', 'type', 'training_course_id', 'training_need_category_id', 'training_need_competency_id', 'training_need_sub_standard_id', 'training_priority_id', 'staff_id', 'assignee_id', 'status_id', 'modified_user_id', 'modified', 'created_user_id', 'created'];

    // ✅ Disable Laravel's default timestamps
    public $timestamps = false;

    // ✅ Treat 'modified' and 'created' as timestamps
    protected $dates = ['modified', 'created'];

    // ✅ Define the primary key
    
    

     // Override getKeyForSaveQuery to handle composite keys
/**
 * @OA\PathItem(
 *     path="/api/v5/staff-training-needs"
 * )
 */
public function _swaggerPath() {}

/**
 * @OA\Get(
 *     path="/api/v5/staff-training-needs",
 *     summary="Get list of StaffTrainingNeeds",
 *     tags={"StaffTrainingNeeds"},
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
                          @OA\Property(property="reason", type="string", example=null),
                          @OA\Property(property="type", type="string", example=null),
                          @OA\Property(property="training_course_id", type="integer", example=null),
                          @OA\Property(property="training_need_category_id", type="integer", example=null),
                          @OA\Property(property="training_need_competency_id", type="integer", example=null),
                          @OA\Property(property="training_need_sub_standard_id", type="integer", example=null),
                          @OA\Property(property="training_priority_id", type="integer", example=null),
                          @OA\Property(property="staff_id", type="integer", example=null),
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
 *     path="/api/v5/staff-training-needs/{id}",
 *     summary="Get StaffTrainingNeeds by ID",
 *     tags={"StaffTrainingNeeds"},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="ID of the StaffTrainingNeeds",
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
 *     path="/api/v5/staff-training-needs",
 *     summary="Create a new StaffTrainingNeeds",
 *     tags={"StaffTrainingNeeds"},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             type="object",
                     @OA\Property(property="id", type="integer", example=null),
                     @OA\Property(property="reason", type="string", example=null),
                     @OA\Property(property="type", type="string", example=null),
                     @OA\Property(property="training_course_id", type="integer", example=null),
                     @OA\Property(property="training_need_category_id", type="integer", example=null),
                     @OA\Property(property="training_need_competency_id", type="integer", example=null),
                     @OA\Property(property="training_need_sub_standard_id", type="integer", example=null),
                     @OA\Property(property="training_priority_id", type="integer", example=null),
                     @OA\Property(property="staff_id", type="integer", example=null),
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
 *     path="/api/v5/staff-training-needs/{id}",
 *     summary="Update StaffTrainingNeeds",
 *     tags={"StaffTrainingNeeds"},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="ID of the StaffTrainingNeeds",
 *         @OA\Schema(type="integer")
 *     ),
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             type="object",
                     @OA\Property(property="id", type="integer", example=null),
                     @OA\Property(property="reason", type="string", example=null),
                     @OA\Property(property="type", type="string", example=null),
                     @OA\Property(property="training_course_id", type="integer", example=null),
                     @OA\Property(property="training_need_category_id", type="integer", example=null),
                     @OA\Property(property="training_need_competency_id", type="integer", example=null),
                     @OA\Property(property="training_need_sub_standard_id", type="integer", example=null),
                     @OA\Property(property="training_priority_id", type="integer", example=null),
                     @OA\Property(property="staff_id", type="integer", example=null),
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
 *     path="/api/v5/staff-training-needs/{id}",
 *     summary="Delete StaffTrainingNeeds",
 *     tags={"StaffTrainingNeeds"},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="ID of the StaffTrainingNeeds",
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