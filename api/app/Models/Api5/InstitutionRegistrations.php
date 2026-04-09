<?php

namespace App\Models\Api5;

use App\Traits\InstitutionScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InstitutionRegistrations extends Model
{
    use HasFactory;
    use InstitutionScope;

    protected $table = 'institution_registrations';

    protected $fillable = [
        'id',
        'institution_id',
        'external_id',
        'status',
        'approved_date',
        'valid_from',
        'valid_to',
        'decision_reference',
        'modified_user_id',
        'modified',
        'created_user_id',
        'created',
    ];

    protected $dates = ['approved_date', 'valid_from', 'valid_to', 'modified', 'created'];

    public $timestamps = false;

    protected static function newFactory()
    {
        return \Database\Factories\InstitutionRegistrationsFactory::new();
    }

    /**
     * @OA\PathItem(
     *     path="/api/v5/institution-registrations"
     * )
     */
    public function _swaggerPath() {}

    /**
     * @OA\Get(
     *     path="/api/v5/institution-registrations",
     *     summary="Get list of InstitutionRegistrations",
     *     tags={"InstitutionRegistrations"},
     *     @OA\Response(response=200, description="Successful operation"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function _swaggerList() {}

    /**
     * @OA\Post(
     *     path="/api/v5/institution-registrations",
     *     summary="Create a new InstitutionRegistrations",
     *     tags={"InstitutionRegistrations"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="institution_id", type="integer", example=6),
     *             @OA\Property(property="external_id", type="string", example="ACC-INS-000123"),
     *             @OA\Property(property="status", type="string", example="active"),
     *             @OA\Property(property="approved_date", type="string", format="date", example="2024-01-01"),
     *             @OA\Property(property="valid_from", type="string", format="date", example="2024-01-01"),
     *             @OA\Property(property="valid_to", type="string", format="date", example="2027-01-01"),
     *             @OA\Property(property="decision_reference", type="string", example="DEC-2024-001")
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
     *     path="/api/v5/institution-registrations/{id}",
     *     summary="Get InstitutionRegistrations by ID",
     *     tags={"InstitutionRegistrations"},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Response(response=200, description="Successful operation"),
     *     @OA\Response(response=404, description="Not found")
     * )
     */
    public function _swaggerView() {}

    /**
     * @OA\Put(
     *     path="/api/v5/institution-registrations/{id}",
     *     summary="Update InstitutionRegistrations",
     *     tags={"InstitutionRegistrations"},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="string", example="expired"),
     *             @OA\Property(property="valid_to", type="string", format="date", example="2027-01-01"),
     *             @OA\Property(property="decision_reference", type="string", example="DEC-2024-001")
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
     *     path="/api/v5/institution-registrations/{id}",
     *     summary="Delete InstitutionRegistrations",
     *     tags={"InstitutionRegistrations"},
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

    public function modifiedUser()
    {
        return $this->belongsTo(SecurityUsers::class, 'modified_user_id', 'id');
    }

    public function createdUser()
    {
        return $this->belongsTo(SecurityUsers::class, 'created_user_id', 'id');
    }
}
