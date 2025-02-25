<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentGuardians extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $table = "student_guardians";
    protected $primaryKey = 'id';
    public $incrementing = false;

    // POCOR-8840 start
    /**
     * Define the relationship with the `SecurityUsers` model (Guardian).
     */
    public function guardian(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(SecurityUsers::class, 'guardian_id', 'id');
    }

    /**
     * Define the relationship with the `SecurityUsers` model (Student).
     */
    public function student(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(SecurityUsers::class, 'student_id', 'id');
    }
    // POCOR-8840 end
}
