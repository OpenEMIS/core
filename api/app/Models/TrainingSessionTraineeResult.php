<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TrainingSessionTraineeResult extends Model
{
    use HasFactory;

    protected $primaryKey = 'id';
    public $incrementing = false;
    public $timestamps = false;
    protected $table = "training_session_trainee_results";


    public function trainingSession()
    {
        return $this->belongsTo(TrainingSession::class, 'training_session_id', 'id');
    }


    public function trainingResultType()
    {
        return $this->belongsTo(TrainingResultType::class, 'training_result_type_id', 'id');
    }


    public function user()
    {
        return $this->belongsTo(SecurityUsers::class, 'trainee_id', 'id');
    }
}
