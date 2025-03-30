<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InfrastructureProjectsNeeds extends Model
{
    use HasFactory;

    protected $table = 'infrastructure_projects_needs';

    // ✅ Allow mass assignment
    protected $fillable = ['id', 'infrastructure_project_id', 'infrastructure_need_id'];

    // ✅ Disable Laravel's default timestamps
    public $timestamps = false;

    // ✅ Treat 'modified' and 'created' as timestamps
    protected $dates = ['modified', 'created'];

    // ✅ Define the primary key
    protected $primaryKey = ['infrastructure_project_id', 'infrastructure_need_id'];
    public $incrementing = false;

     // Override getKeyForSaveQuery to handle composite keys
/**
 * @OA\PathItem(
 *     path="/api/v5/infrastructure-projects-needs"
 * )
 */
public function _swaggerPath() {}

/**
 * @OA\Get(
 *     path="/api/v5/infrastructure-projects-needs",
 *     summary="Get list of InfrastructureProjectsNeeds",
 *     tags={"InfrastructureProjectsNeeds"},
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
                          @OA\Property(property="infrastructure_project_id", type="integer", example=null),
                          @OA\Property(property="infrastructure_need_id", type="integer", example=null)
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
 *     path="/api/v5/infrastructure-projects-needs/{id}",
 *     summary="Get InfrastructureProjectsNeeds by ID",
 *     tags={"InfrastructureProjectsNeeds"},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="ID of the InfrastructureProjectsNeeds",
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
 *     path="/api/v5/infrastructure-projects-needs",
 *     summary="Create a new InfrastructureProjectsNeeds",
 *     tags={"InfrastructureProjectsNeeds"},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             type="object",
                     @OA\Property(property="id", type="string", example=null),
                     @OA\Property(property="infrastructure_project_id", type="integer", example=null),
                     @OA\Property(property="infrastructure_need_id", type="integer", example=null)
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
 *     path="/api/v5/infrastructure-projects-needs/{id}",
 *     summary="Update InfrastructureProjectsNeeds",
 *     tags={"InfrastructureProjectsNeeds"},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="ID of the InfrastructureProjectsNeeds",
 *         @OA\Schema(type="integer")
 *     ),
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             type="object",
                     @OA\Property(property="id", type="string", example=null),
                     @OA\Property(property="infrastructure_project_id", type="integer", example=null),
                     @OA\Property(property="infrastructure_need_id", type="integer", example=null)
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
 *     path="/api/v5/infrastructure-projects-needs/{id}",
 *     summary="Delete InfrastructureProjectsNeeds",
 *     tags={"InfrastructureProjectsNeeds"},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="ID of the InfrastructureProjectsNeeds",
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