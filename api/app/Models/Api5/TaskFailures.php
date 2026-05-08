<?php

namespace App\Models\Api5;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

//POCOR-9694 Task failure detail — lazy-populated on FAILED status
class TaskFailures extends Model
{
    use HasFactory;

    protected $table = 'task_failures';

    //POCOR-9694
    protected $fillable = [
        'task_id',
        'task_job_id',
        'exception_class',
        'exception_message',
        'stack_trace',
        'retry_allowed',
    ];

    public $timestamps = false;

    //POCOR-9694
    protected $casts = [
        'task_id' => 'integer',
        'task_job_id' => 'integer',
        'retry_allowed' => 'boolean',
        'created' => 'datetime',
    ];

    //POCOR-9694
    public function task()
    {
        return $this->belongsTo(Tasks::class, 'task_id');
    }

    //POCOR-9694
    public function job()
    {
        return $this->belongsTo(TaskJobs::class, 'task_job_id');
    }
}
