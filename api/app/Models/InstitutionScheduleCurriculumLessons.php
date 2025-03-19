<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InstitutionScheduleCurriculumLessons extends Model
{
    use HasFactory;
    // ✅ Allow mass assignment
    protected $fillable = ['id', 'code_only', 'institution_schedule_lesson_detail_id', 'institution_subject_id', 'institution_schedule_lesson_detail_id', 'institution_subject_id'];
    // ✅ Disable Laravel's default timestamps
    public $timestamps = false;

/**
 * @OA\PathItem(
 *     path="/api/v5/institution-schedule-curriculum-lessons"
 * )
 */
public function _swaggerPath() {}

/**
 * @OA\Get(
 *     path="/api/v5/institution-schedule-curriculum-lessons",
 *     summary="Get list of InstitutionScheduleCurriculumLessons",
 *     tags={"InstitutionScheduleCurriculumLessons"},
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
                          @OA\Property(property="code_only", type="integer", example=null),
                          @OA\Property(property="institution_schedule_lesson_detail_id", type="integer", example=null),
                          @OA\Property(property="institution_subject_id", type="integer", example=null)
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
 *     path="/api/v5/institution-schedule-curriculum-lessons/{id}",
 *     summary="Get InstitutionScheduleCurriculumLessons by ID",
 *     tags={"InstitutionScheduleCurriculumLessons"},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="ID of the InstitutionScheduleCurriculumLessons",
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
 *     path="/api/v5/institution-schedule-curriculum-lessons",
 *     summary="Create a new InstitutionScheduleCurriculumLessons",
 *     tags={"InstitutionScheduleCurriculumLessons"},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             type="object",
                     @OA\Property(property="id", type="integer", example=null),
                     @OA\Property(property="code_only", type="integer", example=null),
                     @OA\Property(property="institution_schedule_lesson_detail_id", type="integer", example=null),
                     @OA\Property(property="institution_subject_id", type="integer", example=null)
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
 *     path="/api/v5/institution-schedule-curriculum-lessons/{id}",
 *     summary="Update InstitutionScheduleCurriculumLessons",
 *     tags={"InstitutionScheduleCurriculumLessons"},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="ID of the InstitutionScheduleCurriculumLessons",
 *         @OA\Schema(type="integer")
 *     ),
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             type="object",
                     @OA\Property(property="id", type="integer", example=null),
                     @OA\Property(property="code_only", type="integer", example=null),
                     @OA\Property(property="institution_schedule_lesson_detail_id", type="integer", example=null),
                     @OA\Property(property="institution_subject_id", type="integer", example=null)
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
 *     path="/api/v5/institution-schedule-curriculum-lessons/{id}",
 *     summary="Delete InstitutionScheduleCurriculumLessons",
 *     tags={"InstitutionScheduleCurriculumLessons"},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="ID of the InstitutionScheduleCurriculumLessons",
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
    public function institution_subject()
    {
        return $this->belongsTo(InstitutionSubjects::class, 'institution_subject_id', 'id');
    }
    
}
