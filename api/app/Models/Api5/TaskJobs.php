<?php

namespace App\Models\Api5;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

//POCOR-9694 Task execution attempts — one row per attempt
class TaskJobs extends Model
{
    use HasFactory;

    protected $table = 'task_jobs';

    //POCOR-9694: per-attempt status enum
    public const STATUS_PROCESSING = 1;
    public const STATUS_DONE = 2;
    public const STATUS_FAILED = -2;

    //POCOR-9694
    protected $fillable = [
        'task_id',
        'attempt_number',
        'started_at',
        'ended_at',
        'duration_ms',
        'status',
        'message_preview',
    ];

    public $timestamps = false;

    //POCOR-9694
    protected $casts = [
        'task_id' => 'integer',
        'attempt_number' => 'integer',
        'duration_ms' => 'integer',
        'status' => 'integer',
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'created' => 'datetime',
    ];

    //POCOR-9694
    public function task()
    {
        return $this->belongsTo(Tasks::class, 'task_id');
    }
}
