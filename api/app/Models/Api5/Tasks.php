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
}
