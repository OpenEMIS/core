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
}
