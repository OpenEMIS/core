<?php

namespace App\Models\Api5;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * POCOR-9694 — OpenEMIS Task failure detail.
 *
 * Lazy-populated on FAILED status. One row per failed attempt; carries the
 * exception class, message, and stack trace for the Runtime UI.
 *
 * @property int         $id
 * @property int         $task_id           FK-shape → tasks.id
 * @property int|null    $task_job_id       FK-shape → task_jobs.id (the failed attempt)
 * @property string|null $exception_class
 * @property string|null $exception_message
 * @property string|null $stack_trace
 * @property bool        $retry_allowed
 * @property \Illuminate\Support\Carbon|null $created
 *
 * @property-read Tasks|null    $task
 * @property-read TaskJobs|null $job
 */
class TaskFailures extends Model
{
    use HasFactory;

    /** @var string POCOR-9694 */
    protected $table = 'task_failures';

    /**
     * POCOR-9694
     * @var string[]
     */
    protected $fillable = [
        'task_id',
        'task_job_id',
        'exception_class',
        'exception_message',
        'stack_trace',
        'retry_allowed',
    ];

    /** @var bool POCOR-9694 — DB defaults handle created */
    public $timestamps = false;

    /**
     * POCOR-9694
     * @var array<string,string>
     */
    protected $casts = [
        'task_id' => 'integer',
        'task_job_id' => 'integer',
        'retry_allowed' => 'boolean',
        'created' => 'datetime',
    ];

    //POCOR-9694: parent task
    public function task()
    {
        return $this->belongsTo(Tasks::class, 'task_id');
    }

    //POCOR-9694: the failed attempt (nullable — TasksRecorder may record a failure without a job row)
    public function job()
    {
        return $this->belongsTo(TaskJobs::class, 'task_job_id');
    }

/**
 * @OA\PathItem(
 *     path="/api/v5/task-failures"
 * )
 */
public function _swaggerPath() {}

/**
 * @OA\Get(
 *     path="/api/v5/task-failures",
 *     summary="Get list of TaskFailures",
 *     tags={"TaskFailures"},
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
                          @OA\Property(property="task_job_id", type="integer", example=null),
                          @OA\Property(property="exception_class", type="string", example=null),
                          @OA\Property(property="exception_message", type="string", example=null),
                          @OA\Property(property="stack_trace", type="string", example=null),
                          @OA\Property(property="retry_allowed", type="boolean", example=null),
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
 *     path="/api/v5/task-failures",
 *     summary="Create a new TaskFailures",
 *     tags={"TaskFailures"},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             type="object",
                     @OA\Property(property="id", type="integer", example=null),
                     @OA\Property(property="task_id", type="integer", example=null),
                     @OA\Property(property="task_job_id", type="integer", example=null),
                     @OA\Property(property="exception_class", type="string", example=null),
                     @OA\Property(property="exception_message", type="string", example=null),
                     @OA\Property(property="stack_trace", type="string", example=null),
                     @OA\Property(property="retry_allowed", type="boolean", example=null),
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
 *     path="/api/v5/task-failures/{id}",
 *     summary="Get TaskFailures by ID",
 *     tags={"TaskFailures"},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="ID of the TaskFailures",
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
 *     path="/api/v5/task-failures/{id}",
 *     summary="Update TaskFailures",
 *     tags={"TaskFailures"},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="ID of the TaskFailures",
 *         @OA\Schema(type="string")
 *     ),
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             type="object",
                     @OA\Property(property="id", type="integer", example=null),
                     @OA\Property(property="task_id", type="integer", example=null),
                     @OA\Property(property="task_job_id", type="integer", example=null),
                     @OA\Property(property="exception_class", type="string", example=null),
                     @OA\Property(property="exception_message", type="string", example=null),
                     @OA\Property(property="stack_trace", type="string", example=null),
                     @OA\Property(property="retry_allowed", type="boolean", example=null),
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
 *     path="/api/v5/task-failures/{id}",
 *     summary="Delete TaskFailures",
 *     tags={"TaskFailures"},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="ID of the TaskFailures",
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
