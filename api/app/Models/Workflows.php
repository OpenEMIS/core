<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Workflows extends Model
{
    use HasFactory;
    // ✅ Allow mass assignment
    protected $fillable = ['id', 'code', 'name', 'message', 'workflow_model_id', 'modified_user_id', 'modified', 'created_user_id', 'created', 'workflow_model_id', 'modified_user_id', 'created_user_id'];
    // ✅ Treat 'modified' and 'created' as timestamps
    protected $dates = ['modified', 'created'];

    public $timestamps = false;
    protected $table = "workflows";








    public function WorkflowSteps()
    {
        return $this->hasMany(WorkflowSteps::class, 'workflow_id', 'id');
    }
}
