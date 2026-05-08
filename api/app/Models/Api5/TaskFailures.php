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
}
