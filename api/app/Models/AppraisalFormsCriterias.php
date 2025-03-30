<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AppraisalFormsCriterias extends Model
{
    use HasFactory;

    protected $table = 'appraisal_forms_criterias';

    // ✅ Allow mass assignment
    protected $fillable = ['id', 'appraisal_form_id', 'appraisal_criteria_id', 'section', 'is_mandatory', 'order'];

    // ✅ Disable Laravel's default timestamps
    public $timestamps = false;

    // ✅ Treat 'modified' and 'created' as timestamps
    protected $dates = ['modified', 'created'];

    // ✅ Define the primary key
    protected $primaryKey = ['appraisal_form_id', 'appraisal_criteria_id'];
    public $incrementing = false;

     // Override getKeyForSaveQuery to handle composite keys
/**
 * @OA\PathItem(
 *     path="/api/v5/appraisal-forms-criterias"
 * )
 */
public function _swaggerPath() {}

/**
 * @OA\Get(
 *     path="/api/v5/appraisal-forms-criterias",
 *     summary="Get list of AppraisalFormsCriterias",
 *     tags={"AppraisalFormsCriterias"},
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
                          @OA\Property(property="appraisal_form_id", type="integer", example=null),
                          @OA\Property(property="appraisal_criteria_id", type="integer", example=null),
                          @OA\Property(property="section", type="string", example=null),
                          @OA\Property(property="is_mandatory", type="integer", example=null),
                          @OA\Property(property="order", type="integer", example=null)
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
 *     path="/api/v5/appraisal-forms-criterias/{id}",
 *     summary="Get AppraisalFormsCriterias by ID",
 *     tags={"AppraisalFormsCriterias"},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="ID of the AppraisalFormsCriterias",
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
 *     path="/api/v5/appraisal-forms-criterias",
 *     summary="Create a new AppraisalFormsCriterias",
 *     tags={"AppraisalFormsCriterias"},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             type="object",
                     @OA\Property(property="id", type="string", example=null),
                     @OA\Property(property="appraisal_form_id", type="integer", example=null),
                     @OA\Property(property="appraisal_criteria_id", type="integer", example=null),
                     @OA\Property(property="section", type="string", example=null),
                     @OA\Property(property="is_mandatory", type="integer", example=null),
                     @OA\Property(property="order", type="integer", example=null)
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
 *     path="/api/v5/appraisal-forms-criterias/{id}",
 *     summary="Update AppraisalFormsCriterias",
 *     tags={"AppraisalFormsCriterias"},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="ID of the AppraisalFormsCriterias",
 *         @OA\Schema(type="integer")
 *     ),
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             type="object",
                     @OA\Property(property="id", type="string", example=null),
                     @OA\Property(property="appraisal_form_id", type="integer", example=null),
                     @OA\Property(property="appraisal_criteria_id", type="integer", example=null),
                     @OA\Property(property="section", type="string", example=null),
                     @OA\Property(property="is_mandatory", type="integer", example=null),
                     @OA\Property(property="order", type="integer", example=null)
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
 *     path="/api/v5/appraisal-forms-criterias/{id}",
 *     summary="Delete AppraisalFormsCriterias",
 *     tags={"AppraisalFormsCriterias"},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="ID of the AppraisalFormsCriterias",
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