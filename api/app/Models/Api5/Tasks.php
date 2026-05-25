<?php

namespace App\Models\Api5;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * POCOR-9694 — OpenEMIS Task (abstraction over Laravel queue / shadow projection).
 *
 * One row per enqueued unit of OpenEMIS-side work. Status enum mirrors the
 * existing `system_processes` convention so cross-stack reads can rely on
 * the same numeric values.
 *
 * @property int         $id
 * @property string      $task_type    alert | webhook | export | profile | import | integration | event.<name> | runtime_heartbeat
 * @property string|null $source_table Legacy table this task mirrors (alert_queue, webhook_queue, jobs, …)
 * @property int|null    $source_id    Row id in source_table (FK-shape, not enforced)
 * @property array|null  $payload_json Decoupled structured payload — independent of Laravel job serialisation
 * @property int         $status       0=NEW, 1=PROCESSING, 2=DONE, -1=ABORT, -2=FAILED
 * @property \Illuminate\Support\Carbon      $available_at Do not process before this time
 * @property \Illuminate\Support\Carbon|null $started_at
 * @property \Illuminate\Support\Carbon|null $completed_at
 * @property int                              $retry_count
 * @property \Illuminate\Support\Carbon|null $created
 * @property \Illuminate\Support\Carbon|null $modified
 *
 * @property-read \Illuminate\Database\Eloquent\Collection|TaskJobs[]     $jobs
 * @property-read \Illuminate\Database\Eloquent\Collection|TaskFailures[] $failures
 */
class Tasks extends Model
{
    use HasFactory;

    /** @var string POCOR-9694 */
    protected $table = 'tasks';

    //POCOR-9694: status enum mirrors system_processes convention
    public const STATUS_NEW = 0;
    public const STATUS_PROCESSING = 1;
    public const STATUS_DONE = 2;
    public const STATUS_ABORT = -1;
    public const STATUS_FAILED = -2;

    /**
     * POCOR-9694
     * @var string[]
     */
    protected $fillable = [
        'task_type',
        'source_table',
        'source_id',
        'payload_json',
        'status',
        'available_at',
        'started_at',
        'completed_at',
        'retry_count',
    ];

    /**
     * POCOR-9694 — DB defaults handle created/modified, no Laravel timestamps.
     * @var bool
     */
    public $timestamps = false;

    /**
     * POCOR-9694
     * @var array<string,string>
     */
    protected $casts = [
        'payload_json' => 'array',
        'status' => 'integer',
        'retry_count' => 'integer',
        'available_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'created' => 'datetime',
        'modified' => 'datetime',
    ];

    //POCOR-9694: per-attempt jobs (1:N)
    public function jobs()
    {
        return $this->hasMany(TaskJobs::class, 'task_id');
    }

    //POCOR-9694: failure detail rows (1:N — at most one per failed attempt)
    public function failures()
    {
        return $this->hasMany(TaskFailures::class, 'task_id');
    }

/**
 * @OA\PathItem(
 *     path="/api/v5/tasks"
 * )
 */
public function _swaggerPath() {}

/**
 * @OA\Get(
 *     path="/api/v5/tasks",
 *     summary="Get list of Tasks",
 *     tags={"Tasks"},
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
                          @OA\Property(property="task_type", type="string", example=null),
                          @OA\Property(property="source_table", type="string", example=null),
                          @OA\Property(property="source_id", type="integer", example=null),
                          @OA\Property(property="payload_json", type="object", example=null),
                          @OA\Property(property="status", type="integer", example=null),
                          @OA\Property(property="available_at", type="string", format="date-time", example=null),
                          @OA\Property(property="started_at", type="string", format="date-time", example=null),
                          @OA\Property(property="completed_at", type="string", format="date-time", example=null),
                          @OA\Property(property="retry_count", type="integer", example=null),
                          @OA\Property(property="created", type="string", format="date-time", example=null),
                          @OA\Property(property="modified", type="string", format="date-time", example=null)
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
 *     path="/api/v5/tasks",
 *     summary="Create a new Tasks",
 *     tags={"Tasks"},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             type="object",
                     @OA\Property(property="id", type="integer", example=null),
                     @OA\Property(property="task_type", type="string", example=null),
                     @OA\Property(property="source_table", type="string", example=null),
                     @OA\Property(property="source_id", type="integer", example=null),
                     @OA\Property(property="payload_json", type="object", example=null),
                     @OA\Property(property="status", type="integer", example=null),
                     @OA\Property(property="available_at", type="string", format="date-time", example=null),
                     @OA\Property(property="started_at", type="string", format="date-time", example=null),
                     @OA\Property(property="completed_at", type="string", format="date-time", example=null),
                     @OA\Property(property="retry_count", type="integer", example=null),
                     @OA\Property(property="created", type="string", format="date-time", example=null),
                     @OA\Property(property="modified", type="string", format="date-time", example=null)
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
 *     path="/api/v5/tasks/{id}",
 *     summary="Get Tasks by ID",
 *     tags={"Tasks"},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="ID of the Tasks",
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
 *     path="/api/v5/tasks/{id}",
 *     summary="Update Tasks",
 *     tags={"Tasks"},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="ID of the Tasks",
 *         @OA\Schema(type="string")
 *     ),
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             type="object",
                     @OA\Property(property="id", type="integer", example=null),
                     @OA\Property(property="task_type", type="string", example=null),
                     @OA\Property(property="source_table", type="string", example=null),
                     @OA\Property(property="source_id", type="integer", example=null),
                     @OA\Property(property="payload_json", type="object", example=null),
                     @OA\Property(property="status", type="integer", example=null),
                     @OA\Property(property="available_at", type="string", format="date-time", example=null),
                     @OA\Property(property="started_at", type="string", format="date-time", example=null),
                     @OA\Property(property="completed_at", type="string", format="date-time", example=null),
                     @OA\Property(property="retry_count", type="integer", example=null),
                     @OA\Property(property="created", type="string", format="date-time", example=null),
                     @OA\Property(property="modified", type="string", format="date-time", example=null)
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
 *     path="/api/v5/tasks/{id}",
 *     summary="Delete Tasks",
 *     tags={"Tasks"},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="ID of the Tasks",
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
