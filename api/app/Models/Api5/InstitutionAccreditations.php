<?php

namespace App\Models\Api5;

use App\Models\Api5\SecurityUsers;
use App\Traits\InstitutionScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InstitutionAccreditations extends Model
{
    use HasFactory;
    use InstitutionScope;

    protected $table = 'institution_accreditations';

    //POCOR-9610: start - API v5 accreditation payload fields from external registrations integration
    protected $fillable = [
        'id',
        'institution_id',
        'programme_name',
        'programme_code',
        'qualification_level',
        'status',
        'valid_from',
        'valid_to',
        'external_id',
        'modified_user_id',
        'modified',
        'created_user_id',
        'created',
    ];
    //POCOR-9610: end

    //POCOR-9610: start - Cast imported accreditation lifecycle dates for CRUD responses
    protected $dates = ['valid_from', 'valid_to', 'modified', 'created'];
    //POCOR-9610: end

    public $timestamps = false;

    //POCOR-9610: start - Bind the Api5 model to the existing root Database\Factories namespace
    protected static function newFactory()
    {
        return \Database\Factories\InstitutionAccreditationsFactory::new();
    }
    //POCOR-9610: end

    /**
     * @OA\PathItem(
     *     path="/api/v5/institution-accreditations"
     * )
     */
    public function _swaggerPath() {}

    /**
     * @OA\Get(
     *     path="/api/v5/institution-accreditations",
     *     summary="Get list of InstitutionAccreditations",
     *     tags={"InstitutionAccreditations"},
     *     @OA\Response(response=200, description="Successful operation"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function _swaggerList() {}

    /**
     * @OA\Post(
     *     path="/api/v5/institution-accreditations",
     *     summary="Create a new InstitutionAccreditations",
     *     tags={"InstitutionAccreditations"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="institution_id", type="integer", example=6),
     *             @OA\Property(property="programme_name", type="string", example="Diploma Electrical"),
     *             @OA\Property(property="programme_code", type="string", example="DE-101"),
     *             @OA\Property(property="qualification_level", type="string", example="Diploma"),
     *             @OA\Property(property="status", type="string", example="active"),
     *             @OA\Property(property="valid_from", type="string", format="date", example="2024-01-01"),
     *             @OA\Property(property="valid_to", type="string", format="date", example="2027-01-01"),
     *             @OA\Property(property="external_id", type="string", example="ACC-PROG-000123")
     *         )
     *     ),
     *     @OA\Response(response=201, description="Created successfully"),
     *     @OA\Response(response=400, description="Invalid data"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function _swaggerCreate() {}

    /**
     * @OA\Get(
     *     path="/api/v5/institution-accreditations/{id}",
     *     summary="Get InstitutionAccreditations by ID",
     *     tags={"InstitutionAccreditations"},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Response(response=200, description="Successful operation"),
     *     @OA\Response(response=404, description="Not found")
     * )
     */
    public function _swaggerView() {}

    /**
     * @OA\Put(
     *     path="/api/v5/institution-accreditations/{id}",
     *     summary="Update InstitutionAccreditations",
     *     tags={"InstitutionAccreditations"},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="string", example="revoked"),
     *             @OA\Property(property="valid_from", type="string", format="date", example="2024-01-01"),
     *             @OA\Property(property="valid_to", type="string", format="date", example="2027-01-01")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Updated successfully"),
     *     @OA\Response(response=400, description="Invalid data"),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=404, description="Not found")
     * )
     */
    public function _swaggerUpdate() {}

    /**
     * @OA\Delete(
     *     path="/api/v5/institution-accreditations/{id}",
     *     summary="Delete InstitutionAccreditations",
     *     tags={"InstitutionAccreditations"},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Response(response=204, description="Deleted successfully"),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=404, description="Not found")
     * )
     */
    public function _swaggerDelete() {}

    //POCOR-9610: start - Expose institution and audit-user relations for shared CRUD responses
    public function institution()
    {
        return $this->belongsTo(Institutions::class, 'institution_id', 'id');
    }

    public function modifiedUser()
    {
        return $this->belongsTo(SecurityUsers::class, 'modified_user_id', 'id');
    }

    public function createdUser()
    {
        return $this->belongsTo(SecurityUsers::class, 'created_user_id', 'id');
    }
    //POCOR-9610: end
}
