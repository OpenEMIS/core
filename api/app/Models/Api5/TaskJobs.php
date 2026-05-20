<?php

namespace App\Models\Api5;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * POCOR-9694 — OpenEMIS Task execution attempt.
 *
 * One row per attempt. A single `tasks` row may have several `task_jobs`
 * rows when retries occur — `attempt_number` distinguishes them.
 *
 * @property int         $id
 * @property int         $task_id          FK-shape → tasks.id
 * @property int         $attempt_number   1-indexed
 * @property \Illuminate\Support\Carbon      $started_at
 * @property \Illuminate\Support\Carbon|null $ended_at
 * @property int|null    $duration_ms      ended_at - started_at, in milliseconds
 * @property int         $status           1=PROCESSING, 2=DONE, -2=FAILED
 * @property string|null $message_preview  Short outcome message for UI listing (≤500 chars)
 * @property \Illuminate\Support\Carbon|null $created
 *
 * @property-read Tasks|null $task
 */
class TaskJobs extends Model
{
    use HasFactory;

    /** @var string POCOR-9694 */
    protected $table = 'task_jobs';

    //POCOR-9694: per-attempt status enum
    public const STATUS_PROCESSING = 1;
    public const STATUS_DONE = 2;
    public const STATUS_FAILED = -2;

    /**
     * POCOR-9694
     * @var string[]
     */
    protected $fillable = [
        'task_id',
        'attempt_number',
        'started_at',
        'ended_at',
        'duration_ms',
        'status',
        'message_preview',
    ];

    /** @var bool POCOR-9694 — DB defaults handle created */
    public $timestamps = false;

    /**
     * POCOR-9694
     * @var array<string,string>
     */
    protected $casts = [
        'task_id' => 'integer',
        'attempt_number' => 'integer',
        'duration_ms' => 'integer',
        'status' => 'integer',
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'created' => 'datetime',
    ];

    //POCOR-9694: parent task
    public function task()
    {
        return $this->belongsTo(Tasks::class, 'task_id');
    }

/**
 * @OA\PathItem(
 *     path="/api/v5/task-jobs"
 * )
 */
public function _swaggerPath() {}

/**
 * @OA\Get(
 *     path="/api/v5/task-jobs",
 *     summary="Get list of TaskJobs",
 *     tags={"TaskJobs"},
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
                          @OA\Property(property="id", type="integer", example=null),
                          @OA\Property(property="task_id", type="integer", example=null),
                          @OA\Property(property="attempt_number", type="integer", example=null),
                          @OA\Property(property="started_at", type="string", format="date-time", example=null),
                          @OA\Property(property="ended_at", type="string", format="date-time", example=null),
                          @OA\Property(property="duration_ms", type="integer", example=null),
                          @OA\Property(property="status", type="integer", example=null),
                          @OA\Property(property="message_preview", type="string", example=null),
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
 *     path="/api/v5/task-jobs",
 *     summary="Create a new TaskJobs",
 *     tags={"TaskJobs"},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             type="object",
                     @OA\Property(property="id", type="integer", example=null),
                     @OA\Property(property="task_id", type="integer", example=null),
                     @OA\Property(property="attempt_number", type="integer", example=null),
                     @OA\Property(property="started_at", type="string", format="date-time", example=null),
                     @OA\Property(property="ended_at", type="string", format="date-time", example=null),
                     @OA\Property(property="duration_ms", type="integer", example=null),
                     @OA\Property(property="status", type="integer", example=null),
                     @OA\Property(property="message_preview", type="string", example=null),
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
 *     path="/api/v5/task-jobs/{id}",
 *     summary="Get TaskJobs by ID",
 *     tags={"TaskJobs"},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="ID of the TaskJobs",
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
 *     path="/api/v5/task-jobs/{id}",
 *     summary="Update TaskJobs",
 *     tags={"TaskJobs"},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="ID of the TaskJobs",
 *         @OA\Schema(type="string")
 *     ),
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             type="object",
                     @OA\Property(property="id", type="integer", example=null),
                     @OA\Property(property="task_id", type="integer", example=null),
                     @OA\Property(property="attempt_number", type="integer", example=null),
                     @OA\Property(property="started_at", type="string", format="date-time", example=null),
                     @OA\Property(property="ended_at", type="string", format="date-time", example=null),
                     @OA\Property(property="duration_ms", type="integer", example=null),
                     @OA\Property(property="status", type="integer", example=null),
                     @OA\Property(property="message_preview", type="string", example=null),
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
 *     path="/api/v5/task-jobs/{id}",
 *     summary="Delete TaskJobs",
 *     tags={"TaskJobs"},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="ID of the TaskJobs",
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

}
