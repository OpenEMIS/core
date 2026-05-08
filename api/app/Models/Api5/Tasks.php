<?php

namespace App\Models\Api5;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

//POCOR-9694 OpenEMIS Tasks — abstraction over Laravel queue (shadow projection)
class Tasks extends Model
{
    use HasFactory;

    protected $table = 'tasks';

    //POCOR-9694: status enum mirrors system_processes convention
    public const STATUS_NEW = 0;
    public const STATUS_PROCESSING = 1;
    public const STATUS_DONE = 2;
    public const STATUS_ABORT = -1;
    public const STATUS_FAILED = -2;

    //POCOR-9694
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

    //POCOR-9694: DB defaults handle these — no Laravel timestamps
    public $timestamps = false;

    //POCOR-9694
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

    //POCOR-9694: relations
    public function jobs()
    {
        return $this->hasMany(TaskJobs::class, 'task_id');
    }

    //POCOR-9694
    public function failures()
    {
        return $this->hasMany(TaskFailures::class, 'task_id');
    }
}
