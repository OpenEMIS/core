<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\UuidId;

class StudentGuardians extends Model
{
    use HasFactory;
    use UuidId;

    // ✅ Allow mass assignment
    public $timestamps = false;
    // ✅ Treat 'modified' and 'created' as timestamps
    public $incrementing = false;
    protected $fillable = ['id', 'student_id', 'guardian_id', 'guardian_relation_id', 'modified_user_id', 'modified', 'created_user_id', 'created', 'student_id', 'guardian_id', 'guardian_relation_id', 'modified_user_id', 'created_user_id'];
    protected $dates = ['modified', 'created'];
    protected $table = "student_guardians";
    protected $primaryKey = 'id';
    protected $keyType = 'string';


    // POCOR-8840 start








    public function guardian(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(SecurityUsers::class, 'guardian_id', 'id');
    }

    public function student(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(SecurityUsers::class, 'student_id', 'id');
    }

    // POCOR-8966, POCOR-9030 start
    public function guardianRelation(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(GuardianRelations::class, 'guardian_relation_id', 'id');
    }
    // POCOR-8840 end
    public function scopeWithGuardian($query)
    {
        return $query->with('guardian');
    }
    public function scopeWithStudent($query)
    {
        return $query->with('student');
    }
    public function scopeWithRelation($query)
    {
        return $query->with('guardianRelation');
    }
    public function scopeFull($query)
    {
        return $query->with('guardianRelation')->with('guardian')->with('student');
    }
    // POCOR-8966, POCOR-9030 end
}
