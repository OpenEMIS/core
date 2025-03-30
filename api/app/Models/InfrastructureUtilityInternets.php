<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\InstitutionScope;

class InfrastructureUtilityInternets extends Model
{
    use HasFactory;
use InstitutionScope;

    protected $table = 'infrastructure_utility_internets';

    // ✅ Allow mass assignment
    protected $fillable = ['id', 'comment', 'internet_purpose', 'academic_period_id', 'institution_id', 'utility_internet_bandwidth_id', 'utility_internet_type_id', 'utility_internet_condition_id', 'modified_user_id', 'modified', 'created_user_id', 'created'];

    // ✅ Disable Laravel's default timestamps
    public $timestamps = false;

    // ✅ Treat 'modified' and 'created' as timestamps
    protected $dates = ['modified', 'created'];

    // ✅ Define the primary key
    
    

     // Override getKeyForSaveQuery to handle composite keys
/**
 * @OA\PathItem(
 *     path="/api/v5/infrastructure-utility-internets"
 * )
 */
public function _swaggerPath() {}

/**
 * @OA\Get(
 *     path="/api/v5/infrastructure-utility-internets",
 *     summary="Get list of InfrastructureUtilityInternets",
 *     tags={"InfrastructureUtilityInternets"},
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
                          @OA\Property(property="comment", type="string", example=null),
                          @OA\Property(property="internet_purpose", type="integer", example=null),
                          @OA\Property(property="academic_period_id", type="integer", example=null),
                          @OA\Property(property="institution_id", type="integer", example=null),
                          @OA\Property(property="utility_internet_bandwidth_id", type="integer", example=null),
                          @OA\Property(property="utility_internet_type_id", type="integer", example=null),
                          @OA\Property(property="utility_internet_condition_id", type="integer", example=null),
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
 *     path="/api/v5/infrastructure-utility-internets/{id}",
 *     summary="Get InfrastructureUtilityInternets by ID",
 *     tags={"InfrastructureUtilityInternets"},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="ID of the InfrastructureUtilityInternets",
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
 *     path="/api/v5/infrastructure-utility-internets",
 *     summary="Create a new InfrastructureUtilityInternets",
 *     tags={"InfrastructureUtilityInternets"},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             type="object",
                     @OA\Property(property="id", type="integer", example=null),
                     @OA\Property(property="comment", type="string", example=null),
                     @OA\Property(property="internet_purpose", type="integer", example=null),
                     @OA\Property(property="academic_period_id", type="integer", example=null),
                     @OA\Property(property="institution_id", type="integer", example=null),
                     @OA\Property(property="utility_internet_bandwidth_id", type="integer", example=null),
                     @OA\Property(property="utility_internet_type_id", type="integer", example=null),
                     @OA\Property(property="utility_internet_condition_id", type="integer", example=null),
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
 *     path="/api/v5/infrastructure-utility-internets/{id}",
 *     summary="Update InfrastructureUtilityInternets",
 *     tags={"InfrastructureUtilityInternets"},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="ID of the InfrastructureUtilityInternets",
 *         @OA\Schema(type="integer")
 *     ),
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             type="object",
                     @OA\Property(property="id", type="integer", example=null),
                     @OA\Property(property="comment", type="string", example=null),
                     @OA\Property(property="internet_purpose", type="integer", example=null),
                     @OA\Property(property="academic_period_id", type="integer", example=null),
                     @OA\Property(property="institution_id", type="integer", example=null),
                     @OA\Property(property="utility_internet_bandwidth_id", type="integer", example=null),
                     @OA\Property(property="utility_internet_type_id", type="integer", example=null),
                     @OA\Property(property="utility_internet_condition_id", type="integer", example=null),
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
 *     path="/api/v5/infrastructure-utility-internets/{id}",
 *     summary="Delete InfrastructureUtilityInternets",
 *     tags={"InfrastructureUtilityInternets"},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="ID of the InfrastructureUtilityInternets",
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