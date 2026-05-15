<?php

namespace App\Models\Api5;

use App\Models\Api5\SecurityUsers;
use App\Traits\InstitutionScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

//POCOR-9610: API v5 model for institution_accreditations
class InstitutionAccreditations extends Model
{
    use HasFactory;
    use InstitutionScope;

    protected $table = 'institution_accreditations';

    protected $fillable = [
        'id',
        'institution_id',
        'education_programme_id',
        'valid_from',
        'valid_to',
        'modified_user_id',
        'modified',
        'created_user_id',
        'created',
    ];

    protected $dates = ['valid_from', 'valid_to', 'modified', 'created'];

    public $timestamps = false;

    protected static function newFactory()
    {
        return \Database\Factories\InstitutionAccreditationsFactory::new();
    }

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
     *             @OA\Property(property="education_programme_id", type="integer", example=9),
     *             @OA\Property(property="valid_from", type="string", format="date", example="2024-01-01"),
     *             @OA\Property(property="valid_to", type="string", format="date", example="2026-12-31")
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
     *             @OA\Property(property="education_programme_id", type="integer", example=9),
     *             @OA\Property(property="valid_from", type="string", format="date", example="2025-01-01"),
     *             @OA\Property(property="valid_to", type="string", format="date", example="2027-12-31")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Updated successfully"),
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

    public function institution()
    {
        return $this->belongsTo(Institutions::class, 'institution_id', 'id');
    }

    public function educationProgramme()
    {
        return $this->belongsTo(EducationProgrammes::class, 'education_programme_id', 'id');
    }

    public function modifiedUser()
    {
        return $this->belongsTo(SecurityUsers::class, 'modified_user_id', 'id');
    }

    public function createdUser()
    {
        return $this->belongsTo(SecurityUsers::class, 'created_user_id', 'id');
    }
}
