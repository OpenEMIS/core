<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ScholarshipsScholarshipAttachmentTypes extends Model
{
    use HasFactory;

    public $timestamps = false;

    // ✅ Allow mass assignment
    public $incrementing = false;

    // ✅ Disable Laravel's default timestamps
    protected $table = 'scholarships_scholarship_attachment_types';

    // ✅ Treat 'modified' and 'created' as timestamps
    protected $fillable = ['scholarship_id', 'scholarship_attachment_type_id', 'is_mandatory'];

    // ✅ Define the primary key
    protected $dates = ['modified', 'created'];
    protected $primaryKey = ['scholarship_id', 'scholarship_attachment_type_id'];

    // Override getKeyForSaveQuery to handle composite keys

    public static function getValidationRules(): array
    {
        return [
            // Add validation rules here
        ];
    }

    /**
     * @OA\PathItem(
     *     path="/api/v5/scholarships-scholarship-attachment-types"
     * )
     */
    public function _swaggerPath()
    {
    }

    /**
     * @OA\Get(
     *     path="/api/v5/scholarships-scholarship-attachment-types",
     *     summary="Get list of ScholarshipsScholarshipAttachmentTypes",
     *     tags={"ScholarshipsScholarshipAttachmentTypes"},
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
    @OA\Property(property="scholarship_id", type="integer", example=null),
    @OA\Property(property="scholarship_attachment_type_id", type="integer", example=null),
    @OA\Property(property="is_mandatory", type="integer", example=null)
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
    public function _swaggerList()
    {
    }

    /**
     * @OA\Get(
     *     path="/api/v5/scholarships-scholarship-attachment-types/{id}",
     *     summary="Get ScholarshipsScholarshipAttachmentTypes by ID",
     *     tags={"ScholarshipsScholarshipAttachmentTypes"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID of the ScholarshipsScholarshipAttachmentTypes",
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
    public function _swaggerView()
    {
    }

    /**
     * @OA\Post(
     *     path="/api/v5/scholarships-scholarship-attachment-types",
     *     summary="Create a new ScholarshipsScholarshipAttachmentTypes",
     *     tags={"ScholarshipsScholarshipAttachmentTypes"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
    @OA\Property(property="scholarship_id", type="integer", example=null),
    @OA\Property(property="scholarship_attachment_type_id", type="integer", example=null),
    @OA\Property(property="is_mandatory", type="integer", example=null)
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
    public function _swaggerCreate()
    {
    }

    /**
     * @OA\Put(
     *     path="/api/v5/scholarships-scholarship-attachment-types/{id}",
     *     summary="Update ScholarshipsScholarshipAttachmentTypes",
     *     tags={"ScholarshipsScholarshipAttachmentTypes"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID of the ScholarshipsScholarshipAttachmentTypes",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
    @OA\Property(property="scholarship_id", type="integer", example=null),
    @OA\Property(property="scholarship_attachment_type_id", type="integer", example=null),
    @OA\Property(property="is_mandatory", type="integer", example=null)
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
    public function _swaggerUpdate()
    {
    }

    /**
     * @OA\Delete(
     *     path="/api/v5/scholarships-scholarship-attachment-types/{id}",
     *     summary="Delete ScholarshipsScholarshipAttachmentTypes",
     *     tags={"ScholarshipsScholarshipAttachmentTypes"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID of the ScholarshipsScholarshipAttachmentTypes",
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
    public function _swaggerDelete()
    {
    }

    // Override setKeysForSaveQuery to handle composite keys

    public function _swaggerHelper()
    {
        return;
    }

    protected function getKeyForSaveQuery()
    {
        $query = $this->newQueryWithoutScopes();
        $keyName = $this->getKeyName();
        if (!is_array($keyName)) {
            $keyName = [$keyName];;
        }
        foreach ($keyName as $key) {
            $query->where($key, '=', $this->getAttribute($key));
        }

        return $query;
    }

    protected function setKeysForSaveQuery($query)
    {
        $keyName = $this->getKeyName();
        if (!is_array($keyName)) {
            $keyName = [$keyName];;
        }
        foreach ($keyName as $key) {
            $query->where($key, '=', $this->getAttribute($key));
        }

        return $query;
    }
}
