<?php

namespace App\Models\Api5;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\InstitutionScope;

use App\Models\Concerns\WebhookQueueTrait;
class InstitutionClassStudents extends Model
{
    use HasFactory;
    use InstitutionScope;

    // POCOR-9257: Configure webhook events
    use WebhookQueueTrait;
    protected $webhookEvents = ['created', 'updated', 'deleted'];
    // ✅ Allow mass assignment
    protected $fillable = ['id', 'student_id', 'institution_class_id', 'education_grade_id', 'academic_period_id', 'next_institution_class_id', 'institution_id', 'student_status_id', 'modified_user_id', 'modified', 'created_user_id', 'created', 'student_id', 'institution_class_id', 'education_grade_id', 'academic_period_id', 'next_institution_class_id', 'institution_id', 'student_status_id', 'modified_user_id', 'created_user_id'];
    // ✅ Treat 'modified' and 'created' as timestamps
    protected $dates = ['modified', 'created'];

    protected $primaryKey = 'id';
    public $incrementing = false;
    public $timestamps = false;
    protected $table = "institution_class_students";


/**
 * @OA\PathItem(
 *     path="/api/v5/institution-class-students"
 * )
 */
public function _swaggerPath() {}

/**
 * @OA\Get(
 *     path="/api/v5/institution-class-students",
 *     summary="Get list of InstitutionClassStudents",
 *     tags={"InstitutionClassStudents"},
 *     @OA\Parameter(
 *         name="limit",
 *         in="query",
 *         required=false,
 *         description="Maximum number of results to return",
 *         @OA\Schema(type="number")
 *     ),
 *     @OA\Parameter(
 *         name="page",
 *         in="query",
 *         required=false,
 *         description="Page number for paginated results",
 *         @OA\Schema(type="number")
 *     ),
 *     @OA\Parameter(
 *         name="orderby",
 *         in="query",
 *         required=false,
 *         description="Field to order results by",
 *         @OA\Schema(type="string")
 *     ),
 *     @OA\Parameter(
 *         name="order",
 *         in="query",
 *         required=false,
 *         description="Order direction: asc or desc",
 *         @OA\Schema(type="string", enum={"asc", "desc"})
 *     ),
 *     @OA\Parameter(
 *         name="_fields",
 *         in="query",
 *         required=false,
 *         description="Comma-separated list of fields to include in response",
 *         @OA\Schema(type="string")
 *     ),
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
                          @OA\Property(property="student_id", type="integer", example=null),
                          @OA\Property(property="institution_class_id", type="integer", example=null),
                          @OA\Property(property="education_grade_id", type="integer", example=null),
                          @OA\Property(property="academic_period_id", type="integer", example=null),
                          @OA\Property(property="next_institution_class_id", type="integer", example=null),
                          @OA\Property(property="institution_id", type="integer", example=null),
                          @OA\Property(property="student_status_id", type="integer", example=null),
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
 * @OA\Post(
 *     path="/api/v5/institution-class-students",
 *     summary="Create a new InstitutionClassStudents",
 *     tags={"InstitutionClassStudents"},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             type="object",
                     @OA\Property(property="id", type="string", example=null),
                     @OA\Property(property="student_id", type="integer", example=null),
                     @OA\Property(property="institution_class_id", type="integer", example=null),
                     @OA\Property(property="education_grade_id", type="integer", example=null),
                     @OA\Property(property="academic_period_id", type="integer", example=null),
                     @OA\Property(property="next_institution_class_id", type="integer", example=null),
                     @OA\Property(property="institution_id", type="integer", example=null),
                     @OA\Property(property="student_status_id", type="integer", example=null),
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
 * @OA\Get(
 *     path="/api/v5/institution-class-students/{id}",
 *     summary="Get InstitutionClassStudents by ID",
 *     tags={"InstitutionClassStudents"},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="ID of the InstitutionClassStudents",
 *         @OA\Schema(type="string")
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
 * @OA\Put(
 *     path="/api/v5/institution-class-students/{id}",
 *     summary="Update InstitutionClassStudents",
 *     tags={"InstitutionClassStudents"},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="ID of the InstitutionClassStudents",
 *         @OA\Schema(type="string")
 *     ),
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             type="object",
                     @OA\Property(property="id", type="string", example=null),
                     @OA\Property(property="student_id", type="integer", example=null),
                     @OA\Property(property="institution_class_id", type="integer", example=null),
                     @OA\Property(property="education_grade_id", type="integer", example=null),
                     @OA\Property(property="academic_period_id", type="integer", example=null),
                     @OA\Property(property="next_institution_class_id", type="integer", example=null),
                     @OA\Property(property="institution_id", type="integer", example=null),
                     @OA\Property(property="student_status_id", type="integer", example=null),
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
 *     path="/api/v5/institution-class-students/{id}",
 *     summary="Delete InstitutionClassStudents",
 *     tags={"InstitutionClassStudents"},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="ID of the InstitutionClassStudents",
 *         @OA\Schema(type="string")
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
    public function user()
    {
        return $this->belongsTo(SecurityUsers::class, 'student_id', 'id');
    }


    public function studentStatus()
    {
        return $this->belongsTo(StudentStatuses::class, 'student_status_id', 'id');
    }


    public function institutionClass()
    {
        return $this->belongsTo(InstitutionClasses::class, 'institution_class_id', 'id');
    }


    public function securityUser()
    {
        return $this->belongsTo(SecurityUsers::class, 'student_id', 'id');
    }

    public function status()
    {
        return $this->belongsTo(StudentStatuses::class, 'student_status_id', 'id');
    }

    public function educationGrade()
    {
        return $this->belongsTo(EducationGrades::class, 'education_grade_id', 'id');
    }


    //For POCOR-8363 Start...
    public function createdUser()
    {
        return $this->belongsTo(SecurityUsers::class, 'created_user_id', 'id');
    }


    public function modifiedUser()
    {
        return $this->belongsTo(SecurityUsers::class, 'modified_user_id', 'id');
    }
    //For POCOR-8363 End...

    //POCOR-9633: start - withMeals scope: joins meal records + injects default_meal_receive_id
    // Used by GET /api/v5/institution-class-students?_scope=withMeals&institution_id=X&institution_class_id=Y&academic_period_id=Z&meal_programmes_id=P&date=D
    public function scopeWithMeals($query)
    {
        $mealProgrammesId = request()->query('_meal_programmes_id');
        $date = request()->query('_date');

        $configItem = \App\Models\Api5\ConfigItems::where('code', 'DefaultDeliveryStatus')->first();
        $defaultDeliveryStatus = $configItem->value ?? '';
        $mealReceived = \App\Models\Api5\MealReceived::where('name', $defaultDeliveryStatus)->first();
        $defaultMealReceiveId = $mealReceived->id ?? null;

        $query->with('user:id,openemis_no,first_name,last_name')
            ->join('student_statuses', 'student_statuses.id', '=', 'institution_class_students.student_status_id')
            ->where('student_statuses.code', 'CURRENT')
            ->leftJoin('student_meal_marked_records', function ($q) use ($mealProgrammesId, $date) {
                $q->on('institution_class_students.institution_class_id', '=', 'student_meal_marked_records.institution_class_id')
                    ->on('institution_class_students.institution_id', '=', 'student_meal_marked_records.institution_id')
                    ->where('student_meal_marked_records.meal_programmes_id', $mealProgrammesId)
                    ->where('student_meal_marked_records.date', $date);
            })
            ->leftJoin('institution_meal_students', function ($q) use ($mealProgrammesId, $date) {
                $q->on('institution_meal_students.institution_class_id', '=', 'institution_class_students.institution_class_id')
                    ->on('institution_meal_students.student_id', '=', 'institution_class_students.student_id')
                    ->on('institution_meal_students.institution_id', '=', 'institution_class_students.institution_id')
                    ->where('institution_meal_students.meal_programmes_id', $mealProgrammesId)
                    ->where('institution_meal_students.date', $date);
            })
            ->leftJoin('meal_received', 'meal_received.id', '=', 'institution_meal_students.meal_received_id')
            ->leftJoin('meal_benefits', 'meal_benefits.id', '=', 'institution_meal_students.meal_benefit_id')
            ->select(
                'institution_class_students.id',
                'institution_class_students.student_id',
                'institution_class_students.institution_id',
                'institution_class_students.institution_class_id',
                'institution_class_students.academic_period_id',
                'student_meal_marked_records.id as marked_meal_id',
                'institution_meal_students.id as institution_meal_student_id',
                'institution_meal_students.meal_programmes_id as meal_program_id',
                'institution_meal_students.meal_benefit_id',
                'institution_meal_students.meal_received_id',
                'institution_meal_students.date as meal_date',
                'meal_received.name as meal_received_name',
                'meal_benefits.name as meal_benefit_name',
                \DB::raw("$defaultMealReceiveId as default_meal_receive_id")
            )
            ->groupBy('institution_class_students.student_id');

        return $query;
    }
    //POCOR-9633: end

}
