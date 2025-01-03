<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InstitutionDepartments extends Model
{
    use HasFactory;
    protected $fillable = [
        'code',
        'name',
        'institution_id',
        'staff_id',
        'manager_id',
        'created_user_id',
        'created',
        'modified_user_id',
        'modified'
    ];

    protected $primaryKey = 'id';
    public $timestamps = false;
    protected $table = "institution_departments";

    public function securityUser()
    {
        return $this->belongsTo(SecurityUsers::class, 'staff_id', 'id');
    }

    public function departmentManager()
    {
        return $this->belongsTo(SecurityUsers::class, 'manager_id', 'id');
    }

    public function institution()
    {
        return $this->belongsTo(Institutions::class, 'institution_id', 'id');
    }

    


}
